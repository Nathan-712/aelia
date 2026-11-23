<?php if (!defined('AERLIA')) exit; ?>

<!-- ============================================================
     Portal Multimedia — Arsitektur Asli (MEGA Islamic Boarding School)
     Di-embed ke dalam Aerlia shell. 100% arsitektur dipertahankan.
     FIX: iframe loader + auto-fallback untuk error 153
     ============================================================ -->
<div id="megaPortalRoot" class="mega-portal-root">

<style>
/* === CSS PORTAL (scope: #megaPortalRoot) === */
#megaPortalRoot{
  --bg:#0b1120;--panel:#111827;--text:#e5e7eb;--accent:#3b82f6;
  --success:#10b981;--warn:#f59e0b;--err:#ef4444;--card:#1e293b;
  --border:#334155;--muted:#94a3b8;--ok:#10b981;--ban:#ef4444;
  color:var(--text);font-family:system-ui,sans-serif;
  padding:2rem 1.5rem 3rem;max-width:1200px;margin:0 auto;width:100%;
}
#megaPortalRoot *{box-sizing:border-box}
#megaPortalRoot header{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem}
#megaPortalRoot .brand{font-size:1.25rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
#megaPortalRoot .brand svg{width:32px;height:32px;color:var(--accent)}
#megaPortalRoot .user-bar{display:flex;align-items:center;gap:1rem;font-size:.9rem;flex-wrap:wrap}
#megaPortalRoot .quota-timer{display:flex;align-items:center;gap:.5rem;background:var(--bg);border:1px solid var(--border);padding:.4rem .8rem;border-radius:8px;font-family:ui-monospace,monospace;font-size:.85rem}
#megaPortalRoot .quota-timer .label{font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:1px}
#megaPortalRoot .quota-timer .digits{color:var(--ok);font-weight:700;letter-spacing:2px}
#megaPortalRoot .quota-timer.warn{border-color:var(--warn)}
#megaPortalRoot .quota-timer.warn .digits{color:var(--warn)}
#megaPortalRoot .quota-timer.danger{border-color:var(--accent);animation:mpPulse 1s infinite}
#megaPortalRoot .quota-timer.danger .digits{color:var(--accent)}
@keyframes mpPulse{50%{opacity:.5}}
#megaPortalRoot main{max-width:1200px;margin:0 auto;width:100%}
#megaPortalRoot .search-box{display:flex;gap:.5rem;margin-bottom:1.5rem}
#megaPortalRoot input[type=text],#megaPortalRoot input[type=password]{flex:1;background:var(--bg);border:1px solid var(--border);color:var(--text);padding:.75rem 1rem;border-radius:10px;font-size:.95rem;outline:none}
#megaPortalRoot input:focus{border-color:var(--accent)}
#megaPortalRoot button{background:var(--accent);color:#fff;border:none;padding:.75rem 1.5rem;border-radius:10px;font-weight:600;cursor:pointer;font-family:inherit}
#megaPortalRoot button:hover{opacity:.9}
#megaPortalRoot button:disabled{opacity:.5;cursor:not-allowed}
#megaPortalRoot button.sec{background:var(--border)}
#megaPortalRoot .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem}
#megaPortalRoot .card{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;cursor:pointer;transition:transform .2s,border-color .2s}
#megaPortalRoot .card:hover{transform:translateY(-3px);border-color:var(--accent)}
#megaPortalRoot .thumb{position:relative;aspect-ratio:16/9;background:#000}
#megaPortalRoot .thumb img{width:100%;height:100%;object-fit:cover}
#megaPortalRoot .duration{position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.85);color:#fff;font-size:.75rem;font-weight:600;padding:2px 6px;border-radius:4px}
#megaPortalRoot .info{padding:.75rem}
#megaPortalRoot .title{font-size:.9rem;font-weight:600;margin-bottom:.4rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
#megaPortalRoot .channel{font-size:.8rem;color:var(--muted);cursor:pointer}
#megaPortalRoot .channel:hover{color:var(--accent);text-decoration:underline}
#megaPortalRoot .meta{font-size:.7rem;color:var(--muted);margin-top:.2rem}
#megaPortalRoot .empty{text-align:center;padding:3rem;color:var(--muted);grid-column:1/-1}
#megaPortalRoot .iframe-section{background:var(--panel);border-radius:12px;padding:1.5rem;margin-top:2rem;border:1px solid var(--border)}
#megaPortalRoot .iframe-section h2{color:#60a5fa;font-size:1.3rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
#megaPortalRoot .controls{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:stretch}
#megaPortalRoot .controls input{flex:1;min-width:180px;background:#1e293b;border:1px dashed #374151;color:#94a3b8;font-family:monospace;font-size:13px;cursor:not-allowed}
#megaPortalRoot .controls input:disabled{opacity:.7}
#megaPortalRoot .frame-wrap{border:2px solid #374151;border-radius:8px;overflow:hidden;background:#fff;height:65vh;position:relative;margin-top:1rem}
#megaPortalRoot iframe{width:100%;height:100%;border:none}
#megaPortalRoot .loader{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.85);color:white;padding:12px 24px;border-radius:6px;display:none;font-size:0.9rem;z-index:10}
#megaPortalRoot .loader.show{display:block}
#megaPortalRoot .status{margin-top:10px;padding:12px;background:var(--panel);border-radius:6px;font-size:.85rem;line-height:1.5;border-left:4px solid var(--accent);transition:border-color .3s}
#megaPortalRoot .note{background:#1e293b;padding:12px;border-radius:6px;margin-top:12px;font-size:.8rem;line-height:1.6}
#megaPortalRoot .note code{background:#0f172a;padding:2px 6px;border-radius:4px;font-family:monospace;color:#93c5fd}
#megaPortalRoot .hidden{display:none!important}
#megaPortalRoot .spinner{width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:mpSp .8s linear infinite;display:inline-block}
@keyframes mpSp{to{transform:rotate(360deg)}}
#megaPortalRoot .auth-container{max-width:420px;margin:2rem auto;padding:2rem;background:var(--card);border:1px solid var(--border);border-radius:12px;text-align:center}
#megaPortalRoot .auth-container h1{font-size:1.5rem;margin-bottom:.5rem}
#megaPortalRoot .auth-container h2{font-size:1rem;color:var(--muted);margin-bottom:1.5rem}
#megaPortalRoot .auth-container input{width:100%;margin-bottom:.75rem}
#megaPortalRoot .auth-container button{width:100%}
#megaPortalRoot .err{color:var(--accent);font-size:.85rem;min-height:1.2rem;margin-top:.5rem}
#megaPortalRoot .banned-icon{font-size:4rem;margin-bottom:1rem}
#megaPortalRoot .channel-header{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
#megaPortalRoot .channel-header h2{font-size:1.1rem;display:flex;align-items:center;gap:.5rem}
#megaPortalRoot .warning-banner{background:rgba(245,158,11,.15);border:1px solid var(--warn);color:var(--warn);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem;display:flex;align-items:center;gap:.5rem}
#megaPortalRoot .warning-banner.danger{background:rgba(239,68,68,.15);border-color:var(--accent);color:var(--accent)}
#megaPortalRoot .expired-screen{max-width:500px;margin:2rem auto;padding:2.5rem;background:var(--card);border:1px solid var(--border);border-radius:12px;text-align:center}
#megaPortalRoot .expired-screen .icon{font-size:4rem;margin-bottom:1rem}
#megaPortalRoot .expired-screen h1{color:var(--accent);margin-bottom:.5rem}
#megaPortalRoot .expired-screen p{color:var(--muted);margin-bottom:1.5rem}
#megaPortalRoot .load-more-wrap{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;gap:1rem;padding:2rem}
#megaPortalRoot .load-more-wrap a{color:var(--accent);text-decoration:none;font-size:.85rem}
#megaPortalRoot .load-more-wrap a:hover{text-decoration:underline}
@media(max-width:768px){
  #megaPortalRoot .controls{flex-direction:column}
  #megaPortalRoot .controls input,#megaPortalRoot .controls button{width:100%}
}
</style>

