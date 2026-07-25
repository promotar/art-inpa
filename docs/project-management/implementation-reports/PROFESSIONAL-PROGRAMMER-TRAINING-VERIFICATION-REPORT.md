# تقرير تدريب بلجن المبرمج المحترف والتحقق منه

تاريخ التقرير: 2026-06-28

## الهدف

توضيح طريقة تدريب بلجن `Professional Programmer` على منصة Art INPA، وكيف يتم التأكد أن التدريب مطبق فعلياً وليس مجرد أرقام ظاهرة في الواجهة.

النتيجة الحالية: التدريب مطبق ومفحوص على السيرفر، والحارس الإنتاجي يمنع بدء أي مسار إصلاح قبل تحقق شروط التدريب والباكب والخطة.

## معنى التدريب في هذا البلجن

التدريب هنا ليس تدريب نموذج ذكاء اصطناعي جديد من الصفر. التدريب المقصود هو بناء ذاكرة تشغيلية داخل قاعدة بيانات Laravel تجعل البلجن يعرف حالة المنصة الحالية قبل أن يشرح أو يقترح أو يطلب موافقة إصلاح.

مصدر الحقيقة يبقى Laravel وقاعدة البيانات والكود الفعلي، وليس كلام الموديل.

## أين يتم تنفيذ التدريب

الكلاس المسؤول:

```text
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerLearningService.php
```

الدالة الرئيسية:

```text
run(string $trigger = 'manual', ?int $userId = null)
```

هذه الدالة:

1. تنشئ سجل جولة تدريب في `professional_programmer_learning_runs`.
2. تفهرس مصادر المنصة داخل `professional_programmer_learning_sources`.
3. تحفظ hashes وmetadata وملخصات تشغيلية.
4. لا تنسخ أسرار `.env` ولا قيم إعدادات حساسة.
5. تضع الجولة `completed` فقط إذا انتهت كل خطوات التدريب بدون استثناء.

## ما الذي يتعلمه البلجن فعلياً

التدريب الحالي يغطي:

```text
code
route
schema
plugin
setting
permission
documentation
migration
conversation
log
```

### 1. الكود

يفهرس الملفات من:

```text
app
modules
routes
config
database/migrations
resources/views
```

ويحفظ:

- path
- hash
- size
- extension
- line count
- summary حسب نوع الملف

يتم استبعاد:

```text
vendor
storage
bootstrap/cache
node_modules
```

### 2. الراوتات

يفهرس Laravel runtime route collection:

- methods
- uri
- route name
- action
- middleware

هذا يجعله يفهم فلو الوصول وليس الملفات فقط.

### 3. سكيمة قاعدة البيانات

يفهرس metadata فقط:

- table names
- column names

لا ينسخ قيم الجداول ولا كلمات مرور ولا مفاتيح.

### 4. البلجنات والموديولات

يفهرس ملفات:

```text
modules/*/module.json
professional-programmer-plugin/*/module.json
```

ويتعلم:

- slug
- version
- permissions
- routes
- provider boundaries

### 5. الإعدادات

يفهرس registry metadata من `platform_settings` بدون نسخ القيم نفسها:

- group key
- setting key
- type
- category
- module
- visibility
- editable
- sensitive flag

### 6. الصلاحيات

يفهرس metadata/counts من:

```text
permissions
roles
role_has_permissions
model_has_roles
```

هذا يعطيه وعي بصلاحيات المنصة بدون كشف بيانات مستخدمين حساسة.

### 7. الوثائق

يفهرس ذاكرة المشروع:

```text
project.txt
project_documentation.md
changes-log.txt
connection-method.txt
backups-log.txt
professional-programmer-plugin/professional-programmer/docs/plugin.md
```

### 8. المايجريشن

يفهرس migration ledger من جدول `migrations` حتى يعرف حالة نشر قاعدة البيانات.

### 9. المحادثات

يفهرس metadata من جداول محادثات الذكاء المتوفرة:

```text
ai_messages
ai_assistant_messages
```

يحفظ عدد الصفوف وآخر تاريخ فقط، وليس محتوى أسرار.

### 10. الحوادث واللوجات

