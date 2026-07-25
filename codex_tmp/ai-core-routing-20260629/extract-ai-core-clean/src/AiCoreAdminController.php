<?php

namespace Modules\AiCore;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiCoreAdminController extends Controller
{
    public function index(
        AiModelRegistry $models,
        AiToolRegistry $tools,
        AiCoreSettings $settings,
    ): View {
        return view('ai-core::admin.index', [
            'settings' => $settings->publicValues(),
            'models' => $models->all(),
            'tools' => $tools->all(),
            'datasets' => Schema::hasTable('ai_core_datasets')
                ? DB::table('ai_core_datasets')->orderBy('slug')->get()->map(fn (object $row): array => (array) $row)->all()
                : [],
            'requestCount' => Schema::hasTable('ai_core_requests') ? DB::table('ai_core_requests')->count() : 0,
            'auditCount' => Schema::hasTable('ai_core_audit_logs') ? DB::table('ai_core_audit_logs')->count() : 0,
        ]);
    }
}