<div id="loginView" class="auth-container">
  <h1>🔐 Login Portal</h1>
  <h2>Masuk untuk mengakses materi multimedia</h2>
  <div id="step1">
    <input type="text" id="lgUser" placeholder="Username" autocomplete="username">
    <input type="password" id="lgPass" placeholder="Password" autocomplete="current-password">
    <button id="btnStep1">Lanjut</button>
  </div>
  <div id="step2" class="hidden">
    <p style="margin-bottom:.5rem;color:var(--muted);font-size:.9rem">Masukkan kode 6-digit dari Admin:</p>
    <input type="text" id="lgCode" placeholder="123456" maxlength="6" style="font-size:1.2rem;text-align:center;letter-spacing:4px">
    <button id="btnStep2">Verifikasi & Masuk</button>
    <button class="sec" id="btnBack" style="margin-top:.5rem">← Kembali</button>
  </div>
  <div id="lgErr" class="err"></div>
</div>

<div id="bannedView" class="auth-container hidden">
  <div class="banned-icon">🚫</div>
  <h1 style="color:var(--ban)">Akses Ditolak</h1>
  <h2>anda telah di ban</h2>
  <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem">Akun Anda telah diblokir sementara oleh Administrator.</p>
  <button class="sec" onclick="location.reload()">Coba Lagi</button>
