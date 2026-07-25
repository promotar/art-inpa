<div
    id="pp-widget"
    class="ppw"
    dir="rtl"
    data-alerts-url="{{ $alertsUrl }}"
    data-message-url="{{ $messageUrl }}"
    data-approve-url="{{ $approveUrl }}"
    data-dashboard-url="{{ $dashboardUrl }}"
>
    <button type="button" class="ppw__launcher" aria-label="فتح محادثة المبرمج المحترف" aria-expanded="false">
        <span class="ppw__launcher-icon" aria-hidden="true">&lt;/&gt;</span>
        <span class="ppw__launcher-label">محادثة المبرمج</span>
        <span class="ppw__badge" hidden>0</span>
    </button>

    <section class="ppw__panel" aria-live="polite" hidden>
        <header class="ppw__header">
            <div class="ppw__brand">
                <span class="ppw__brand-icon" aria-hidden="true">&lt;/&gt;</span>
                <div>
                    <strong>Repair Console</strong>
                    <span>تشخيص مبني على أدلة السجل</span>
                </div>
            </div>
            <div class="ppw__header-actions">
                <button type="button" class="ppw__expand" aria-label="تكبير">⛶</button>
                <button type="button" class="ppw__close" aria-label="إغلاق">×</button>
            </div>
        </header>

        <div class="ppw__body">
            <section class="ppw__summary">
                <div>
                    <span class="ppw__eyebrow">حالة النظام</span>
                    <strong class="ppw__summary-title">لا تشخيص بدون دليل من السجل</strong>
                </div>
                <a class="ppw__dashboard-link" href="{{ $dashboardUrl }}">لوحة البلجن</a>
            </section>

            <div class="ppw__alerts"></div>

            <div class="ppw__start-card">
                <p>ابدأ مراجعة صيانة مبنية على الدليل: نص الخطأ، الملف، الجدول، العمود، السبب المرجح، الفحوصات، المخاطر، وخطة الرجوع.</p>
                <button type="button" class="ppw__start">بدء المحادثة مع المبرمج</button>
            </div>

            <div class="ppw__chat" hidden></div>
            <div class="ppw__repair-report" hidden></div>

            <form class="ppw__form" hidden>
                <input type="hidden" name="incident_id" value="">
                <textarea name="message" rows="2" placeholder="اكتب سؤالك للمبرمج..."></textarea>
                <button type="submit">إرسال</button>
            </form>

            <div class="ppw__actions" hidden>
                <div class="ppw__approval-summary" hidden></div>
                <div class="ppw__approval-box">
                    <textarea name="proposed_plan" rows="2" placeholder="خطة التعديل المقترحة قبل أي تنفيذ"></textarea>
                    <textarea name="risk_summary" rows="2" placeholder="درجة الحساسية والمخاطر المحتملة"></textarea>
                    <textarea name="expected_impact" rows="2" placeholder="الأثر المتوقع بعد التعديل"></textarea>
                    <textarea name="rollback_plan" rows="2" placeholder="خطة الرجوع إذا ظهر أثر جانبي"></textarea>
                </div>
                <button type="button" class="ppw__approve">أوافق على بدء خطة الإصلاح</button>
            </div>
        </div>
    </section>
</div>

