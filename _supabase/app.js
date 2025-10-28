/* =================================
   ClickHub - UI + Supabase wiring
   ================================= */
const $  = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

function onReady(fn){ document.readyState==='loading' ? document.addEventListener('DOMContentLoaded', fn, {once:true}) : fn(); }

// ------ small utils ------
const genCode = (len=6) => {
  const chars='abcdefghijklmnopqrstuvwxyz0123456789';
  let s=''; for(let i=0;i<len;i++) s += chars[Math.floor(Math.random()*chars.length)];
  return s;
};
const todayUtcDate = () => new Date().toISOString().slice(0,10); // YYYY-MM-DD

// ------ Supabase shortcuts ------
const sbClient = () => window.sb; // global from supabase.js

// Reads the logged-in user (or null)
async function getSessionUser() {
  const { data: { user } } = await sbClient().auth.getUser();
  return user || null;
}

// Sum points for user client-side (MVP)
async function getTotalPoints(userId){
  const { data, error } = await sbClient()
    .from('points_ledger')
    .select('points')
    .eq('user_id', userId);
  if (error) return 0;
  return (data || []).reduce((sum, r) => sum + (r.points||0), 0);
}

// Has user done captcha today? (MVP checks points_ledger for reason)
async function hasCaptchaToday(userId){
  const start = todayUtcDate(); // 00:00 UTC
  const { data, error } = await sbClient()
    .from('points_ledger')
    .select('id, created_at, reason')
    .eq('user_id', userId)
    .eq('reason', 'captcha_daily')
    .gte('created_at', `${start}T00:00:00Z`);
  return !error && data && data.length > 0;
}

// Ensure profile row exists; create referral_code on first run
async function ensureProfile(user, extras={}) {
  const { data: existing } = await sbClient()
    .from('profiles')
    .select('id, username, referral_code, referred_by')
    .eq('id', user.id)
    .maybeSingle();

  // build upsert payload
  const payload = { id: user.id, ...extras };

  if (!existing?.referral_code) {
    payload.referral_code = genCode(6);
  }
  const { error } = await sbClient().from('profiles').upsert(payload, { onConflict: 'id' });
  if (error) console.warn('upsert profile error', error);
  const { data } = await sbClient().from('profiles').select('*').eq('id', user.id).single();
  return data;
}

// resolve a ?ref=CODE to a user_id
async function getReferrerIdByCode(code){
  if (!code) return null;
  const { data, error } = await sbClient()
    .from('profiles')
    .select('id')
    .eq('referral_code', code)
    .maybeSingle();
  return error ? null : data?.id || null;
}

// ------ page bootstraps ------
onReady(async () => {
  // footer year
  const y = $('#year'); if (y) y.textContent = new Date().getFullYear();

  const path = location.pathname;

  // SIGNUP: capture ?ref=
  const hiddenRef = $('#ref-code');
  if (hiddenRef){
    const ref = new URLSearchParams(location.search).get('ref');
    if (ref) hiddenRef.value = ref;
  }

  // Forms (placeholders replaced with Supabase)
  const signupForm = $('#signup-form');
  if (signupForm){
    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = $('#email').value.trim();
      const password = $('#password').value.trim();
      const username = $('#username').value.trim();
      const refCode  = $('#ref-code').value.trim();

      if (!email || !password || !username){
        alert('Please fill all fields'); return;
      }

      // 1) create auth user
      const { data, error } = await sbClient().auth.signUp({ email, password });
      if (error) { alert(error.message); return; }
      const user = data.user;

      // 2) resolve referrer
      let referred_by = null;
      if (refCode) referred_by = await getReferrerIdByCode(refCode);

      // 3) create profile (username, referral_code generated)
      await ensureProfile(user, { username, referred_by });

      // 4) if ref present, create pending referral record
      if (referred_by){
        await sbClient().from('referrals').insert({
          referrer_id: referred_by,
          referee_id: user.id,
          status: 'pending',
          first_click_at: new Date().toISOString(),
          first_click_ip: null
        });
      }

      alert('Account created. Check your email if confirmation is enabled.');
      location.href = 'dashboard.php';
    });
  }

  const loginForm = $('#login-form');
  if (loginForm){
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = $('#login-email').value.trim();
      const password = $('#login-password').value.trim();
      const { error } = await sbClient().auth.signInWithPassword({ email, password });
      if (error) { alert(error.message); return; }
      location.href = 'dashboard.php';
    });
  }

  const logoutBtn = $('#btn-logout');
  if (logoutBtn){
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await sbClient().auth.signOut();
      location.href = 'index.php';
    });
  }

  // DASHBOARD wiring
  if (path.endsWith('/dashboard.php') || path.endsWith('dashboard.php')){
    const user = await getSessionUser();
    if (!user){ location.href = 'login.php'; return; }

    // ensure profile exists and read it
    const profile = await ensureProfile(user);
    const pointsEl = $('#points-total');
    const refInput = $('#ref-link');

    // show referral link
    if (refInput){
      const origin = `${location.protocol}//${location.host}${location.pathname.replace(/\/[^/]*$/, '/')}`;
      const link = `${origin}signup.php?ref=${profile.referral_code || 'YOURCODE'}`;
      refInput.value = link;
    }

    // show points total
    if (pointsEl){
      const total = await getTotalPoints(user.id);
      pointsEl.textContent = total;
    }

    // Daily captcha state
    const already = await hasCaptchaToday(user.id);
    const claimBtn = $('#btn-claim');
    const claimStatus = $('#claim-status');
    if (already && claimBtn && claimStatus){
      claimBtn.disabled = true;
      claimBtn.textContent = 'Already claimed';
      claimStatus.textContent = '✓ Come back tomorrow.';
    }

    // Copy button
    const copyBtn = $('#btn-copy-link');
    if (copyBtn && refInput){
      copyBtn.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(refInput.value); copyBtn.textContent='Copied!'; }
        catch { refInput.select(); document.execCommand('copy'); copyBtn.textContent='Copied!'; }
        setTimeout(()=> copyBtn.textContent='Copy', 1200);
      });
    }

    // Claim button (MVP: direct insert to points_ledger)
    if (claimBtn && claimStatus){
      claimBtn.addEventListener('click', async () => {
        claimBtn.disabled = true;
        // insert +5 with reason 'captcha_daily' IF not done today
        const did = await hasCaptchaToday(user.id);
        if (did){
          claimStatus.textContent = 'Already claimed today.'; return;
        }
        const { error } = await sbClient().from('points_ledger').insert({
          user_id: user.id,
          points: 5,
          reason: 'captcha_daily',
          meta: { provider: 'turnstile', ts: new Date().toISOString() }
        });
        if (error){ claimStatus.textContent = 'Error adding points.'; return; }
        const total = await getTotalPoints(user.id);
        if (pointsEl) pointsEl.textContent = total;
        claimBtn.textContent = 'Verified';
        claimStatus.textContent = '✓ Claimed. Come back tomorrow.';
      });
    }
  }

  // ADMIN tabs (UI only)
  const tabs = $$('.tabs .tab');
  const panels = $$('.tabpanels .tabpanel');
  if (tabs.length && panels.length){
    tabs.forEach(tab=>{
      tab.addEventListener('click', ()=>{
        tabs.forEach(t=>t.classList.remove('tab--active'));
        tab.classList.add('tab--active');
        const target = tab.dataset.tab;
        panels.forEach(p=>p.classList.toggle('tabpanel--active', p.id === `tab-${target}`));
      });
    });
  }
});
