<?php

use Illuminate\Contracts\Console\Kernel;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerAiService;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$answer = app(ProfessionalProgrammerAiService::class)->answer('اشرح باختصار حالة مراقبة اللوجات في بلجن المبرمج المحترف.', null, null);

echo 'ai_ok='.(($answer['ok'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'endpoint='.($answer['endpoint_used'] ?? '').PHP_EOL;
echo 'message_length='.strlen((string) ($answer['message'] ?? '')).PHP_EOL;
