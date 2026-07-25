<?php

namespace Modules\ProfessionalProgrammer;

use App\Platform\Core\Services\SettingsRepository;

class ProfessionalProgrammerSettings
{
    public function __construct(private readonly SettingsRepository $settings)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $values = $this->settings->values();

        return [
            'enabled' => $this->bool($this->setting($values, 'enabled', true)),
            'admin_widget_enabled' => $this->bool($this->setting($values, 'admin_widget_enabled', true)),
            'auto_scan_logs_on_admin_request' => $this->bool($this->setting($values, 'auto_scan_logs_on_admin_request', true)),
            'log_scan_cooldown_seconds' => max(15, min(3600, (int) $this->setting($values, 'log_scan_cooldown_seconds', 60))),
            'log_tail_bytes' => max(16384, min(2097152, (int) $this->setting($values, 'log_tail_bytes', 262144))),
            'max_admin_alerts' => max(1, min(20, (int) $this->setting($values, 'max_admin_alerts', 5))),
            'learning_enabled' => $this->bool($this->setting($values, 'learning_enabled', true)),
            'learning_max_files_per_run' => max(25, min(5000, (int) $this->setting($values, 'learning_max_files_per_run', 600))),
            'learning_max_file_bytes' => max(1024, min(2097152, (int) $this->setting($values, 'learning_max_file_bytes', 524288))),
            'system_prompt' => (string) $this->setting($values, 'system_prompt', 'You are Art INPA Professional Programmer. Explain incidents in Arabic, rank severity, identify likely root cause, propose a safe repair plan, and always ask for admin approval before code changes.'),
            'repair_requires_admin_approval' => $this->bool($this->setting($values, 'repair_requires_admin_approval', true)),
            'require_fresh_training_before_repair' => $this->bool($this->setting($values, 'require_fresh_training_before_repair', true)),
            'training_fresh_minutes' => max(5, min(1440, (int) $this->setting($values, 'training_fresh_minutes', 60))),
            'require_backup_before_repair' => $this->bool($this->setting($values, 'require_backup_before_repair', true)),
            'require_written_plan_before_repair' => $this->bool($this->setting($values, 'require_written_plan_before_repair', true)),
            'web_terminal_write_allowed' => $this->bool($this->setting($values, 'web_terminal_write_allowed', false)),
            'suppress_maintenance_noise' => $this->bool($this->setting($values, 'suppress_maintenance_noise', true)),
            'backup_roots' => $this->array($this->setting($values, 'backup_roots', ['app', 'modules', 'routes', 'config', 'database/migrations', 'resources/views', 'composer.json', 'composer.lock', 'project_documentation.md', 'project.txt'])),
        ];
    }

    private function setting(array $values, string $key, mixed $default): mixed
    {
        return $this->decodeScalar($values['professional_programmer.'.$key] ?? $default);
    }

    private function bool(mixed $value): bool
    {
        $value = $this->decodeScalar($value);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<int, string>
     */
    private function array(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }

            return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value) ?: [])));
        }

        return [];
    }

    private function decodeScalar(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', trim($value)) ?? trim($value);
        if ($trimmed === '') {
            return '';
        }

        $decoded = json_decode($trimmed, true);

        return json_last_error() === JSON_ERROR_NONE && ! is_array($decoded) ? $decoded : $trimmed;
    }
}
