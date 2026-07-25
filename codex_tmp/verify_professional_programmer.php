<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerLearningService;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerLearningVerificationService;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerLogMonitor;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tables = [
    'professional_programmer_learning_runs',
    'professional_programmer_learning_sources',
    'professional_programmer_incidents',
    'professional_programmer_messages',
    'professional_programmer_repair_approvals',
    'professional_programmer_training_samples',
    'professional_programmer_training_jobs',
];

foreach ($tables as $table) {
    echo $table.'='.(Schema::hasTable($table) ? 'yes' : 'no').PHP_EOL;
}

echo 'settings_count='.DB::table('platform_settings')->where('group_key', 'professional_programmer')->count().PHP_EOL;
echo 'plugin_status='.DB::table('plugins')->where('slug', 'professional-programmer')->value('status').PHP_EOL;

$scan = app(ProfessionalProgrammerLogMonitor::class)->scanLatest('verify_script');
echo 'scan_ok='.(($scan['ok'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'scan_created='.($scan['created'] ?? 0).PHP_EOL;
echo 'scan_updated='.($scan['updated'] ?? 0).PHP_EOL;

$learn = app(ProfessionalProgrammerLearningService::class)->run('verify_script');
echo 'local_learning_index_ok='.(($learn['ok'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'learn_files_seen='.($learn['files_seen'] ?? 0).PHP_EOL;
echo 'learn_files_changed='.($learn['files_changed'] ?? 0).PHP_EOL;

$verification = app(ProfessionalProgrammerLearningVerificationService::class)->status();
echo 'training_endpoint_reachable='.(($verification['training_endpoint_reachable'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'learning_verified='.(($verification['learning_verified'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'active_model_version='.($verification['active_model_version'] ?? 'N/A').PHP_EOL;
echo 'candidate_model_version='.($verification['candidate_model_version'] ?? 'N/A').PHP_EOL;