يفهرس حالة جدول:

```text
professional_programmer_incidents
```

ويستخدم `ProfessionalProgrammerLogMonitor` لفحص لوجات Laravel بشكل bounded وسريع.

## كيف يبدأ التدريب

يوجد ثلاث طرق:

### 1. من لوحة الإدارة

المسار:

```text
/admin/plugins/professional-programmer
```

الزر:

```text
ابدأ التعلم الآن
```

ينفذ:

```text
POST /admin/plugins/professional-programmer/learn
```

### 2. تلقائياً عند دخول الأدمن

الكلاس:

```text
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerMiddleware.php
```

الدالة:

```text
refreshLearningIfStale()
```

السلوك:

- عند دخول أدمن إلى صفحة admin.
- يفحص `ProductionGuard::readiness()`.
- إذا التدريب قديم، يشغل جولة `admin_auto_refresh`.
- يوجد cache cooldown لمدة 300 ثانية حتى لا تتكرر الجولة مع كل طلب.

### 3. من تحقق/صيانة Codex

يمكن تشغيل جولة مباشرة من Laravel runtime عبر:

```php
app(Modules\ProfessionalProgrammer\ProfessionalProgrammerLearningService::class)->run('codex_verification');
```

## كيف يعرف النظام أن التدريب صالح

الكلاس:

```text
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerProductionGuard.php
```

الدالة:

```text
readiness()
```

تتحقق من:

1. وجود آخر جولة مكتملة.
2. أن عمر التدريب أقل من `training_fresh_minutes`.
3. وجود أنواع المصادر المطلوبة:

```text
code
route
schema
plugin
setting
documentation
```

4. أن الكتابة من واجهة الويب للتيرمنل غير مفعلة.

إذا فشل أي شرط، يصبح `ready_for_repair_approval=false`.

## ماذا يحدث قبل أي إصلاح

مسار الموافقة:

```text
POST /admin/plugins/professional-programmer/approve
```

يمر عبر:

```text
ProfessionalProgrammerController::approve()
ProfessionalProgrammerProductionGuard::validateRepairRequest()
ProfessionalProgrammerProductionGuard::createBackupCheckpoint()
```

الموافقة ترفض إذا لم توجد:

- خطة التعديل `proposed_plan`
- ملخص المخاطر `risk_summary`
- الأثر المتوقع `expected_impact`
- خطة الرجوع `rollback_plan`
- تدريب حديث
- باكب ناجح

حتى بعد الموافقة، الحالة تكون:

```text
approved_pending_codex
```

أي لا يوجد تعديل مباشر من الشات. التنفيذ الفعلي يجب أن يتم في جلسة صيانة/Codex موثقة.

## سياسات الأدوات

الجدول:

```text
professional_programmer_tool_policies
```

تم التحقق من وجود 8 سياسات.

السياسة العامة:

- terminal read: محدود للصيانة.
- filesystem read: محدود للصيانة.
- database metadata read: مسموح كمعلومات metadata فقط.
- terminal write: محظور من الويب.
- filesystem write: يتطلب صيانة خارج الشات.
- database write: يتطلب صيانة خارج الشات.
- deploy: يتطلب صيانة خارج الشات.
- web chat approval: يسجل موافقة فقط بعد الحراسة.

## الباكب قبل الإصلاح

الدالة:

```text
createBackupCheckpoint()
```

تنشئ checkpoint داخل:

```text
storage/app/professional-programmer/backups/pre-repair-*
```

وتحفظ:

- `manifest.json`
- `database_snapshot.json`
- نسخة من source roots المحددة في إعدادات `backup_roots`

ملاحظة مهمة:

هذا checkpoint لا يصدّر `.env` ولا قيم قاعدة البيانات. إذا كان الإصلاح يتضمن migration أو data write، يجب عمل backup تشغيل خارجي إضافي قبل التنفيذ.

## نتائج التحقق الحالية على السيرفر

السيرفر:

```text
10.10.0.20
path: /var/www/store.z4rank.com/laravel
```

### تحقق الجداول والبلجن

الأمر:

```text
php codex_tmp/verify_professional_programmer.php
```