</div>

<div id="expiredView" class="expired-screen hidden">
  <div class="icon">⏰</div>
  <h1>Waktu Penggunaan Habis</h1>
  <p>Kuota akses Anda telah berakhir. Silakan hubungi admin untuk perpanjangan.</p>
  <button class="sec" onclick="mpLogout()">Keluar</button>
</div>

<header id="appHeader" class="hidden">
  <div class="brand">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
    <span>Portal Multimedia</span>
  </div>
  <div class="user-bar">
    <div id="quotaBox" class="quota-timer hidden">
      <span class="label">Sisa</span>
      <span class="digits" id="quotaDigits">--:--:--:--:--</span>
    </div>
    <span id="whoami">—</span>
    <button class="sec" id="btnLogout" style="padding:.5rem 1rem;font-size:.8rem">Keluar Portal</button>
  </div>
</header>

<main id="appMain" class="hidden">
  <div id="warningBanner" class="warning-banner hidden"></div>
  <div id="channelHeader" class="channel-header hidden">
    <div><h2>📺 <span id="channelName">Channel</span></h2></div>
    <button class="sec" id="btnBackToSearch" style="padding:.5rem 1rem;font-size:.85rem">← Kembali ke Pencarian</button>
  </div>

  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Cari video...">
    <button id="searchBtn">
      <span id="btnText">Cari</span>
      <span id="btnSpinner" class="spinner hidden"></span>
    </button>
  </div>
  <div id="grid" class="grid">
    <div class="empty">Masukkan kata kunci di atas untuk mencari video.</div>
  </div>

  <div class="iframe-section">
    <h2>🎬 Pemutar Video</h2>
    <div class="controls">
      <input type="text" id="videoUrlInput" placeholder="Klik video di atas untuk memuat..." disabled>
      <button onclick="loadIframeAction()">🚀 Load aman</button>
      <button class="sec" onclick="openInNewTab()" style="background:var(--success)">📑 Tab Baru</button>
    </div>
    <div class="frame-wrap">
      <div class="loader" id="frameLoader">⏳ Memuat...</div>
      <iframe 
        id="targetFrame" 
        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals allow-presentation"
        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
        referrerpolicy="strict-origin-when-cross-origin"
        title="Pemutar video YouTube">
      </iframe>
    </div>
    <div class="status" id="frameStatus">
      💡 Klik video di atas untuk memuat materi pembelajaran. Tombol "Tab Baru" untuk membuka di tab terpisah.
    </div>
    <div class="note">
      🛠️ <b>Cara Pakai:</b><br>
      • Cari video menggunakan search bar di atas<br>
      • Klik video yang ingin ditonton<br>
      • URL akan otomatis terisi (tidak bisa diedit untuk keamanan)<br>
      • Klik <code>🚀 Load aman</code> untuk memutar di iframe<br>
      • Klik <code>📑 Tab Baru</code> untuk membuka di tab terpisah<br>
      ⚠️ Jika error 153 (video diblokir), coba gunakan tombol "Tab Baru".
    </div>
  </div>
