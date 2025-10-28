const $  = (s,r=document)=>r.querySelector(s);
const $$ = (s,r=document)=>[...r.querySelectorAll(s)];

function onReady(fn){document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();}

async function post(url, data) {
  const body = new URLSearchParams(data||{});
  const res = await fetch(`${window.BASE_PATH}${url}`, {
    method:'POST',
    headers: {'X-CSRF': window.CSRF},
    body
  });
  return res.json();
}
async function get(url) {
  const res = await fetch(`${window.BASE_PATH}${url}`, {headers:{'X-CSRF':window.CSRF}});
  return res.json();
}

onReady(()=>{
  const y=$('#year'); if(y) y.textContent=new Date().getFullYear();

  // Admin tab switching
  const tabs = $$('.tabs .tab'); const panels = $$('.tabpanels .tabpanel');
  if (tabs.length && panels.length){
    tabs.forEach(t=>{
      t.addEventListener('click', ()=>{
        tabs.forEach(x=>x.classList.remove('tab--active')); t.classList.add('tab--active');
        const target=t.dataset.tab;
        panels.forEach(p=>p.classList.toggle('tabpanel--active', p.id===`tab-${target}`));
        if (location.pathname.endsWith('/admin_users.php')) loadUsers(target);
        if (location.pathname.endsWith('/admin_referrals.php')) loadRefs(target);
      });
    });
  }

  // Dashboard load
  if (location.pathname.endsWith('/dashboard.php')) initDashboard();
  if (location.pathname.endsWith('/admin_users.php')) loadUsers('pending');
  if (location.pathname.endsWith('/admin_referrals.php')) loadRefs('pending');

  // Admin Points
  const adjForm = $('#points-adjust-form');
  if (adjForm){
    adjForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(adjForm);
      const r = await post('/api/admin_points_adjust.php', Object.fromEntries(fd));
      alert(r.ok ? 'Adjusted' : r.error);
    });
  }
});

async function initDashboard(){
  const data = await get('/api/me.php');
  if (!data.ok){ alert(data.error||'Not logged in'); location.href = `${window.BASE_PATH}/login.php`; return; }
  const u = data.user;

  // totals + referral link
  const pt = $('#points-total'); if (pt) pt.textContent = u.total_points ?? 0;
  const ref = $('#ref-link'); if (ref) ref.value = `${location.origin}${window.BASE_PATH}/signup.php?ref=${u.username || u.referral_code}`;
  const copyBtn = $('#btn-copy-link'); if (copyBtn && ref){
    copyBtn.addEventListener('click', async ()=>{
      try { await navigator.clipboard.writeText(ref.value); copyBtn.textContent='Copied!'; setTimeout(()=>copyBtn.textContent='Copy',1000); } catch(){}
    });
  }

  // recent
  const tbody = $('#activity-table tbody');
  if (tbody){
    tbody.innerHTML = (data.recent_ledger||[]).map(r=>`
      <tr><td>${r.created_at}</td><td>${r.reason}</td><td>${r.points}</td><td>${r.note||''}</td></tr>
    `).join('');
  }

  // daily login
  const btnLogin = $('#btn-claim-login');
  const btnCap   = $('#btn-claim-captcha');
  const status   = $('#claim-status');
  if (data.flags?.did_login_today && btnLogin){ btnLogin.disabled=true; btnLogin.textContent='Login claimed'; }
  if (data.flags?.did_captcha_today && btnCap){ btnCap.disabled=true; btnCap.textContent='Captcha claimed'; }

  if (btnLogin){
    btnLogin.addEventListener('click', async ()=>{
      btnLogin.disabled=true;
      const r = await post('/api/claim_daily_login.php', {_csrf:window.CSRF});
      if (!r.ok){ status.textContent=r.error; btnLogin.disabled=false; return; }
      status.textContent = `+${r.points_added} for daily login`;
      const pt = $('#points-total'); pt.textContent = (+pt.textContent||0) + r.points_added;
      btnLogin.textContent='Login claimed';
    });
  }
  if (btnCap){
    btnCap.addEventListener('click', async ()=>{
      btnCap.disabled=true;
      const r = await post('/api/claim_captcha.php', {_csrf:window.CSRF});
      if (!r.ok){ status.textContent=r.error; btnCap.disabled=false; return; }
      status.textContent = `+${r.points_added} for captcha`;
      const pt = $('#points-total'); pt.textContent = (+pt.textContent||0) + r.points_added;
      btnCap.textContent='Captcha claimed';
    });
  }
}

async function loadUsers(which){
  const table = $(`#users-${which} tbody`);
  if (!table) return;
  const r = await get(`/api/admin_users_list.php?status=${which}`);
  if (!r.ok){ alert(r.error||'error'); return; }
  table.innerHTML = r.users.map(u=>`
    <tr>
      <td>${u.email}</td>
      <td>${u.username||''}</td>
      <td>${u.total_points||0}</td>
      <td>
        ${which==='pending'
          ? `<button class="btn btn--primary" data-act="approve" data-id="${u.id}">Approve</button>`
          : which==='active'
            ? `<button class="btn btn--outline" data-act="suspend" data-id="${u.id}">Suspend</button>`
            : `<button class="btn btn--outline" data-act="activate" data-id="${u.id}">Activate</button>`}
      </td>
    </tr>
  `).join('');
  table.querySelectorAll('button').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id=b.dataset.id, act=b.dataset.act;
      let action = act;
      if (act==='activate') action='approve';
      const r = await post('/api/admin_user_update.php', { _csrf:window.CSRF, user_id:id, action });
      alert(r.ok ? r.message : r.error);
      location.reload();
    });
  });
}

async function loadRefs(which){
  const table = $(`#ref-${which} tbody`);
  if (!table) return;
  const r = await get(`/api/admin_referrals_list.php?status=${which}`);
  if (!r.ok){ alert(r.error||'error'); return; }
  table.innerHTML = r.referrals.map(x=>`
    <tr>
      <td>${x.created_at}</td>
      <td>${x.referrer_username||x.referrer_email}</td>
      <td>${x.referee_username||x.referee_email}</td>
      <td>${x.notes||''}</td>
      <td>
        ${which==='pending' ? `
          <input class="input" style="max-width:200px" placeholder="note" id="note-${x.id}">
          <button class="btn btn--primary" data-act="approve" data-id="${x.id}">Approve</button>
          <button class="btn btn--outline" data-act="reject" data-id="${x.id}">Reject</button>
        ` : ''}
      </td>
    </tr>
  `).join('');
  table.querySelectorAll('button').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id=b.dataset.id, act=b.dataset.act;
      const note = $(`#note-${id}`)?.value || '';
      const r = await post('/api/admin_referral_action.php', { _csrf:window.CSRF, referral_id:id, action:act, notes:note });
      alert(r.ok ? r.message : r.error);
      location.reload();
    });
  });
}
