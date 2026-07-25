<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$now = now();
$error = <<<'ERR'
Illuminate\Database\QueryException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'seo_title' in 'field list' (Connection: mysql, SQL: update `blog_posts` set `seo_title` = test where `id` = 14) in /var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/PostController.php:221
ERR;
$id = DB::table('professional_programmer_incidents')->insertGetId([
 'fingerprint' => 'codex-evidence-test-'.time(),
 'source' => 'codex_verification',
 'level' => 'error',
 'severity' => 'high',
 'title' => 'SQLSTATE[42S22]: Unknown column seo_title in blog_posts',
 'message' => $error,
 'context' => json_encode(['trigger' => 'codex_evidence_console_verification']),
 'occurrences' => 1,
 'first_seen_at' => $now,
 'last_seen_at' => $now,
 'status' => 'open',
 'created_at' => $now,
 'updated_at' => $now,
]);
$diagnosis = app(Modules\ProfessionalProgrammer\ProfessionalProgrammerIncidentAnalyzer::class)->analyze($id);
echo 'incident_id='.$id.PHP_EOL;
echo 'column='.($diagnosis['database_column'] ?? '').PHP_EOL;
echo 'table='.($diagnosis['database_table'] ?? '').PHP_EOL;
echo 'file='.($diagnosis['file'] ?? '').PHP_EOL;
echo 'line='.($diagnosis['line'] ?? '').PHP_EOL;
echo 'repair_type='.($diagnosis['repair_type'] ?? '').PHP_EOL;
echo 'needs_migration='.(($diagnosis['needs_migration'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'has_generic_answer='.(str_contains($diagnosis['likely_cause'] ?? '', 'قد يكون') ? 'yes' : 'no').PHP_EOL;
echo 'likely_cause='.($diagnosis['likely_cause'] ?? '').PHP_EOL;
DB::table('professional_programmer_incidents')->where('id', $id)->update(['status' => 'suppressed', 'updated_at' => now()]);