</main>

</div><!-- /megaPortalRoot -->

<script>
(function(){
  'use strict';
  var API_URL = 'https://yt-admin.yusant0304.workers.dev/?api_action=';
  var WORKER_BASE_URL = 'https://indexer.yusant0304.workers.dev/';

  var authToken = localStorage.getItem('yt_user_token') || '';
  var quotaExpiresAt = parseInt(localStorage.getItem('yt_quota_expires_at') || '0', 10);
  var quotaEnabled = localStorage.getItem('yt_quota_enabled') === 'true';
  var currentChannelName = '';
  var currentVideoId = '';
  var currentDummyUrl = '';
  var quotaTimerInterval = null;
  var fallbackAttempted = false;

  var root = document.getElementById('megaPortalRoot');
  var $ = function(id){ return root.querySelector('#' + id); };

  async function call(data) {
    if (authToken) data.token = authToken;
    try {
      var r = await fetch(API_URL + data.action, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
      var rawText = await r.text();
      if (!rawText) return { error: 'Respons kosong' };
      try {
        var res = JSON.parse(rawText);
        if (res.token) { authToken = res.token; localStorage.setItem('yt_user_token', authToken); }
        return res;
      } catch (e) { return { error: 'Server error: ' + rawText.substring(0, 50) }; }
    } catch (err) { return { error: 'Koneksi gagal: ' + err.message }; }
  }

  async function doStep1() {
    var u = $('lgUser').value.trim(), p = $('lgPass').value;
    $('lgErr').textContent = '';
    $('btnStep1').disabled = true;
    $('btnStep1').innerHTML = '<span class="spinner"></span>';
    var res = await call({action:'login_step1', username:u, password:p});
    $('btnStep1').disabled = false;
    $('btnStep1').textContent = 'Lanjut';
    if (res.error) {
      if (res.error === 'KUOTA_HABIS') return showExpired();
      $('lgErr').textContent = res.error;
      if (res.error.toLowerCase().indexOf('ban') !== -1) { $('loginView').classList.add('hidden'); $('bannedView').classList.remove('hidden'); }
      return;
    }
    if (typeof res.quota_enabled === 'boolean') { quotaEnabled = res.quota_enabled; localStorage.setItem('yt_quota_enabled', quotaEnabled); }
    if (typeof res.quota_expires_at === 'number') { quotaExpiresAt = res.quota_expires_at; localStorage.setItem('yt_quota_expires_at', quotaExpiresAt); }
    if (res.totp_required) {
      localStorage.setItem('yt_pending_token', res.pending_token);
      $('step1').classList.add('hidden');
      $('step2').classList.remove('hidden');
      $('lgCode').focus();
    } else {
      authToken = res.token;
      localStorage.setItem('yt_user_token', authToken);
      showDashboard(res);
    }
  }

  async function doStep2() {
    var code = $('lgCode').value.trim(), pendingToken = localStorage.getItem('yt_pending_token');
    $('lgErr').textContent = '';
    if (!pendingToken) { $('lgErr').textContent = 'Sesi tidak valid.'; return; }
    var res = await call({action:'login_step2', code:code, pending_token:pendingToken});
    if (res.error) {
      if (res.error === 'KUOTA_HABIS') return showExpired();
      $('lgErr').textContent = res.error; return;
    }
    if (typeof res.quota_enabled === 'boolean') { quotaEnabled = res.quota_enabled; localStorage.setItem('yt_quota_enabled', quotaEnabled); }
    if (typeof res.quota_expires_at === 'number') { quotaExpiresAt = res.quota_expires_at; localStorage.setItem('yt_quota_expires_at', quotaExpiresAt); }
    authToken = res.token;
    localStorage.setItem('yt_user_token', authToken);
    showDashboard(res);
  }

  function showDashboard(res) {
    $('loginView').classList.add('hidden');
    $('bannedView').classList.add('hidden');
    $('expiredView').classList.add('hidden');
    $('appHeader').classList.remove('hidden');
    $('appMain').classList.remove('hidden');
    $('whoami').textContent = res.username;
    if (quotaEnabled && quotaExpiresAt > 0) {
      $('quotaBox').classList.remove('hidden');
      startQuotaTimer();
    } else {
      $('quotaBox').classList.add('hidden');
    }
    setTimeout(function(){ root.scrollIntoView({ behavior:'smooth' }); }, 100);
  }

  function showExpired() {
    clearInterval(quotaTimerInterval);
    $('loginView').classList.add('hidden');
    $('bannedView').classList.add('hidden');
    $('appHeader').classList.add('hidden');
    $('appMain').classList.add('hidden');
    $('expiredView').classList.remove('hidden');
    localStorage.removeItem('yt_user_token');
    localStorage.removeItem('yt_quota_enabled');
    localStorage.removeItem('yt_quota_expires_at');
    localStorage.removeItem('yt_pending_token');
    authToken = ''; quotaEnabled = false; quotaExpiresAt = 0;
  }
  
  window.mpLogout = async function() {
    await call({action:'logout'});
    localStorage.removeItem('yt_user_token');
    localStorage.removeItem('yt_pending_token');
    localStorage.removeItem('yt_quota_enabled');
    localStorage.removeItem('yt_quota_expires_at');
    authToken = ''; quotaEnabled = false; quotaExpiresAt = 0;
    showExpired();
    $('loginView').classList.remove('hidden');
  };

  function fmtQuota(ms) {
    if (ms <= 0) return '00:00:00:00:00';
    var s = Math.floor(ms / 1000);
    var mo = Math.floor(s / (30*86400)), d = Math.floor((s % (30*86400)) / 86400);
    var h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60), sc = s % 60;
    return String(mo).padStart(2,'0') + ':' + String(d).padStart(2,'0') + ':' + String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(sc).padStart(2,'0');
  }

  function startQuotaTimer() {
    clearInterval(quotaTimerInterval);
    var tick = function() {
      var remaining = Math.max(0, quotaExpiresAt - Date.now());
      $('quotaDigits').textContent = fmtQuota(remaining);
      $('quotaBox').classList.remove('warn', 'danger');
      if (remaining <= 0) { showExpired(); return; }
      else if (remaining < 5 * 60 * 1000) { $('quotaBox').classList.add('danger'); showWarning('⚠️ Sisa waktu kurang dari 5 menit!', true); }
      else if (remaining < 60 * 60 * 1000) { $('quotaBox').classList.add('danger'); showWarning('⚠️ Sisa waktu kurang dari 1 jam.', true); }
      else if (remaining < 24 * 3600 * 1000) { $('quotaBox').classList.add('warn'); showWarning('⏰ Sisa waktu kurang dari 24 jam.', false); }
      else { hideWarning(); }
    };
    tick();
    quotaTimerInterval = setInterval(tick, 1000);
  }

  function showWarning(msg, danger) {
    var b = $('warningBanner');
    b.textContent = msg; b.classList.remove('hidden', 'danger');
    if (danger) b.classList.add('danger');
  }
  function hideWarning() { $('warningBanner').classList.add('hidden'); }

  function escapeHtml(t) {
    if (!t) return '';
    return String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;").replace(/`/g,"&#96;");
  }

  function parseYouTubeHtml(html) {
    var m = html.match(/var\s+ytInitialData\s*=\s*(\{.+?\});\s*<\/script>/s) || html.match(/ytInitialData"\s*,\s*(\{.+?\})\s*\)\s*;/s);
    if (!m) throw new Error("Gagal menemukan data.");
    var data; try { data = JSON.parse(m[1]); } catch (e) { throw new Error("JSON rusak."); }
    var results = [], seen = new Set();
    (function walk(obj) {
      if (!obj || typeof obj !== 'object') return;
      var renderer = obj.videoRenderer || obj.gridVideoRenderer || obj.compactVideoRenderer || (obj.richItemRenderer && obj.richItemRenderer.content && obj.richItemRenderer.content.videoRenderer);
      if (renderer && renderer.videoId && !seen.has(renderer.videoId)) {
        seen.add(renderer.videoId);
        var duration = '';
        if (renderer.lengthText) duration = renderer.lengthText.simpleText || (renderer.lengthText.runs && renderer.lengthText.runs[0].text) || '';
        var channel = (renderer.ownerText && renderer.ownerText.runs && renderer.ownerText.runs[0].text) || (renderer.longBylineText && renderer.longBylineText.runs && renderer.longBylineText.runs[0].text) || (renderer.shortBylineText && renderer.shortBylineText.runs && renderer.shortBylineText.runs[0].text) || 'Channel';
        var title = (renderer.title && renderer.title.runs && renderer.title.runs[0].text) || (renderer.title && renderer.title.simpleText) || 'Video';
        var views = (renderer.viewCountText && renderer.viewCountText.simpleText) || (renderer.shortViewCountText && renderer.shortViewCountText.simpleText) || '';
        results.push({ id: renderer.videoId, title:title, channel:channel, duration:duration, meta:views });
      }
      for (var k in obj) { if (obj[k] && typeof obj[k] === 'object') walk(obj[k]); }
    })(data);
    return results;
  }

  function createVideoCard(v) {
    return '<div class="card" data-video-id="' + v.id + '" data-title="' + escapeHtml(v.title) + '">'
      + '<div class="thumb"><img src="https://i.ytimg.com/vi/' + v.id + '/hqdefault.jpg" loading="lazy" referrerpolicy="no-referrer">'
      + (v.duration ? '<span class="duration">' + escapeHtml(v.duration) + '</span>' : '') + '</div>'
      + '<div class="info"><div class="title">' + escapeHtml(v.title) + '</div>'
      + '<div class="channel" data-channel-name="' + escapeHtml(v.channel) + '">' + escapeHtml(v.channel) + '</div>'
      + '<div class="meta">' + escapeHtml(v.meta) + '</div></div></div>';
  }

  function renderVideos(videos) {
    if (!videos.length) { $('grid').innerHTML = '<div class="empty">Tidak ada hasil ditemukan.</div>'; return; }
    $('grid').innerHTML = videos.map(createVideoCard).join('');
  }

  async function searchVideos() {
    var q = $('searchInput').value.trim();
    if (!q) return;
    $('channelHeader').classList.add('hidden');
    $('searchBtn').disabled = true;
    $('btnText').classList.add('hidden');
    $('btnSpinner').classList.remove('hidden');
    $('grid').innerHTML = '<div class="empty">Mencari video...</div>';
    try {
      var target = 'https://www.youtube.com/results?search_query=' + encodeURIComponent(q);
      var r = await fetch(WORKER_BASE_URL + '?url=' + encodeURIComponent(target));
      if (!r.ok) throw new Error('HTTP ' + r.status);
      var videos = parseYouTubeHtml(await r.text());
      if (!videos.length) $('grid').innerHTML = '<div class="empty">Tidak ada hasil.</div>';
      else {
        renderVideos(videos);
        $('grid').insertAdjacentHTML('beforeend', '<div class="load-more-wrap"><a href="' + target + '" target="_blank" rel="noopener">🔗 Buka di situs utama</a></div>');
      }
    } catch (e) { $('grid').innerHTML = '<div class="empty">Gagal memuat: ' + e.message + '</div>'; }
    finally { $('searchBtn').disabled = false; $('btnText').classList.remove('hidden'); $('btnSpinner').classList.add('hidden'); }
  }

  async function viewChannel(channelName) {
    currentChannelName = channelName;
    $('channelHeader').classList.remove('hidden');
    $('channelName').textContent = channelName;
    $('grid').innerHTML = '<div class="empty">Memuat video dari channel...</div>';
    window.scrollTo({ top: root.offsetTop, behavior:'smooth' });
    try {
      var query = '"' + channelName + '"';
      var target = 'https://www.youtube.com/results?search_query=' + encodeURIComponent(query);
      var r = await fetch(WORKER_BASE_URL + '?url=' + encodeURIComponent(target));
      if (!r.ok) throw new Error('HTTP ' + r.status);
      var videos = parseYouTubeHtml(await r.text());
      if (!videos.length) $('grid').innerHTML = '<div class="empty">Tidak ada video ditemukan untuk "' + escapeHtml(channelName) + '".</div>';
      else {
        renderVideos(videos);
        $('grid').insertAdjacentHTML('beforeend', '<div class="load-more-wrap"><p style="color:var(--muted);font-size:.8rem;margin-bottom:.5rem">⚠️ Hasil pencarian "' + escapeHtml(channelName) + '"</p><a href="https://www.youtube.com/results?search_query=' + encodeURIComponent(channelName) + '" target="_blank" rel="noopener">🔗 Buka di situs utama</a></div>');
      }
    } catch (e) {
      $('grid').innerHTML = '<div class="empty">Gagal: ' + e.message + '<br><button class="sec mp-retry" style="margin-top:1rem">🔄 Coba Lagi</button></div>';
    }
  }

  function backToSearch() {
    $('channelHeader').classList.add('hidden');
    $('grid').innerHTML = '<div class="empty">Masukkan kata kunci di atas untuk mencari video.</div>';
    $('searchInput').value = '';
    $('searchInput').focus();
  }

  function buildDummyUrl(videoId) { return 'https://yout-ube.com/watch?v=' + videoId; }

  function transformToNocookie(dummyUrl) {
    try {
      var u = new URL(dummyUrl);
      var id = u.searchParams.get('v');
      if (!id || !/^[a-zA-Z0-9_-]{11}$/.test(id)) return null;
      var host = 'https://www.youtube-nocookie.com';
      var params = 'autoplay=1&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&fs=1';
      return host + '/embed/' + id + '?' + params;
    } catch(e) { return null; }
  }
  
  function transformToNormal(dummyUrl) {
    try {
      var u = new URL(dummyUrl);
      var id = u.searchParams.get('v');
      if (!id || !/^[a-zA-Z0-9_-]{11}$/.test(id)) return null;
      var host = 'https://www.youtube.com';
      var params = 'autoplay=1&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&fs=1';
      return host + '/embed/' + id + '?' + params;
    } catch(e) { return null; }
  }

  function selectVideo(id, title) {
    if (!/^[a-zA-Z0-9_-]{11}$/.test(id)) { setFrameStatus('❌ ID materi tidak valid.', 'err'); return; }
    currentVideoId = id;
    currentDummyUrl = buildDummyUrl(id);
    fallbackAttempted = false;
    $('videoUrlInput').value = currentDummyUrl;
    setFrameStatus(
      '✅ Video dipilih: <code>' + escapeHtml(title) + '</code><br>' +
      'Klik <b>🚀 Load aman</b> untuk memutar atau <b>📑 Tab Baru</b> untuk membuka di tab terpisah.', 
      'info'
    );
    root.querySelector('.iframe-section').scrollIntoView({ behavior:'smooth', block:'center' });
  }

  window.loadIframeAction = function(useNormal) {
    if (!currentDummyUrl) { setFrameStatus('⚠️ Pilih video terlebih dahulu dengan mengklik card di atas.', 'warn'); return; }
    
    var embedUrl = useNormal ? transformToNormal(currentDummyUrl) : transformToNocookie(currentDummyUrl);
    if (!embedUrl) { setFrameStatus('❌ Gagal membangun URL materi.', 'err'); return; }
    
    var frame = $('targetFrame');
    var loader = $('frameLoader');
    
    delete frame.dataset.loaded;
    delete frame.dataset.error;
    if (frame.dataset.loadTimer) clearTimeout(parseInt(frame.dataset.loadTimer, 10));
    
    setFrameStatus('🌐 Memuat materi pembelajaran...', 'info');
    loader.classList.add('show');
    frame.src = embedUrl;
    
    var startTime = Date.now();
    var MAX_WAIT = 8000;
    
    function checkLoad() {
      var elapsed = Date.now() - startTime;
      
      if (frame.dataset.loaded === '1') {
        loader.classList.remove('show');
        setFrameStatus('✅ Materi berhasil dimuat.', 'info');
        return;
      }
      
      if (frame.dataset.error === '1') {
        loader.classList.remove('show');
        if (!useNormal && !fallbackAttempted) {
          fallbackAttempted = true;
          setFrameStatus('⚠️ Video diblokir di mode no-cookie. Mencoba YouTube biasa...', 'warn');
          setTimeout(function() { window.loadIframeAction(true); }, 1500);
        } else {
          setFrameStatus('❌ Video gagal dimuat. Gunakan tombol <b>📑 Tab Baru</b>.', 'err');
        }
        return;
      }
      
      if (elapsed >= MAX_WAIT) {
        loader.classList.remove('show');
        setFrameStatus('⚠️ Pemuatan lambat. Gunakan tombol <b>📑 Tab Baru</b>.', 'warn');
        return;
      }
      
      frame.dataset.loadTimer = String(setTimeout(checkLoad, 400));
    }
    
    frame.dataset.loadTimer = String(setTimeout(checkLoad, 400));
  };

  window.openInNewTab = function() {
    if (!currentDummyUrl) { setFrameStatus('⚠️ Pilih video terlebih dahulu.', 'warn'); return; }
    var embedUrl = transformToNocookie(currentDummyUrl);
    if (!embedUrl) { setFrameStatus('❌ Gagal membangun URL.', 'err'); return; }
    window.open(embedUrl, '_blank', 'noopener,noreferrer');
    setFrameStatus('📑 Membuka di tab baru...', 'info');
  };

  function setFrameStatus(msg, type) {
    type = type || 'info';
    var colors = { info:'var(--accent)', warn:'var(--warn)', err:'var(--err)' };
    var status = $('frameStatus');
    status.style.borderLeftColor = colors[type] || colors.info;
    status.innerHTML = msg;
  }

  (function() {
    var frame = $('targetFrame');
    if (!frame) return;
    
    frame.addEventListener('load', function() {
      if (!this.src || this.src === 'about:blank' || this.src.indexOf('youtube') === -1) return;
      this.dataset.loaded = '1';
      var loader = $('frameLoader');
      if (loader) loader.classList.remove('show');
      if (this.dataset.loadTimer) {
        clearTimeout(parseInt(this.dataset.loadTimer, 10));
        delete this.dataset.loadTimer;
      }
    });
    
    frame.addEventListener('error', function() {
      this.dataset.error = '1';
      var loader = $('frameLoader');
      if (loader) loader.classList.remove('show');
    });
  })();

  $('btnStep1').addEventListener('click', doStep1);
  $('btnStep2').addEventListener('click', doStep2);
  $('btnBack').addEventListener('click', function() { $('step1').classList.remove('hidden'); $('step2').classList.add('hidden'); $('lgErr').textContent = ''; });
  $('btnLogout').addEventListener('click', window.mpLogout);
  $('searchBtn').addEventListener('click', searchVideos);
  $('searchInput').addEventListener('keydown', function(e){ if (e.key === 'Enter') searchVideos(); });
  $('btnBackToSearch').addEventListener('click', backToSearch);

  $('grid').addEventListener('click', function(e) {
    var retry = e.target.closest('.mp-retry');
    if (retry && currentChannelName) { e.stopPropagation(); viewChannel(currentChannelName); return; }
    var channelEl = e.target.closest('.channel');
    if (channelEl) {
      e.stopPropagation();
      var name = channelEl.dataset.channelName;
      if (name) viewChannel(name);
      return;
    }
    var card = e.target.closest('.card');
    if (card) {
      var videoId = card.dataset.videoId;
      var title = card.dataset.title;
      if (videoId) selectVideo(videoId, title);
    }
  });

  (async function() {
    if (authToken) {
      var r = await call({action:'me'});
      if (r.error === 'KUOTA_HABIS') return showExpired();
      if (r.ok) {
        if (typeof r.quota_enabled === 'boolean') { quotaEnabled = r.quota_enabled; localStorage.setItem('yt_quota_enabled', quotaEnabled); }
        if (typeof r.quota_expires_at === 'number') { quotaExpiresAt = r.quota_expires_at; localStorage.setItem('yt_quota_expires_at', quotaExpiresAt); }
        showDashboard(r);
      } else {
        localStorage.removeItem('yt_user_token'); localStorage.removeItem('yt_quota_enabled'); localStorage.removeItem('yt_quota_expires_at'); localStorage.removeItem('yt_embed_key');
        authToken = '';
      }
    }
  })();
})();
</script>