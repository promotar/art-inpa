<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfessionalProgrammerIncidentAnalyzer
{
    /**
     * @return array<string, mixed>|null
     */
    public function analyze(?int $incidentId): ?array
    {
        if (! $incidentId || ! Schema::hasTable('professional_programmer_incidents')) {
            return null;
        }

        $incident = DB::table('professional_programmer_incidents')->where('id', $incidentId)->first();
        if (! $incident) {
            return null;
        }

        $message = (string) $incident->message;
        $title = (string) $incident->title;
        $text = trim($title."\n".$message);
        $sql = $this->sqlEvidence($text);
        $location = $this->locationEvidence($text);
        $repairType = $this->repairType($sql, $text);
        $severity = (string) ($incident->severity ?? 'medium');

        return [
            'schema_version' => 'professional-programmer.evidence-debug/v1',
            'incident_id' => $incident->id,
            'source' => $incident->source,
            'severity' => $severity,
            'level' => $incident->level,
            'original_error' => $this->compactOriginalError($message !== '' ? $message : $title),
            'file' => $location['file'],
            'line' => $location['line'],
            'sqlstate' => $sql['sqlstate'],
            'database_table' => $sql['table'],
            'database_column' => $sql['column'],
            'sql_operation' => $sql['operation'],
            'likely_cause' => $this->likelyCause($sql, $text),
            'excluded_causes' => $this->excludedCauses($sql, $location, $text),
            'required_checks' => $this->requiredChecks($sql, $location, $repairType),
            'repair_type' => $repairType,
            'needs_migration' => $repairType === 'migration',
            'needs_code_change' => $repairType === 'code',
            'needs_data_cleanup' => $repairType === 'data_cleanup',
            'backup_and_approval_required' => true,
            'approval_summary' => $this->approvalSummary($sql, $location, $repairType),
            'auto_fill' => $this->autoFill($sql, $location, $repairType, $severity),
            'evidence' => array_values(array_filter([
                $sql['evidence'],
                $location['evidence'],
                $this->frameworkEvidence($text),
            ])),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function sqlEvidence(string $text): array
    {
        $sqlstate = null;
        $table = null;
        $column = null;
        $operation = null;
        $evidence = null;

        if (preg_match('/SQLSTATE\[([A-Z0-9]+)\]/i', $text, $match)) {
            $sqlstate = $match[1];
            $evidence = 'ظهر SQLSTATE['.$sqlstate.'] في السجل.';
        }

        foreach ([
            '/Unknown column [\'"`]([^\'"`]+)[\'"`]/i',
            '/Column not found:\s*\d+\s+Unknown column [\'"`]([^\'"`]+)[\'"`]/i',
            '/Unknown column\s+([a-zA-Z0-9_.-]+)/i',
            '/FOREIGN KEY \([`"\']?([^`"\')]+)[`"\']?\)/i',
        ] as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $column = trim($match[1], '`"\' ');
                break;
            }
        }

        if (! $column && preg_match('/Duplicate entry [\'"`][^\'"`]+[\'"`] for key [\'"`]([^\'"`]+)[\'"`]/i', $text, $match)) {
            $key = trim($match[1], '`"\' ');
            if (preg_match('/_([a-zA-Z0-9]+)_unique$/', $key, $keyMatch)) {
                $column = $keyMatch[1];
            }
        }

        if (! $column && preg_match('/insert\s+into\s+[`"]?[a-zA-Z0-9_]+[`"]?\s*\(([^)]+)\)/i', $text, $match)) {
            $columns = array_values(array_filter(array_map(
                fn (string $value): string => trim($value, " `\"'\t\n\r\0\x0B"),
                explode(',', $match[1])
            )));
            if (count($columns) === 1) {
                $column = $columns[0];
            }
        }

        foreach ([
            '/foreign key constraint fails\s+\([`"\']?[^`"\'.]+[`"\']?\.[`"\']?([^`"\')]+)[`"\']?/i',
            '/Table [\'"`]([^\'"`]+)[\'"`] doesn[\'’]?t exist/i',
            '/Base table or view not found:\s*\d+\s+Table [\'"`]([^\'"`]+)[\'"`]/i',
            '/table [`"\']?([a-zA-Z0-9_.]+)[`"\']?,\s+CONSTRAINT/i',
            '/(?:from|into|update|join)\s+[`"]([a-zA-Z0-9_]+)[`"]/i',
        ] as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $table = $this->stripDatabasePrefix(trim($match[1], '`"\' '));
                break;
            }
        }

        if (preg_match('/\b(select|insert|update|delete|alter)\b/i', $text, $match)) {
            $operation = strtolower($match[1]);
        }

        if ($column && ! $evidence) {
            $evidence = 'السجل يذكر العمود: '.$column.'.';
        }
        if ($table) {
            $evidence = trim(($evidence ?: '').' الجدول المستدل عليه: '.$table.'.');
        }

        return compact('sqlstate', 'table', 'column', 'operation', 'evidence');
    }

    /**
     * @return array<string, string|null|int>
     */
    private function locationEvidence(string $text): array
    {
        $file = null;
        $line = null;
        $evidence = null;

        foreach ([
            '/(\/var\/www\/[^:\s]+\.php):(\d+)/',
            '/(\/var\/www\/[^:\s]+\.blade\.php):(\d+)/',
            '/\b(in|at)\s+(\/var\/www\/[^:\s]+):(\d+)/i',
        ] as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $file = $match[count($match) - 2];
                $line = (int) $match[count($match) - 1];
                break;
            }
        }

        if ($file) {
            $file = str_replace(base_path().'/', '', $file);
            $evidence = 'السجل يشير إلى الملف '.$file.($line ? ' على السطر '.$line : '').'.';
        }

        return compact('file', 'line', 'evidence');
    }

    /**
     * @param array<string, string|null> $sql
     */
    private function repairType(array $sql, string $text): string
    {
        if ($sql['column'] && str_contains(strtolower($text), 'unknown column')) {
            return 'migration';
        }

        if ($sql['table'] && (str_contains(strtolower($text), "doesn't exist") || str_contains(strtolower($text), 'base table or view not found'))) {
            return 'migration';
        }

        if (str_contains(strtolower($text), 'duplicate entry') || str_contains(strtolower($text), 'integrity constraint') || str_contains(strtolower($text), 'foreign key constraint fails')) {
            return 'data_cleanup';
        }

        if (str_contains(strtolower($text), 'sql syntax') || str_contains(strtolower($text), 'syntax error or access violation')) {
            return 'unknown';
        }

        if (str_contains(strtolower($text), 'syntax error') || str_contains(strtolower($text), 'undefined variable') || str_contains(strtolower($text), 'undefined method')) {
            return 'code';
        }

        if (str_contains(strtolower($text), 'permission denied') || str_contains(strtolower($text), 'failed to open stream')) {
            return 'code';
        }

        if (str_contains(strtolower($text), 'maximum execution time')) {
            return 'code';
        }

        return 'unknown';
    }

    /**
     * @param array<string, string|null> $sql
     */
    private function likelyCause(array $sql, string $text): string
    {
        if (str_contains(strtolower($text), 'duplicate entry')) {
            return 'من السجل يظهر أن المشكلة Duplicate entry على قيد unique. مركز المشكلة بيانات مكررة أو إدخال لا يفحص وجود القيمة قبل الحفظ.';
        }

        if (str_contains(strtolower($text), 'integrity constraint') || str_contains(strtolower($text), 'foreign key constraint fails')) {
            return 'من السجل يظهر أن المشكلة Integrity constraint. مركز المشكلة علاقة بيانات غير صالحة أو سجل مرتبط مفقود، وليس نقص جدول مثبت بالدليل.';
        }

        if ($sql['column']) {
            return 'من السجل يظهر أن العمود `'.$sql['column'].'` هو مركز المشكلة. الكود أو الاستعلام يطلب هذا العمود بينما سكيمة الجدول لا توفره أو الاسم مختلف.';
        }

        if ($sql['table']) {
            return 'من السجل يظهر أن الجدول `'.$sql['table'].'` هو مركز المشكلة. الاستعلام يعتمد على جدول غير موجود أو غير migrated في قاعدة الإنتاج.';
        }

        if (str_contains(strtolower($text), 'permission denied') || str_contains(strtolower($text), 'failed to open stream')) {
            return 'من السجل يظهر أن المشكلة Permission denied أثناء وصول التطبيق إلى ملف أو مسار. مركز المشكلة صلاحيات/ملكية المسار أو طريقة الكتابة في الكود.';
        }

        if (str_contains(strtolower($text), 'maximum execution time')) {
            return 'من السجل يظهر أن المشكلة timeout أثناء تنفيذ طلب طويل. مركز المشكلة هو مسار/استعلام بطيء وليس خطأ صلاحيات مباشر.';
        }

        if (str_contains(strtolower($text), 'undefined variable')) {
            return 'من السجل يظهر أن متغيراً غير معرف مستخدم في الملف المشار إليه. مركز المشكلة في الكود وليس في قاعدة البيانات.';
        }

        if (str_contains(strtolower($text), 'undefined method')) {
            return 'من السجل يظهر أن Method غير موجودة في الكلاس المذكور. مركز المشكلة استدعاء كود غير مطابق للـ model/service الحالي.';
        }

        return 'insufficient evidence: الدليل المتاح لا يحتوي جدولاً أو عموداً أو ملفاً/سطراً كافياً لتشخيص إصلاح آمن. يجب استخراج stack trace الكامل ونص الاستعلام قبل تقديم إصلاح.';
    }

    /**
     * @param array<string, string|null> $sql
     * @param array<string, string|null|int> $location
     * @return array<int, string>
     */
    private function excludedCauses(array $sql, array $location, string $text): array
    {
        $excluded = [];

        if ($sql['column'] || $sql['table']) {
            $excluded[] = 'ليست مشكلة عامة في SQL syntax لأن السجل يحدد '.($sql['column'] ? 'عموداً واضحاً' : 'جدولاً واضحاً').'.';
            $excluded[] = 'ليست مشكلة اتصال قاعدة البيانات إذا كان السجل وصل إلى مرحلة تنفيذ الاستعلام وأرجع SQLSTATE.';
        }

        if ($location['file']) {
            $excluded[] = 'ليست مشكلة واجهة عامة فقط لأن السجل يحتوي ملفاً/سياقاً تنفيذياً محدداً.';
        }

        if (str_contains(strtolower($text), 'maximum execution time')) {
            $excluded[] = 'ليست مشكلة column missing إذا لم يظهر Unknown column أو Base table في نص الخطأ.';
        }

        if (str_contains(strtolower($text), 'permission denied')) {
            $excluded[] = 'ليست مشكلة missing column/table لأن السجل لا يحتوي SQLSTATE أو اسم جدول/عمود.';
        }

        return $excluded ?: ['لا توجد أسباب مستبعدة كفاية بدون stack trace أو SQLSTATE أو ملف/سطر واضح.'];
    }

    /**
     * @param array<string, string|null> $sql
     * @param array<string, string|null|int> $location
     * @return array<int, string>
     */
    private function requiredChecks(array $sql, array $location, string $repairType): array
    {
        $checks = [];

        if ($sql['table']) {
            $checks[] = 'افحص وجود الجدول `'.$sql['table'].'` وأعمدته في قاعدة الإنتاج.';
        }
        if ($sql['column']) {
            $checks[] = 'افحص هل العمود `'.$sql['column'].'` موجود في migration أو model fillable/casts أو query.';
        }
        if ($location['file']) {
            $checks[] = 'افتح الملف `'.$location['file'].'` وراجع السطر '.($location['line'] ?: 'المذكور في stack trace').'.';
        }
        if ($repairType === 'migration') {
            $checks[] = 'افحص `php artisan migrate:status` قبل أي migration جديد.';
        }
        if ($repairType === 'code') {
            $checks[] = 'افحص آخر تعديل مرتبط بالملف أو المسار قبل تغيير الكود.';
        }
        if ($repairType === 'data_cleanup') {
            $checks[] = 'افحص السجلات المتعارضة أو العلاقات المكسورة بقراءة فقط قبل أي تنظيف بيانات.';
        }
        if ($repairType === 'unknown') {
            $checks[] = 'الدليل غير كاف. اجمع نص الاستعلام الكامل وstack trace قبل صياغة خطة إصلاح.';
        }

        $checks[] = 'اعمل backup قبل الإصلاح ولا تنفذ تعديل من واجهة الشات.';

        return $checks;
    }

    private function frameworkEvidence(string $text): ?string
    {
        if (str_contains($text, 'Illuminate\\Database\\QueryException')) {
            return 'الخطأ مر عبر Illuminate\\Database\\QueryException.';
        }

        if (str_contains($text, 'Symfony\\Component\\ErrorHandler\\Error\\FatalError')) {
            return 'الخطأ FatalError من Symfony ErrorHandler.';
        }

        return null;
    }

    /**
     * @param array<string, string|null> $sql
     * @param array<string, string|null|int> $location
     */
    private function approvalSummary(array $sql, array $location, string $repairType): string
    {
        if ($repairType === 'unknown') {
            return 'لا يمكن تجهيز موافقة إصلاح آمنة: insufficient evidence. يجب إكمال الدليل قبل أي موافقة أو backup إصلاح.';
        }

        $parts = ['قبل الموافقة سيعرض النظام خطة الإصلاح والباكب المطلوب.'];

        if ($location['file']) {
            $parts[] = 'الملف المحتمل: '.$location['file'].($location['line'] ? ':'.$location['line'] : '');
        }
        if ($sql['table']) {
            $parts[] = 'الجدول المتأثر: '.$sql['table'];
        }
        if ($sql['column']) {
            $parts[] = 'العمود المتأثر: '.$sql['column'];
        }

        $parts[] = 'نوع الإصلاح: '.$repairType.'.';

        return implode(' ', $parts);
    }

    /**
     * @param array<string, string|null> $sql
     * @param array<string, string|null|int> $location
     * @return array<string, string>
     */
    private function autoFill(array $sql, array $location, string $repairType, string $severity): array
    {
        if ($repairType === 'unknown') {
            return [
                'proposed_plan' => '',
                'risk_summary' => '',
                'expected_impact' => '',
                'rollback_plan' => '',
            ];
        }

        $target = trim(implode(' ', array_filter([
            $location['file'] ? 'الملف '.$location['file'] : null,
            $sql['table'] ? 'الجدول '.$sql['table'] : null,
            $sql['column'] ? 'العمود '.$sql['column'] : null,
        ])));

        return [
            'proposed_plan' => 'فحص الدليل الأصلي أولاً، ثم مراجعة '.$target.'، وتحديد هل الإصلاح يحتاج migration أو تعديل كود قبل تنفيذ أي تغيير.',
            'risk_summary' => 'مستوى الخطورة: '.$severity.'. الخطر الأساسي هو تعديل إنتاجي مرتبط بـ '.$repairType.' بدون تحقق سكيمة/كود وبدون باكب.',
            'expected_impact' => 'بعد الإصلاح المتوقع إزالة الخطأ المرتبط بالدليل المحدد فقط، بدون تعديل أجزاء غير مرتبطة من المنصة.',
            'rollback_plan' => 'استخدام backup checkpoint قبل الإصلاح، ثم إعادة الملف أو migration/البيانات المتأثرة فقط إذا ظهر أثر جانبي.',
        ];
    }

    private function compactOriginalError(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message)) ?: 'لا يوجد نص خطأ محفوظ.';

        return mb_substr($message, 0, 1200);
    }

    private function stripDatabasePrefix(string $table): string
    {
        if (str_contains($table, '.')) {
            return trim(substr($table, strrpos($table, '.') + 1), '`"\' ');
        }

        return $table;
    }
}
