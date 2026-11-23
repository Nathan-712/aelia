(function () {
  'use strict';
  const feed = document.getElementById('liveFeed');
  if (!feed) return;
  const body = document.body;
  const me = Number(body.dataset.uid || 0);
  const scopeAll = body.dataset.role === 'admin' || body.dataset.role === 'owner';
  const strip = document.getElementById('liveOnline');
  const countEl = document.getElementById('liveCount');
  const POLL_VISIBLE = 4000, POLL_HIDDEN = 15000, MAX_ITEMS = 60;

  let since = Number(feed.dataset.since || 0);
  let timer = null;

  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const ICON = { login:'🟢', logout:'🔴', expired:'⚪', login_failed:'⚠️' };
  const LABEL = { login:'Login', logout:'Logout', expired:'Offline / sesi berakhir', login_failed:'Login gagal' };
  function ago(ts) {
    const d = Math.max(0, Math.floor(Date.now()/1000) - ts);
    if (d < 5) return 'Baru saja';
    if (d < 60) return d + ' detik lalu';
    if (d < 3600) return Math.floor(d/60) + ' menit lalu';
    if (d < 86400) return Math.floor(d/3600) + ' jam lalu';
    return Math.floor(d/86400) + ' hari lalu';
  }
  function itemHtml(ev) {
    let meta = esc(ev.username) + ' • ' + esc(ev.device);
    if (scopeAll && ev.ip) meta += ' • ' + esc(ev.ip);
    return '<div class="history-item live-item" data-id="' + ev.id + '">' +
      '<div class="history-icon ' + esc(ev.type) + '">' + (ICON[ev.type] || '•') + '</div>' +
      '<div class="history-content"><div class="history-title">' + esc(LABEL[ev.type] || ev.type) + '</div>' +
      '<div class="history-meta">' + meta + '</div></div>' +
      '<div class="history-time" data-ts="' + ev.ts + '">' + ago(ev.ts) + '</div></div>';
  }
  function toast(msg, type) {
    const c = document.getElementById('toastContainer'); if (!c) return;
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = '<span style="font-size:18px">' + (type==='success'?'✓':type==='error'?'✕':'ℹ') + '</span><span style="flex:1">' + esc(msg) + '</span>';
    c.appendChild(el);
    setTimeout(() => { el.style.animation = 'slideInRight .3s ease reverse'; setTimeout(() => el.remove(), 280); }, 4200);
  }
  function renderOnline(list) {
    if (countEl) countEl.textContent = list.length + ' online';
    if (strip) {
      strip.innerHTML = list.length
        ? list.map(u => '<span class="online-chip"><span class="dot"></span>' + esc(u.username) + ' <span style="opacity:.6;font-weight:500">' + esc(u.role) + '</span></span>').join('')
        : '<span class="live-empty">Tidak ada user online</span>';
    }
    const ids = new Set(list.map(u => Number(u.user_id)));
    document.querySelectorAll('[data-presence]').forEach(el => {
      const on = ids.has(Number(el.dataset.presence));
      el.classList.toggle('on', on);
      el.innerHTML = '<span class="dot"></span>' + (on ? 'Online' : 'Offline');
    });
  }
  function prepend(events) {
    if (!events.length) return;
    const empty = feed.querySelector('.live-empty'); if (empty) empty.remove();
    events.slice().reverse().forEach(ev => {
      since = Math.max(since, ev.id);
      feed.insertAdjacentHTML('afterbegin', itemHtml(ev));
      if (scopeAll && ev.user_id !== me && ['login','logout','expired'].includes(ev.type)) {
        const msg = ev.username + ' ' + (LABEL[ev.type] || ev.type).toLowerCase() + ' • ' + ev.device;
        toast(msg, ev.type === 'login' ? 'success' : ev.type === 'logout' ? 'error' : 'info');
      }
    });
    feed.dataset.since = String(since);
    while (feed.children.length > MAX_ITEMS) feed.removeChild(feed.lastElementChild);
  }
  async function tick() {
    try {
      const r = await fetch('api/activity.php?since=' + since, { credentials:'same-origin', headers:{ 'Accept':'application/json' } });
      if (r.status === 401) { location.href = '?page=login'; return; }
      const j = await r.json();
      if (!j.ok) { if (j.error === 'upgrade') toast('Jalankan upgrade_realtime.php untuk mengaktifkan live history', 'error'); return; }
      prepend(j.events || []);
      renderOnline(j.online || []);
    } catch (e) { /* jaringan putus: coba lagi putaran berikutnya */ }
    schedule();
  }
  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(tick, document.hidden ? POLL_HIDDEN : POLL_VISIBLE);
  }
  document.addEventListener('visibilitychange', () => { if (!document.hidden) { clearTimeout(timer); tick(); } else schedule(); });
  setInterval(() => {
    feed.querySelectorAll('.history-time[data-ts]').forEach(el => { el.textContent = ago(Number(el.dataset.ts)); });
  }, 15000);

  renderOnlineInitial();
  function renderOnlineInitial() {
    const init = document.getElementById('liveOnlineInit');
    if (init) { try { renderOnline(JSON.parse(init.textContent)); } catch (e) {} init.remove(); }
  }
  schedule();
})();