النتيجة:

```text
professional_programmer_learning_runs=yes
professional_programmer_learning_sources=yes
professional_programmer_incidents=yes
professional_programmer_messages=yes
professional_programmer_repair_approvals=yes
settings_count=19
plugin_status=active
scan_ok=yes
scan_created=0
scan_updated=0
learn_ok=yes
learn_files_seen=365
learn_files_changed=6
```

### تحقق واجهة الإدارة

الأمر:

```text
php codex_tmp/verify_professional_programmer_admin_render.php
```

النتيجة:

```text
admin_render_status=200
admin_render_has_title=yes
```

### تحقق اتصال الذكاء

الأمر:

```text
php codex_tmp/verify_professional_programmer_ai.php
```

النتيجة:

```text
ai_ok=yes
endpoint=/v1/coding/chat
message_length=1555
```

### تحقق مصادر التدريب

آخر جولة مكتملة:

```text
last_run_id=7
last_run_status=completed
code_files_seen=365
conversation_rows_seen=203
log_incidents_seen=25
```

مصادر التعلم المسجلة:

```text
source_type_code=365
source_type_conversation=2
source_type_documentation=5
source_type_log=1
source_type_migration=1
source_type_permission=1
source_type_plugin=9
source_type_route=1
source_type_schema=51
source_type_setting=1
```

### تحقق الحارس الإنتاجي

```text
training_fresh=yes
ready_for_repair_approval=yes
training_age_minutes=3.3
missing_source_types=
tool_policies=8
backup_checkpoints=1
```

### تحقق رفض الموافقة غير المكتملة

تم التحقق سابقاً أن:

```text
approval_without_plan_blocked=yes
```

أي أن النظام لا يقبل موافقة إصلاح بدون خطة مكتوبة.

### تحقق الباكب

تم إنشاء checkpoint:

```text
checkpoint ID: 1
status: completed
files: 385
path: /var/www/store.z4rank.com/laravel/storage/app/professional-programmer/backups/pre-repair-20260628-180650
```

## هل التطبيق 100% حسب المطلوب الحالي؟

نعم، ضمن نطاق البلجن الحالي:

- التدريب يعمل فعلياً على السيرفر.
- التدريب لا يعتمد على أرقام واجهة فقط؛ تم التحقق من قاعدة البيانات والجداول.
- مصادر التدريب المطلوبة موجودة ولا يوجد missing source types.
- الجاهزية الإنتاجية مرتبطة بالحارس وليس بالشكل.
- الإصلاح لا يبدأ بدون خطة وباكب وتدريب حديث.
- الشات لا يملك صلاحية تعديل مباشر.
- اللوجات مفحوصة bounded ولا تعلق دخول الأدمن.
- الوثائق محدثة.

## Learning Verification Update - 2026-06-28

### سبب التحديث

تم فصل معنى الوصول إلى endpoint عن معنى التعلم الحقيقي:

```text
learn_ok
```

لم يعد مؤشراً مقبولاً لأنه كان يثبت أن مسار التعلم/الفهرسة يعمل فقط، ولا يثبت أن النموذج تعلم أو تحسن.

المؤشرات الجديدة:

```text
training_endpoint_reachable
learning_verified
```

### السلوك الجديد

`training_endpoint_reachable` يعني فقط أن Laravel يستطيع الوصول إلى AI Gateway training endpoint.

`learning_verified` لا يصبح `yes` إلا إذا تحققت كل الشروط:

- approved training samples were sent to the AI server
- training job completed successfully
- model_version_before is recorded
- model_version_after is recorded
- golden evaluation set was not used for training
- evaluation_before was executed
- evaluation_after was executed
- after_score > before_score
- generic_answer_count = 0
- regression_found = false
- candidate model was promoted safely

### Laravel Plugin Implementation

تمت إضافة:

```text
professional_programmer_training_samples
professional_programmer_training_jobs
ProfessionalProgrammerLearningVerificationService
```

قواعد Laravel:

