<div id="pp-widget" class="ppw" dir="rtl" data-alerts-url="{{ $alertsUrl }}" data-message-url="{{ $messageUrl }}" data-approve-url="{{ $approveUrl }}" data-dashboard-url="{{ $dashboardUrl }}">
    <button type="button" class="ppw-launcher" aria-label="Professional Programmer">DEV</button>
    <section class="ppw-panel" aria-live="polite">
        <header class="ppw-head">
            <div>
                <strong>المبرمج المحترف</strong>
                <span>مراقبة أخطاء المنصة</span>
            </div>
            <button type="button" class="ppw-close">×</button>
        </header>
        <div class="ppw-alerts"></div>
        <div class="ppw-chat"></div>
        <form class="ppw-form">
            <input type="hidden" name="incident_id" value="">
            <textarea name="message" rows="2" placeholder="اسأل عن السبب أو طريقة الإصلاح"></textarea>
            <button type="submit">إرسال</button>
        </form>
        <div class="ppw-actions">
            <button type="button" class="ppw-approve">أوافق على بدء خطة الإصلاح</button>
            <a href="{{ $dashboardUrl }}">لوحة المبرمج</a>
        </div>
    </section>
</div>
<style>
    .ppw{position:fixed;right:18px;bottom:18px;z-index:99999;font-family:Arial,Tahoma,sans-serif;text-align:right;color:#111827}
    .ppw-launcher{width:56px;height:56px;border:0;border-radius:8px;background:#111827;color:#fff;font-weight:700;box-shadow:0 12px 30px rgba(15,23,42,.25);cursor:pointer}
    .ppw-panel{display:none;width:min(420px,calc(100vw - 28px));max-height:min(680px,calc(100vh - 96px));overflow:auto;background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 18px 50px rgba(15,23,42,.28)}
    .ppw.open .ppw-panel{display:block}.ppw.open .ppw-launcher{display:none}
    .ppw-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;background:#111827;color:#fff}
    .ppw-head span{display:block;font-size:12px;color:#d1d5db;margin-top:2px}.ppw-close{border:0;background:transparent;color:#fff;font-size:24px;line-height:1;cursor:pointer}
    .ppw-alerts{padding:12px 14px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
    .ppw-incident{padding:10px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;margin-bottom:8px}
    .ppw-sev{display:inline-block;padding:2px 7px;border-radius:6px;font-size:11px;font-weight:700;background:#fee2e2;color:#991b1b;margin-left:6px}
    .ppw-chat{padding:12px 14px;display:grid;gap:8px}.ppw-msg{padding:9px 10px;border-radius:8px;line-height:1.55;font-size:13px;white-space:pre-wrap}.ppw-msg.user{background:#eff6ff}.ppw-msg.assistant{background:#f3f4f6}
    .ppw-form{display:grid;grid-template-columns:1fr auto;gap:8px;padding:12px 14px;border-top:1px solid #e5e7eb}.ppw-form textarea{resize:vertical;min-height:42px;border:1px solid #d1d5db;border-radius:8px;padding:8px;font:inherit}.ppw-form button,.ppw-approve{border:0;border-radius:8px;background:#111827;color:#fff;padding:8px 12px;font-weight:700;cursor:pointer}
    .ppw-actions{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:0 14px 14px}.ppw-actions a{color:#2563eb;text-decoration:none;font-size:13px}
</style>
<script>
(function(){
    const root=document.getElementById('pp-widget'); if(!root || root.dataset.ready) return; root.dataset.ready='1';
    const launcher=root.querySelector('.ppw-launcher'), close=root.querySelector('.ppw-close'), alerts=root.querySelector('.ppw-alerts'), chat=root.querySelector('.ppw-chat'), form=root.querySelector('.ppw-form'), incidentInput=form.querySelector('[name=incident_id]'), approve=root.querySelector('.ppw-approve');
    const csrf=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    function open(){root.classList.add('open')} function shut(){root.classList.remove('open')}
    function msg(role,text){const el=document.createElement('div');el.className='ppw-msg '+role;el.textContent=text;chat.appendChild(el);chat.scrollTop=chat.scrollHeight}
    function renderIncidents(items){
        alerts.innerHTML='';
        if(!items.length){alerts.textContent='لا توجد أخطاء مفتوحة حاليًا.';return}
        incidentInput.value=items[0].id;
        items.forEach(function(item){
            const box=document.createElement('div');box.className='ppw-incident';
            box.innerHTML='<span class="ppw-sev">'+item.severity+'</span><strong></strong><div></div><small></small>';
            box.querySelector('strong').textContent=item.title;
            box.querySelector('div').textContent=item.source+' | تكرار: '+item.occurrences;
            box.querySelector('small').textContent='آخر ظهور: '+item.last_seen_at;
            box.addEventListener('click',function(){incidentInput.value=item.id; msg('assistant','سأحلل هذا الخطأ: '+item.title);});
            alerts.appendChild(box);
        });
        open();
    }
    async function loadAlerts(){
        try{
            const res=await fetch(root.dataset.alertsUrl,{headers:{'Accept':'application/json'}});
            const data=await res.json();
            renderIncidents(data.incidents||[]);
            if((data.incidents||[]).length && !chat.dataset.intro){chat.dataset.intro='1';msg('assistant','وجدت أخطاء في لوجات الموقع. اختر الخطأ أو اسألني، وسأشرح الحساسية وخطة العلاج. لن أبدأ أي تعديل قبل موافقتك.');}
        }catch(e){}
    }
    launcher.addEventListener('click',open); close.addEventListener('click',shut);
    form.addEventListener('submit',async function(e){
        e.preventDefault(); const text=form.message.value.trim(); if(!text) return; msg('user',text); form.message.value='';
        const res=await fetch(root.dataset.messageUrl,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({message:text,incident_id:incidentInput.value||null})});
        const data=await res.json(); msg('assistant',data.message||'تمت معالجة الطلب.');
    });
    approve.addEventListener('click',async function(){
        const res=await fetch(root.dataset.approveUrl,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({incident_id:incidentInput.value||null,requested_action:'Start professional code repair plan after admin approval.'})});
        const data=await res.json(); msg('assistant',data.message||'تم تسجيل الموافقة.');
    });
    loadAlerts(); setInterval(loadAlerts,30000);
})();
</script>