<style>
    #pp-widget,
    #pp-widget * {
        box-sizing: border-box;
    }

    #pp-widget.ppw {
        position: fixed;
        right: 22px;
        bottom: 96px;
        z-index: 2147482500;
        color: #111827;
        font-family: Arial, Tahoma, sans-serif;
        text-align: right;
    }

    #pp-widget .ppw__launcher {
        position: relative;
        display: inline-flex;
        min-height: 56px;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 999px;
        background: #0f172a;
        color: #fff;
        padding: 9px 16px 9px 18px;
        box-shadow: 0 18px 44px rgba(15, 23, 42, .28);
        cursor: grab;
        font: inherit;
        font-weight: 800;
        touch-action: none;
        user-select: none;
    }

    #pp-widget .ppw__launcher:active {
        cursor: grabbing;
    }

    #pp-widget .ppw__launcher-icon,
    #pp-widget .ppw__brand-icon {
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border-radius: 10px;
        background: #22c55e;
        color: #052e16;
        font-size: 13px;
        font-weight: 950;
        direction: ltr;
    }

    #pp-widget .ppw__launcher-label {
        max-width: 132px;
        overflow: hidden;
        font-size: 14px;
        line-height: 1.1;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #pp-widget .ppw__badge {
        position: absolute;
        top: -7px;
        right: -5px;
        min-width: 24px;
        height: 24px;
        border: 2px solid #fff;
        border-radius: 999px;
        background: #dc2626;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
        line-height: 20px;
        text-align: center;
    }

    #pp-widget .ppw__panel {
        position: absolute;
        right: 0;
        bottom: 72px;
        display: flex;
        width: min(760px, calc(100vw - 32px));
        max-height: min(760px, calc(100vh - 96px));
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 28px 70px rgba(15, 23, 42, .28);
    }

    #pp-widget.is-expanded .ppw__panel {
        position: fixed;
        inset: 18px;
        width: auto;
        max-height: none;
    }

    #pp-widget .ppw__panel[hidden] {
        display: none !important;
    }

    #pp-widget .ppw__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px;
        background: #0f172a;
        color: #fff;
    }

    #pp-widget .ppw__header-actions {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 8px;
    }

    #pp-widget .ppw__brand {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 10px;
    }

    #pp-widget .ppw__brand strong,
    #pp-widget .ppw__brand span {
        display: block;
    }

    #pp-widget .ppw__brand strong {
        font-size: 15px;
        line-height: 1.2;
    }

    #pp-widget .ppw__brand span {
        margin-top: 3px;
        color: #cbd5e1;
        font-size: 12px;
        line-height: 1.2;
    }

    #pp-widget .ppw__close,
    #pp-widget .ppw__expand {
        display: grid;
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        place-items: center;
        border: 0;
        border-radius: 8px;
        background: rgba(255, 255, 255, .08);
        color: #fff;
        cursor: pointer;
        font-size: 23px;
        line-height: 1;
    }

    #pp-widget .ppw__expand {
        font-size: 16px;
    }

    #pp-widget .ppw__body {
        display: flex;
        min-height: 0;
        flex: 1;
        flex-direction: column;
        gap: 12px;
        overflow: auto;
        padding: 14px;
        background: #f8fafc;
    }

    #pp-widget .ppw__summary,
    #pp-widget .ppw__start-card,
    #pp-widget .ppw__incident,
    #pp-widget .ppw__msg,
    #pp-widget .ppw__report-card,
    #pp-widget .ppw__approval-summary,
    #pp-widget .ppw__approval-box {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }

    #pp-widget .ppw__summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px;
    }

    #pp-widget .ppw__eyebrow {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
    }

    #pp-widget .ppw__summary-title {
        display: block;
        margin-top: 3px;
        color: #0f172a;
        font-size: 14px;
        line-height: 1.3;
    }

    #pp-widget .ppw__dashboard-link {
        flex: 0 0 auto;
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }

    #pp-widget .ppw__alerts {
        display: grid;
        gap: 8px;
        max-height: 150px;
        overflow: auto;
    }

    #pp-widget .ppw__incident {
        padding: 10px;
        cursor: pointer;
    }

    #pp-widget .ppw__incident:hover,
    #pp-widget .ppw__incident.is-selected {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
    }

    #pp-widget .ppw__incident-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
    }

    #pp-widget .ppw__sev {
        flex: 0 0 auto;
        border-radius: 999px;
        background: #fee2e2;
        color: #991b1b;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.2;
    }

    #pp-widget .ppw__incident-title,
    #pp-widget .ppw__incident-meta,
    #pp-widget .ppw__msg {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    #pp-widget .ppw__incident-title {
        min-width: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.45;
    }

    #pp-widget .ppw__incident-meta {
        margin-top: 7px;
        color: #64748b;
        direction: ltr;
        font-size: 11px;
        line-height: 1.45;
        text-align: left;
        unicode-bidi: plaintext;
    }

    #pp-widget .ppw__start-card {
        padding: 12px;
    }

    #pp-widget .ppw__start-card p {
        margin: 0 0 10px;
        color: #475569;
        font-size: 13px;
        line-height: 1.6;
    }

    #pp-widget .ppw__start,
    #pp-widget .ppw__form button,
    #pp-widget .ppw__approve {
        border: 0;
        border-radius: 9px;
        background: #0f172a;
        color: #fff;
        cursor: pointer;
        font: inherit;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.2;
        padding: 10px 12px;
    }

    #pp-widget .ppw__start {
        width: 100%;
        background: #16a34a;
        color: #052e16;
    }

    #pp-widget .ppw__chat {
        display: grid;
        gap: 8px;
    }

    #pp-widget .ppw__chat[hidden],
    #pp-widget .ppw__repair-report[hidden],
    #pp-widget .ppw__form[hidden],
    #pp-widget .ppw__actions[hidden] {
        display: none !important;
    }

    #pp-widget .ppw__msg {
        padding: 10px 11px;
        color: #0f172a;
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    #pp-widget .ppw__msg.user {
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    #pp-widget .ppw__msg.assistant {
        background: #fff;
    }

    #pp-widget .ppw__repair-report {
        display: grid;
        gap: 10px;
    }

    #pp-widget .ppw__report-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    #pp-widget .ppw__report-card {
        padding: 12px;
    }

    #pp-widget .ppw__report-card.is-wide {
        grid-column: 1 / -1;
    }

    #pp-widget .ppw__report-card h3 {
        margin: 0 0 7px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.3;
    }

    #pp-widget .ppw__report-card p,
    #pp-widget .ppw__report-card li,
    #pp-widget .ppw__approval-summary {
        color: #334155;
        font-size: 12px;
        line-height: 1.65;
        overflow-wrap: anywhere;
    }

    #pp-widget .ppw__report-card ul {
        margin: 0;
        padding: 0 18px 0 0;
    }

    #pp-widget .ppw__kv {
        display: grid;
        grid-template-columns: 112px minmax(0, 1fr);
        gap: 6px;
        font-size: 12px;
        line-height: 1.6;
    }

    #pp-widget .ppw__kv span:first-child {
        color: #64748b;
        font-weight: 800;
    }

    #pp-widget .ppw__approval-summary {
        padding: 10px;
        border-color: #fde68a;
        background: #fffbeb;
    }

    #pp-widget .ppw__form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 8px;
    }

    #pp-widget .ppw__form textarea {
        width: 100%;
        min-height: 48px;
        max-height: 120px;
        resize: vertical;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #fff;
        color: #0f172a;
        font: inherit;
        font-size: 13px;
        line-height: 1.5;
        padding: 10px;
        outline: none;
    }

    #pp-widget .ppw__form textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    #pp-widget .ppw__actions {
        display: grid;
        gap: 8px;
    }

    #pp-widget .ppw__approval-box {
        display: grid;
        gap: 8px;
        padding: 10px;
    }

    #pp-widget .ppw__approval-box textarea {
        width: 100%;
        min-height: 42px;
        resize: vertical;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #0f172a;
        font: inherit;
        font-size: 12px;
        line-height: 1.45;
        padding: 8px;
    }

    #pp-widget .ppw__approve {
        width: 100%;
        background: #111827;
    }

    #pp-widget .ppw__approve:disabled {
        cursor: not-allowed;
        opacity: .48;
    }

    @media (max-width: 640px) {
        #pp-widget.ppw {
            right: 12px;
            bottom: 92px;
        }

        #pp-widget .ppw__launcher {
            min-height: 54px;
            padding: 8px 12px;
        }

        #pp-widget .ppw__launcher-label {
            max-width: 112px;
            font-size: 13px;
        }

        #pp-widget .ppw__panel {
            position: fixed;
            right: 10px;
            left: 10px;
            bottom: 86px;
            width: auto;
            max-height: calc(100dvh - 110px);
        }

        #pp-widget.is-expanded .ppw__panel {
            inset: 8px;
        }

        #pp-widget .ppw__report-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
