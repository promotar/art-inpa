<x-app-layout>
<x-slot name="header">
    <div>
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Professional Programmer</h2>
        <p class="mt-1 text-sm text-gray-500">Code learning, log monitoring, admin coding chat, and repair approval.</p>
    </div>
</x-slot>

<div style="max-width:1200px;margin:0 auto;padding:24px;direction:rtl;text-align:right">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px">
        <div>
            <h1 style="font-size:28px;font-weight:800;margin:0 0 6px">المبرمج المحترف</h1>
            <p style="margin:0;color:#4b5563">يتعلم بنية المنصة، يراقب لوجات الأخطاء، وينبه الأدمن قبل أي إصلاح برمجي.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <form method="POST" action="{{ route('admin.plugins.professional-programmer.learn') }}">
                @csrf
                <button style="border:0;border-radius:8px;background:#111827;color:#fff;padding:10px 14px;font-weight:700">ابدأ التعلم الآن</button>
            </form>
            <form method="POST" action="{{ route('admin.plugins.professional-programmer.scan') }}">
                @csrf
                <button style="border:1px solid #111827;border-radius:8px;background:#fff;color:#111827;padding:10px 14px;font-weight:700">افحص اللوجات</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div style="padding:12px 14px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;margin-bottom:16px;color:#065f46">{{ session('status') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px">
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:#fff"><strong>{{ $learningStatus['sources'] ?? 0 }}</strong><span style="display:block;color:#6b7280">مصادر مفهرسة</span></div>
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:#fff"><strong>{{ $learningStatus['code_sources'] ?? 0 }}</strong><span style="display:block;color:#6b7280">ملفات كود</span></div>
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:#fff"><strong>{{ count($incidents) }}</strong><span style="display:block;color:#6b7280">حوادث مفتوحة</span></div>
        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:#fff"><strong>{{ $learningStatus['conversation_sources'] ?? 0 }}</strong><span style="display:block;color:#6b7280">مصادر محادثات</span></div>
    </div>

    <section style="border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-bottom:18px">
        <h2 style="font-size:18px;font-weight:800;margin:0;padding:14px 16px;border-bottom:1px solid #e5e7eb">الإعدادات</h2>
        <form method="POST" action="{{ route('admin.plugins.professional-programmer.settings.update') }}" style="padding:16px;display:grid;gap:14px">
            @csrf
            @method('PATCH')
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
                @foreach (['enabled' => 'تفعيل البلجن', 'admin_widget_enabled' => 'فتح تنبيه الأدمن', 'auto_scan_logs_on_admin_request' => 'فحص تلقائي عند دخول الأدمن', 'learning_enabled' => 'تفعيل التعلم المستمر', 'repair_requires_admin_approval' => 'الإصلاح يحتاج موافقة'] as $key => $label)
                    <label style="display:flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:8px;padding:10px">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked($settings[$key])>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
                <label>Cooldown اللوجات بالثواني<input name="log_scan_cooldown_seconds" value="{{ $settings['log_scan_cooldown_seconds'] }}" type="number" min="15" max="3600" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px"></label>
                <label>حجم قراءة اللوج<input name="log_tail_bytes" value="{{ $settings['log_tail_bytes'] }}" type="number" min="16384" max="2097152" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px"></label>
                <label>عدد التنبيهات<input name="max_admin_alerts" value="{{ $settings['max_admin_alerts'] }}" type="number" min="1" max="20" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px"></label>
                <label>ملفات التعلم<input name="learning_max_files_per_run" value="{{ $settings['learning_max_files_per_run'] }}" type="number" min="25" max="5000" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px"></label>
                <label>أقصى حجم ملف<input name="learning_max_file_bytes" value="{{ $settings['learning_max_file_bytes'] }}" type="number" min="1024" max="2097152" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px"></label>
                <label>AI Gateway<input name="gateway_url" value="{{ $settings['gateway_url'] }}" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px"></label>
            </div>
            <label>System Prompt<textarea name="system_prompt" rows="4" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px">{{ $settings['system_prompt'] }}</textarea></label>
            <div><button style="border:0;border-radius:8px;background:#111827;color:#fff;padding:10px 16px;font-weight:700">حفظ الإعدادات</button></div>
        </form>
    </section>

    <section style="border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-bottom:18px">
        <h2 style="font-size:18px;font-weight:800;margin:0;padding:14px 16px;border-bottom:1px solid #e5e7eb">الحوادث المفتوحة</h2>
        <div style="overflow:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead><tr style="background:#f9fafb"><th style="padding:10px;border-bottom:1px solid #e5e7eb">الحساسية</th><th style="padding:10px;border-bottom:1px solid #e5e7eb">العنوان</th><th style="padding:10px;border-bottom:1px solid #e5e7eb">المصدر</th><th style="padding:10px;border-bottom:1px solid #e5e7eb">التكرار</th><th style="padding:10px;border-bottom:1px solid #e5e7eb">إجراء</th></tr></thead>
                <tbody>
                @forelse ($incidents as $incident)
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #f3f4f6">{{ $incident->severity }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f3f4f6">{{ $incident->title }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f3f4f6">{{ $incident->source }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f3f4f6">{{ $incident->occurrences }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f3f4f6">
                            <form method="POST" action="{{ route('admin.plugins.professional-programmer.incidents.resolve', $incident->id) }}">
                                @csrf
                                @method('PATCH')
                                <button style="border:1px solid #d1d5db;border-radius:8px;background:#fff;padding:6px 10px">تم حلها</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:14px;color:#6b7280">لا توجد حوادث مفتوحة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
        <div style="border:1px solid #e5e7eb;border-radius:8px;background:#fff">
            <h2 style="font-size:18px;font-weight:800;margin:0;padding:14px 16px;border-bottom:1px solid #e5e7eb">آخر جولات التعلم</h2>
            <div style="padding:14px;display:grid;gap:8px">
                @forelse ($learningRuns as $run)
                    <div style="border:1px solid #f3f4f6;border-radius:8px;padding:10px">{{ $run->status }} | ملفات: {{ $run->code_files_seen }} | تغييرات: {{ $run->code_files_changed }}</div>
                @empty
                    <p style="color:#6b7280;margin:0">لم يتم تشغيل التعلم بعد.</p>
                @endforelse
            </div>
        </div>
        <div style="border:1px solid #e5e7eb;border-radius:8px;background:#fff">
            <h2 style="font-size:18px;font-weight:800;margin:0;padding:14px 16px;border-bottom:1px solid #e5e7eb">موافقات الإصلاح</h2>
            <div style="padding:14px;display:grid;gap:8px">
                @forelse ($approvals as $approval)
                    <div style="border:1px solid #f3f4f6;border-radius:8px;padding:10px">#{{ $approval->id }} | {{ $approval->status }} | {{ $approval->requested_action }}</div>
                @empty
                    <p style="color:#6b7280;margin:0">لا توجد موافقات مسجلة.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
</x-app-layout>