- يخزن عينات تدريب من موافقات إصلاح إدارية مكتملة فقط.
- لا يدرب من raw logs مباشرة.
- لا يرسل نص log خام كعينة تدريب.
- العينة تحتوي diagnosis معتمد + repair outcome approved.
- يعرض حالة job ونسخ النموذج ونتائج التقييم في dashboard.

### AI Gateway Implementation

تمت إضافة:

```text
/v1/coding/training/status
/v1/coding/training/jobs
/v1/coding/training/jobs/{job_id}
```

الـ AI Gateway يحفظ:

```text
active_model_version
candidate_model_version
model_version_before
model_version_after
before_score
after_score
improvement_percent
generic_answer_count
regression_found
promoted
learning_verified
```

### تحقق Production

تم تشغيل اختبار controlled approved sample بدون أي إصلاح إنتاجي:

```text
sample_id=1
verification_ok=yes
training_endpoint_reachable=yes
learning_verified=yes
job_status=completed
active_model_version=professional-programmer-candidate-20260628205830-8a828ad6b079
candidate_model_version=professional-programmer-candidate-20260628205830-8a828ad6b079
before_score=0.62
after_score=0.855
improvement_percent=37.9
generic_answer_count=0
regression_found=no
promoted=yes
```

فحوصات الحالة النهائية:

```text
professional_programmer_training_samples=yes
professional_programmer_training_jobs=yes
local_learning_index_ok=yes
training_endpoint_reachable=yes
learning_verified=yes
ai_ok=yes
endpoint=/v1/coding/chat
admin_render_status=200
learning_verification_open_test_incidents=0
learning_verification_suppressed_test_incidents=1
```

### Backups

```text
/root/codex-backups/professional-programmer-learning-verification-20260628-235356
/root/codex-backups/ai-gateway-professional-training-20260628-235356
```

### حدود يجب أن تبقى واضحة

هذا النظام لا يدعي fine-tuning مباشر لأوزان LLM. هو يحقق ويروج `AI Gateway model profile` مخصصاً للمبرمج المحترف بعد إرسال عينات إدارية معتمدة وتقييم before/after. نجاح API وحده لا يكفي ولا يظهر كتعلم.

أفضل ممارسة لهذا المشروع هي:

```text
Laravel source of truth
database-backed learning index
admin-approved training samples
AI Gateway verified candidate profile
golden evaluation outside training data
production guard
AI Gateway receives authorized context
Codex/maintenance performs actual edits after approval
```

وليس:

```text
web chat directly edits files
model guesses platform state
model queries database freely
approval without backup
approval without rollback plan
```

## طريقة التشغيل الموصى بها

1. بعد أي نشر أو تعديل كبير، اضغط `ابدأ التعلم الآن` من لوحة البلجن.
2. راجع كرت `جاهزية الإنتاج قبل أي إصلاح`.
3. لا توافق على إصلاح إلا بعد تعبئة:
   - خطة التعديل
   - المخاطر
   - الأثر
   - rollback
4. تأكد أن checkpoint backup تم إنشاؤه.
5. نفذ التعديل فقط عبر جلسة Codex/maintenance موثقة.
6. بعد التنفيذ، شغل التدريب مرة أخرى حتى يتعلم الحالة الجديدة.

## توصيات تحسين مستقبلية

لرفع المستوى أكثر:

1. إضافة Artisan command رسمي:

```text
php artisan professional-programmer:learn --trigger=deploy
```

2. تشغيله تلقائياً بعد كل deployment.
3. إضافة queue job للتدريب إذا كبرت المنصة كثيراً.
4. إضافة diff learning حسب آخر commit أو mtime بدلاً من scan كامل.
5. إضافة صفحة تعرض آخر الملفات التي تغير hash لها.
6. ربطه بسجل operation_logs لكل جولة تدريب وجولة approval.

## الخلاصة

التدريب الحالي مطبق ومفحوص ويعمل بطريقة إنتاجية محافظة. البلجن صار يعرف حالة المنصة من مصادر Laravel الفعلية، ويمنع الإصلاحات غير الموثقة، ويطلب الباكب والخطة قبل أي موافقة. هذا هو المسار الصحيح حالياً لمنصة إنتاج حساسة يعمل عليها أكثر من مبرمج.
