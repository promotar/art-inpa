<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProfessionalProgrammerLogMonitor
{
    public function __construct(private readonly ProfessionalProgrammerSettings $settings)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function scanLatest(string $trigger = 'manual'): array
    {
        if (! Schema::hasTable('professional_programmer_incidents')) {
            return ['ok' => false, 'created' => 0, 'updated' => 0, 'reason' => 'tables_missing'];
        }

        $settings = $this->settings->values();
        $created = 0;
        $updated = 0;

        foreach ($this->logPaths() as $path) {
            foreach ($this->parseTail($path, (int) $settings['log_tail_bytes']) as $entry) {
                $result = $this->storeIncident($path, $entry, $trigger);
                $created += $result === 'created' ? 1 : 0;
                $updated += $result === 'updated' ? 1 : 0;
            }
        }

        return ['ok' => true, 'created' => $created, 'updated' => $updated];
    }

    public function scanForAdminRequest(): void
    {
        $settings = $this->settings->values();

        if (! $settings['enabled'] || ! $settings['auto_scan_logs_on_admin_request']) {
            return;
        }

        $cooldown = (int) $settings['log_scan_cooldown_seconds'];
        $key = 'professional_programmer:last_log_scan';

        if (Cache::has($key)) {
            return;
        }

        try {
            $this->scanLatest('admin_request');
            Cache::put($key, true, $cooldown);
        } catch (Throwable) {
            Cache::put($key, true, $cooldown);
        }
    }

    /**
     * @return array<int, object>
     */
    public function unresolved(int $limit = 5): array
    {
        if (! Schema::hasTable('professional_programmer_incidents')) {
            return [];
        }

        return DB::table('professional_programmer_incidents')
            ->whereIn('status', ['open', 'acknowledged', 'awaiting_fix'])
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->latest('last_seen_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function logPaths(): array
    {
        return [
            storage_path('logs/laravel.log'),
            storage_path('logs/platform-error.log'),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseTail(string $path, int $bytes): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $size = filesize($path);
        if ($size === false || $size <= 0) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [];
        }

        $offset = max(0, $size - $bytes);
        fseek($handle, $offset);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        $entries = [];
        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (! preg_match('/\]\s+\w+\.(EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):\s+(.*)$/i', $line, $match)) {
                continue;
            }

            $level = strtoupper($match[1]);
            if (! in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING'], true)) {
                continue;
            }

            $entries[] = [
                'level' => $level,
                'message' => mb_substr($match[2], 0, 4000),
            ];
        }

        return $entries;
    }

    /**
     * @param array<string, string> $entry
     */
    private function storeIncident(string $path, array $entry, string $trigger): string
    {
        $message = $this->redact((string) $entry['message']);
        $level = (string) $entry['level'];
        $fingerprint = sha1($path.'|'.$level.'|'.$this->normalizeMessage($message));
        $severity = $this->severity($level, $message);
        $now = now();
        $existing = DB::table('professional_programmer_incidents')->where('fingerprint', $fingerprint)->first(['id', 'occurrences']);

        if ($existing) {
            DB::table('professional_programmer_incidents')
                ->where('id', $existing->id)
                ->update([
                    'occurrences' => ((int) $existing->occurrences) + 1,
                    'last_seen_at' => $now,
                    'context' => json_encode(['trigger' => $trigger], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);

            return 'updated';
        }

        DB::table('professional_programmer_incidents')->insert([
            'fingerprint' => $fingerprint,
            'source' => str_replace(base_path().'/', '', $path),
            'level' => strtolower($level),
            'severity' => $severity,
            'title' => $this->title($message),
            'message' => $message,
            'context' => json_encode(['trigger' => $trigger], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'occurrences' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return 'created';
    }

    private function severity(string $level, string $message): string
    {
        if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL'], true)) {
            return 'critical';
        }

        if ($level === 'ERROR' || str_contains($message, 'Exception') || str_contains($message, 'SQLSTATE')) {
            return 'high';
        }

        return $level === 'WARNING' ? 'medium' : 'low';
    }

    private function title(string $message): string
    {
        $title = preg_replace('/\s+/', ' ', trim($message)) ?: 'Laravel log incident';

        return mb_substr($title, 0, 180);
    }

    private function normalizeMessage(string $message): string
    {
        $message = preg_replace('/\d+/', '{n}', $message) ?? $message;
        $message = preg_replace('/[a-f0-9]{16,}/i', '{hash}', $message) ?? $message;

        return mb_strtolower($message);
    }

    private function redact(string $message): string
    {
        $message = preg_replace('/(password|api[_-]?key|token|secret)(["\']?\s*[:=]\s*)[^,\s\]}]+/i', '$1$2[redacted]', $message) ?? $message;

        return $message;
    }
}
