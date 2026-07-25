<?php

namespace Modules\AiAssistant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class AiAssistantController extends Controller
{
    public function chat(AiAssistantSettings $settings): View
    {
        return view('ai-assistant::chat-page', [
            'config' => $settings->publicConfig(),
        ]);
    }

    public function message(Request $request, AiGatewayClient $client): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $conversationId = $this->conversationId($request);
        $history = $this->history($conversationId);
        $message = trim((string) $data['message']);

        $this->storeMessage($conversationId, 'user', $message);

        try {
            $reply = $client->chat($message, $history, $this->userContext($request));
        } catch (Throwable $exception) {
            $reply = 'AI Assistant is currently unavailable. Please try again shortly.';
            $this->storeMessage($conversationId, 'system', $exception->getMessage(), [
                'status' => 'error',
                'exception' => $exception::class,
            ]);
        }

        $this->storeMessage($conversationId, 'assistant', $reply);

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversationId,
            'message' => $reply,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $conversationId = $this->conversationId($request, false);
        $user = $request->user();

        return response()->json([
            'ok' => true,
            'conversation_id' => $conversationId,
            'authenticated' => $user !== null,
            'user_name' => $user ? $this->displayName($user) : null,
            'messages' => $this->history($conversationId, 50),
        ]);
    }

    public function close(Request $request): JsonResponse
    {
        $conversationId = $this->conversationId($request, false);

        if ($conversationId > 0 && Schema::hasTable('ai_assistant_conversations')) {
            DB::table('ai_assistant_conversations')->where('id', $conversationId)->delete();
        }

        $request->session()->forget('ai_assistant_conversation_id');

        return response()->json([
            'ok' => true,
        ]);
    }

    private function conversationId(Request $request, bool $create = true): int
    {
        if (! Schema::hasTable('ai_assistant_conversations')) {
            return 0;
        }

        $sessionKey = 'ai_assistant_conversation_id';
        $existing = (int) $request->session()->get($sessionKey, 0);
        $user = $request->user();

        if ($existing > 0 && $this->conversationBelongsToRequest($existing, $request)) {
            return $existing;
        }

        $stored = DB::table('ai_assistant_conversations')
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->when(! $user, fn ($query) => $query
                ->whereNull('user_id')
                ->where('session_id', $request->session()->getId()))
            ->latest('updated_at')
            ->value('id');

        if ($stored) {
            $request->session()->put($sessionKey, (int) $stored);

            return (int) $stored;
        }

        if (! $create) {
            return 0;
        }

        $id = DB::table('ai_assistant_conversations')->insertGetId([
            'session_id' => $request->session()->getId(),
            'user_id' => $user?->id,
            'source' => str_starts_with(trim($request->path(), '/'), 'admin') ? 'admin' : 'frontend',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request->session()->put($sessionKey, $id);

        return $id;
    }

    /**
     * @return array<int, array{role:string,content:string}>
     */
    private function history(int $conversationId, int $limit = 8): array
    {
        if ($conversationId <= 0 || ! Schema::hasTable('ai_assistant_messages')) {
            return [];
        }

        return DB::table('ai_assistant_messages')
            ->where('conversation_id', $conversationId)
            ->whereIn('role', ['user', 'assistant'])
            ->latest('id')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (object $message): array => [
                'role' => (string) $message->role,
                'content' => (string) $message->content,
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function storeMessage(int $conversationId, string $role, string $content, array $metadata = []): void
    {
        if ($conversationId <= 0 || ! Schema::hasTable('ai_assistant_messages')) {
            return;
        }

        DB::table('ai_assistant_messages')->insert([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ai_assistant_conversations')
            ->where('id', $conversationId)
            ->update(['updated_at' => now()]);
    }

    private function conversationBelongsToRequest(int $conversationId, Request $request): bool
    {
        $conversation = DB::table('ai_assistant_conversations')
            ->where('id', $conversationId)
            ->first(['user_id', 'session_id']);

        if (! $conversation) {
            return false;
        }

        $user = $request->user();

        if ($user) {
            return (int) $conversation->user_id === (int) $user->id;
        }

        return $conversation->user_id === null
            && hash_equals((string) $conversation->session_id, (string) $request->session()->getId());
    }

    private function userContext(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            return 'Registered user: '.$this->displayName($user).'. User id: '.$user->id.'.';
        }

        return 'Guest visitor using a session-scoped chat. Do not assume this visitor is logged in.';
    }

    private function displayName(object $user): string
    {
        $name = trim((string) ($user->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $email = trim((string) ($user->email ?? ''));

        return $email !== '' ? $email : 'User';
    }
}
