(function () {
  'use strict';
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  /* Toast auto dismiss */
  $$('#toastContainer .toast').forEach(t => setTimeout(() => {
    t.style.animation = 'slideInRight .3s ease reverse';
    setTimeout(() => t.remove(), 280);
  }, 6000));

  /* Sidebar mobile */
  const sb = $('#sidebar'), mt = $('#menuToggle');
  if (mt && sb) mt.onclick = () => sb.classList.toggle('open');

  /* Modal: buka via tombol, tutup via Esc/overlay */
  $$('[data-open-modal]').forEach(b => b.onclick = () => { const m = $('#' + b.dataset.openModal); if (m) m.classList.add('is-open'); });
  $$('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('is-open'); });
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { $$('.modal-overlay.is-open').forEach(m => m.classList.remove('is-open')); if (sb) sb.classList.remove('open'); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { const s = $('#searchInput'); if (s) { e.preventDefault(); s.focus(); } }
  });

  /* Konfirmasi form berbahaya */
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
  }));

  /* Copy kode */
  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text);
    return new Promise((res, rej) => {
      const ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy') ? res() : rej(); } catch (err) { rej(err); }
      ta.remove();
    });
  }
  function flashBtn(btn, ok) { const o = btn.innerHTML; btn.innerHTML = ok ? '✅' : '❌'; setTimeout(() => btn.innerHTML = o, 1500); }
  $$('[data-copy-card]').forEach(b => b.onclick = () => {
    const ta = b.closest('.script-card').querySelector('.card-code');
    copyText(ta ? ta.value : '').then(() => flashBtn(b, true)).catch(() => flashBtn(b, false));
  });
  $$('[data-copy-target]').forEach(b => b.onclick = () => {
    const el = $(b.dataset.copyTarget);
    copyText(el ? el.textContent : '').then(() => flashBtn(b, true)).catch(() => flashBtn(b, false));
  });

  /* Search + sort script (client-side) */
  const grid = $('#scriptsGrid'), si = $('#searchInput'), ss = $('#sortSelect');
  function applyFilter() {
    if (!grid) return;
    const q = (si && si.value || '').toLowerCase().trim();
    const cards = $$('.script-card', grid);
    cards.forEach(c => {
      const hay = (c.dataset.title || '') + ' ' + (c.dataset.desc || '') + ' ' + (c.dataset.tags || '');
      c.style.display = (!q || hay.includes(q)) ? '' : 'none';
    });
    const mode = ss ? ss.value : 'newest';
    const visible = cards.filter(c => c.style.display !== 'none');
    visible.sort((a, b) => {
      if (mode === 'az') return (a.dataset.title || '').localeCompare(b.dataset.title || '');
      if (mode === 'za') return (b.dataset.title || '').localeCompare(a.dataset.title || '');
      const ka = mode === 'oldest' ? 'created' : 'updated';
      const kb = mode === 'oldest' ? 'created' : 'updated';
      const da = new Date(a.dataset[ka] || 0).getTime(), db = new Date(b.dataset[kb] || 0).getTime();
      return mode === 'oldest' ? da - db : db - da;
    });
    visible.forEach(c => grid.appendChild(c));
  }
  if (si) si.addEventListener('input', applyFilter);
  if (ss) ss.addEventListener('change', applyFilter);

  /* Syntax highlight */
  if (window.hljs) $$('pre code').forEach(el => { try { hljs.highlightElement(el); } catch (e) {} });

  /* Password toggle */
  $$('.password-toggle').forEach(b => b.onclick = () => {
    const t = $('#' + b.dataset.target);
    if (!t) return;
    t.type = t.type === 'password' ? 'text' : 'password';
    b.textContent = t.type === 'password' ? '👁️' : '🙈';
  });

  /* Avatar upload preview + drag drop */
  const af = $('#avatarFile'), ad = $('#avatarDrop'), ap = $('#avatarPreview');
  function previewFile(file) {
    if (!file || !ap) return;
    const r = new FileReader();
    r.onload = e => { ap.innerHTML = '<img src="' + e.target.result + '" alt="">'; ap.classList.add('has-image'); };
    r.readAsDataURL(file);
  }
  if (ad && af) {
    ad.onclick = () => af.click();
    af.onchange = () => previewFile(af.files[0]);
    ad.ondragover = e => { e.preventDefault(); ad.classList.add('dragover'); };
    ad.ondragleave = () => ad.classList.remove('dragover');
    ad.ondrop = e => {
      e.preventDefault(); ad.classList.remove('dragover');
      if (e.dataTransfer.files && e.dataTransfer.files[0]) { af.files = e.dataTransfer.files; previewFile(af.files[0]); }
    };
  }

  /* ============ YouTube No-Cookie Player ============ */
  if ($('#ytPlayBtn')) {
    let ytLink = '', ytId = '', ytPlayer = null, ytNoCookie = true;
    if (!window.__ytApiLoading) {
      window.__ytApiLoading = true;
      const t = document.createElement('script');
      t.src = 'https://www.youtube.com/iframe_api';
      document.head.appendChild(t);
    }
    window.onYouTubeIframeAPIReady = function () {};

    const el = id => document.getElementById(id);
    function convert() {
      const input = el('ytUrl').value.trim();
      const vs = el('ytVideoSection'), em = el('ytErrorMsg'), eo = el('ytErrorOverlay'), ld = el('ytLoading');
      vs.style.display = 'none'; em.style.display = 'none'; eo.style.display = 'none';
      const re = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
      const m = input.match(re);
      if (!m || m[2].length !== 11) { em.textContent = '❌ Link tidak valid! Pastikan itu link YouTube.'; em.style.display = 'block'; return; }
      ytId = m[2];
      ytNoCookie = !el('ytForceNormal').checked;
      const p = { enablejsapi: 1, origin: location.origin, playsinline: 1, rel: 0, modestbranding: 1 };
      if (el('ytAutoplay').checked) p.autoplay = 1;
      if (el('ytMute').checked) p.mute = 1;
      if (el('ytLoop').checked) { p.loop = 1; p.playlist = ytId; }
      const qs = Object.entries(p).map(([k, v]) => k + '=' + v).join('&');
      ytLink = 'https://www.' + (ytNoCookie ? 'youtube-nocookie.com' : 'youtube.com') + '/embed/' + ytId + '?' + qs;
      vs.style.display = 'block'; ld.style.display = 'block';
      if (ytPlayer) { try { ytPlayer.destroy(); } catch (e) {} ytPlayer = null; }
      el('ytPlayer').innerHTML = '';
      if (typeof YT === 'undefined' || !YT.Player) { setTimeout(convert, 1500); return; }
      ytPlayer = new YT.Player('ytPlayer', {
        videoId: ytId,
        host: ytNoCookie ? 'https://www.youtube-nocookie.com' : 'https://www.youtube.com',
        playerVars: p,
        events: { onReady: onReady, onError: onErr }
      });
    }
    function onReady() {
      el('ytLoading').style.display = 'none';
      const b = el('ytStatusBadge');
      if (ytNoCookie) { b.textContent = '🔒 No-Cookie Mode'; b.style.background = 'rgba(16,185,129,.15)'; b.style.color = '#10b981'; b.style.border = '1px solid rgba(16,185,129,.3)'; }
      else { b.textContent = '⚡ YouTube Biasa'; b.style.background = 'rgba(245,158,11,.15)'; b.style.color = '#f59e0b'; b.style.border = '1px solid rgba(245,158,11,.3)'; }
      b.style.display = 'block';
    }
    function onErr(ev) {
      el('ytLoading').style.display = 'none';
      const msgs = { 2: 'Parameter URL tidak valid.', 5: 'Error pada HTML5 player.', 100: 'Video tidak ditemukan / dihapus.', 101: 'Pemilik video memblokir embedding.', 150: 'Pemilik video memblokir embedding.', 153: 'Pemilik video memblokir pemutaran di situs ini.' };
      el('ytErrorCode').textContent = 'Error Code: ' + ev.data;
      let txt = msgs[ev.data] || 'Terjadi error saat memutar video.';
      if ([101, 150, 153].includes(ev.data) && ytNoCookie) txt = 'Video diblokir di mode no-cookie. Coba YouTube biasa.';
      el('ytErrorText').textContent = txt;
      el('ytErrorOverlay').style.display = 'flex';
    }
    el('ytPlayBtn').onclick = convert;
    el('ytUrl').onkeypress = e => { if (e.key === 'Enter') convert(); };
    el('ytFallbackBtn').onclick = () => { el('ytForceNormal').checked = true; convert(); };
    el('ytOpenBtn').onclick = () => { if (ytId) window.open('https://www.youtube.com/watch?v=' + ytId, '_blank'); };
    el('ytCopyBtn').onclick = function () {
      if (!ytLink) return;
      copyText(ytLink).then(() => { const o = this.innerHTML; this.innerHTML = '✅ Tersalin!'; setTimeout(() => this.innerHTML = o, 2000); });
    };
    el('ytResetBtn').onclick = () => {
      if (ytPlayer) { try { ytPlayer.destroy(); } catch (e) {} ytPlayer = null; }
      el('ytPlayer').innerHTML = '';
      el('ytVideoSection').style.display = 'none';
      el('ytErrorOverlay').style.display = 'none';
      el('ytLoading').style.display = 'none';
      el('ytUrl').value = ''; el('ytUrl').focus();
    };
  }
})();