(function(){
    const root=document.getElementById('pp-widget');
    if(!root || root.dataset.ready==='1') return;
    root.dataset.ready='1';

    const launcher=root.querySelector('.ppw__launcher');
    const badge=root.querySelector('.ppw__badge');
    const panel=root.querySelector('.ppw__panel');
    const close=root.querySelector('.ppw__close');
    const expand=root.querySelector('.ppw__expand');
    const alerts=root.querySelector('.ppw__alerts');
    const chat=root.querySelector('.ppw__chat');
    const report=root.querySelector('.ppw__repair-report');
    const form=root.querySelector('.ppw__form');
    const start=root.querySelector('.ppw__start');
    const actions=root.querySelector('.ppw__actions');
    const approvalSummary=root.querySelector('.ppw__approval-summary');
    const incidentInput=form.querySelector('[name=incident_id]');
    const approve=root.querySelector('.ppw__approve');
    const csrf=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let dragStart=null, dragged=false, activeIncident=null;

    function clamp(x,y,w,h){const m=10;return{x:Math.min(Math.max(m,x),Math.max(m,window.innerWidth-w-m)),y:Math.min(Math.max(m,y),Math.max(m,window.innerHeight-h-m))};}
    function applyPos(pos){if(!pos||typeof pos.x!=='number'||typeof pos.y!=='number')return;const r=root.getBoundingClientRect();const p=clamp(pos.x,pos.y,r.width||180,r.height||60);root.style.left=p.x+'px';root.style.top=p.y+'px';root.style.right='auto';root.style.bottom='auto';}
    try{applyPos(JSON.parse(localStorage.getItem('art-inpa.professional-programmer.widget-position')||'null'));}catch(e){}

    launcher.addEventListener('pointerdown',function(e){if(e.button!==undefined&&e.button!==0)return;const r=root.getBoundingClientRect();dragStart={id:e.pointerId,x:e.clientX,y:e.clientY,left:r.left,top:r.top,width:r.width,height:r.height};dragged=false;launcher.setPointerCapture&&launcher.setPointerCapture(e.pointerId);});
    launcher.addEventListener('pointermove',function(e){if(!dragStart||dragStart.id!==e.pointerId)return;const dx=e.clientX-dragStart.x,dy=e.clientY-dragStart.y;if(Math.abs(dx)+Math.abs(dy)<4)return;dragged=true;const p=clamp(dragStart.left+dx,dragStart.top+dy,dragStart.width,dragStart.height);root.style.left=p.x+'px';root.style.top=p.y+'px';root.style.right='auto';root.style.bottom='auto';e.preventDefault();});
    launcher.addEventListener('pointerup',function(e){if(!dragStart||dragStart.id!==e.pointerId)return;const r=root.getBoundingClientRect();if(dragged){try{localStorage.setItem('art-inpa.professional-programmer.widget-position',JSON.stringify({x:r.left,y:r.top}));}catch(err){}root.dataset.dragged='1';setTimeout(function(){delete root.dataset.dragged;},0);}dragStart=null;});
    window.addEventListener('resize',function(){const r=root.getBoundingClientRect();applyPos({x:r.left,y:r.top});});

    function open(){panel.hidden=false;launcher.setAttribute('aria-expanded','true');}
    function shut(){panel.hidden=true;launcher.setAttribute('aria-expanded','false');}
    function showConversation(){chat.hidden=false;form.hidden=false;actions.hidden=false;if(!chat.dataset.intro){chat.dataset.intro='1';msg('assistant','أنا جاهز. اختر خطأ من القائمة أو اكتب سؤالك، وسأبدأ من الدليل الموجود في السجل قبل أي تحليل.');}form.message.focus();}
    function msg(role,text){const el=document.createElement('div');el.className='ppw__msg '+role;el.textContent=text;chat.appendChild(el);chat.scrollTop=chat.scrollHeight;return el;}
    function text(value,fallback){return value===null||value===undefined||value===''?fallback:String(value);}
    function list(items){return '<ul>'+(items&&items.length?items:['غير متوفر من السجل الحالي.']).map(function(item){return '<li>'+escapeHtml(String(item))+'</li>';}).join('')+'</ul>';}
    function escapeHtml(value){return value.replace(/[&<>"']/g,function(ch){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]);});}
    function card(title,body,wide){return '<section class="ppw__report-card '+(wide?'is-wide':'')+'"><h3>'+escapeHtml(title)+'</h3>'+body+'</section>';}
    function approvalFields(){return ['proposed_plan','risk_summary','expected_impact','rollback_plan'].map(function(name){return root.querySelector('[name='+name+']');}).filter(Boolean);}
    function updateApprovalState(){
        const ready=approvalFields().every(function(field){return field.value.trim()!=='';});
        approve.disabled=!ready;
        approve.title=ready?'':'يجب تعبئة خطة التعديل، المخاطر، الأثر المتوقع، وخطة الرجوع قبل الموافقة.';
    }
    function fillApproval(diagnosis){
        if(!diagnosis||!diagnosis.auto_fill)return;
        ['proposed_plan','risk_summary','expected_impact','rollback_plan'].forEach(function(name){
            const field=root.querySelector('[name='+name+']');
            if(field) field.value=diagnosis.auto_fill[name]||'';
        });
        approvalSummary.hidden=false;
        approvalSummary.textContent=diagnosis.approval_summary||'سيتم إنشاء backup checkpoint قبل تسجيل الموافقة، ولن يتم تنفيذ أي تعديل مباشر من الشات.';
        updateApprovalState();
    }
    function renderDiagnosis(diagnosis){
        if(!diagnosis){return;}
        report.hidden=false;
        const location=(diagnosis.file?diagnosis.file:'غير محدد')+(diagnosis.line?':'+diagnosis.line:'');
        const db=(diagnosis.database_table||diagnosis.database_column)?[
            diagnosis.database_table?'الجدول: '+diagnosis.database_table:null,
            diagnosis.database_column?'العمود: '+diagnosis.database_column:null,
            diagnosis.sqlstate?'SQLSTATE: '+diagnosis.sqlstate:null
        ].filter(Boolean).join(' | '):'لا يوجد جدول/عمود واضح في السجل.';
        report.innerHTML='<div class="ppw__report-grid">'
            +card('نص الخطأ الأصلي','<p>'+escapeHtml(text(diagnosis.original_error,'غير متوفر.'))+'</p>',true)
            +card('الملف والسطر','<div class="ppw__kv"><span>الموقع</span><strong>'+escapeHtml(location)+'</strong></div><div class="ppw__kv"><span>المصدر</span><strong>'+escapeHtml(text(diagnosis.source,'غير محدد'))+'</strong></div>',false)
            +card('الجدول أو العمود','<p>'+escapeHtml(db)+'</p>',false)
            +card('السبب المرجح','<p>'+escapeHtml(text(diagnosis.likely_cause,'غير محدد من الدليل.'))+'</p>',true)
            +card('الأدلة',list(diagnosis.evidence),false)
            +card('أسباب مستبعدة',list(diagnosis.excluded_causes),false)
            +card('فحص مطلوب قبل الإصلاح',list(diagnosis.required_checks),true)
            +card('نوع الإصلاح والخطورة','<div class="ppw__kv"><span>الخطورة</span><strong>'+escapeHtml(text(diagnosis.severity,'medium'))+'</strong></div><div class="ppw__kv"><span>نوع الإصلاح</span><strong>'+escapeHtml(text(diagnosis.repair_type,'unknown'))+'</strong></div><div class="ppw__kv"><span>Migration</span><strong>'+(diagnosis.needs_migration?'نعم':'لا')+'</strong></div><div class="ppw__kv"><span>تعديل كود</span><strong>'+(diagnosis.needs_code_change?'نعم':'لا')+'</strong></div><div class="ppw__kv"><span>تنظيف بيانات</span><strong>'+(diagnosis.needs_data_cleanup?'نعم':'لا')+'</strong></div><div class="ppw__kv"><span>Backup</span><strong>'+(diagnosis.backup_and_approval_required?'إلزامي':'غير مطلوب')+'</strong></div>',true)
            +'</div>';
        fillApproval(diagnosis);
    }
    function selectedBox(id){alerts.querySelectorAll('.ppw__incident').forEach(function(node){node.classList.toggle('is-selected',node.dataset.id===String(id));});}
    function renderIncidents(items){
        alerts.innerHTML='';
        const count=items.length;
        if(count>0){badge.hidden=false;badge.textContent=String(Math.min(count,99));}else{badge.hidden=true;}
        if(!count){alerts.innerHTML='<div class="ppw__incident"><div class="ppw__incident-title">لا توجد أخطاء مفتوحة حاليًا.</div><div class="ppw__incident-meta">Log monitor is clean.</div></div>';return;}
        if(!activeIncident){activeIncident=items[0];incidentInput.value=activeIncident.id;}
        items.forEach(function(item){
            const box=document.createElement('button');
            box.type='button';
            box.className='ppw__incident';
            box.dataset.id=item.id;
            box.innerHTML='<div class="ppw__incident-top"><span class="ppw__sev"></span><strong class="ppw__incident-title"></strong></div><div class="ppw__incident-meta"></div>';
            box.querySelector('.ppw__sev').textContent=item.severity||item.level||'log';
            box.querySelector('.ppw__incident-title').textContent=item.title||'Log incident';
            box.querySelector('.ppw__incident-meta').textContent=(item.source||'unknown source')+' | تكرار: '+(item.occurrences||1);
            box.addEventListener('click',function(){activeIncident=item;incidentInput.value=item.id;selectedBox(item.id);showConversation();msg('assistant','تم اختيار الخطأ: '+(item.title||'Log incident')+'\nاكتب: حلل هذا الخطأ، وسأعرض تقرير إصلاح مبني على الأدلة.');});
            alerts.appendChild(box);
        });
        selectedBox(activeIncident.id);
    }
    async function loadAlerts(){
        try{
            const res=await fetch(root.dataset.alertsUrl,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
            const data=await res.json();
            renderIncidents(data.incidents||[]);
        }catch(e){}
    }
    async function postJson(url,payload){
        const res=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},body:JSON.stringify(payload)});
        return res.json().catch(function(){return{ok:false,message:'تعذر قراءة رد الخادم.'};});
    }

    launcher.addEventListener('click',function(){if(root.dataset.dragged==='1')return;panel.hidden?open():shut();});
    close.addEventListener('click',shut);
    expand.addEventListener('click',function(){root.classList.toggle('is-expanded');});
    start.addEventListener('click',showConversation);
    approvalFields().forEach(function(field){field.addEventListener('input',updateApprovalState);});
    form.addEventListener('submit',async function(e){
        e.preventDefault();
        const text=form.message.value.trim();
        if(!text)return;
        msg('user',text);
        form.message.value='';
        const loading=msg('assistant','جار التحليل...');
        const data=await postJson(root.dataset.messageUrl,{message:text,incident_id:incidentInput.value||null});
        loading.remove();
        msg('assistant',data.message||'تمت معالجة الطلب.');
        renderDiagnosis(data.diagnosis||null);
    });
    approve.addEventListener('click',async function(){
        if(!root.querySelector('[name=proposed_plan]').value.trim() || !root.querySelector('[name=risk_summary]').value.trim() || !root.querySelector('[name=expected_impact]').value.trim() || !root.querySelector('[name=rollback_plan]').value.trim()){
            msg('assistant','لا يمكن طلب الموافقة قبل تعبئة خطة التعديل، المخاطر، الأثر المتوقع، وخطة الرجوع. ابدأ بتحليل الخطأ أولاً حتى يتم تعبئتها من الدليل.');
            return;
        }
        const payload={
            incident_id:incidentInput.value||null,
            requested_action:'Start professional code repair plan after admin approval.',
            proposed_plan:root.querySelector('[name=proposed_plan]').value.trim(),
            risk_summary:root.querySelector('[name=risk_summary]').value.trim(),
            expected_impact:root.querySelector('[name=expected_impact]').value.trim(),
            rollback_plan:root.querySelector('[name=rollback_plan]').value.trim()
        };
        const data=await postJson(root.dataset.approveUrl,payload);
        msg('assistant',data.message||'تم تسجيل الموافقة.');
    });
    updateApprovalState();
    loadAlerts();
    setInterval(loadAlerts,30000);
})();
</script>
