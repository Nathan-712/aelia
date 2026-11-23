<?php
/* =========================================================
   AERLIA CSP PROTECTION — index.php (v2.3 FINAL)
   Front controller: routing, head+favicon, layout, render page.
   Login role-based ditangani actions.php; di sini hanya tampilan.
   ========================================================= */
define('AERLIA', true);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/actions.php';
if (cfg('debug')) { @ini_set('display_errors','1'); error_reporting(E_ALL); }

/* ---------- Routing ---------- */
$rawPage = $_GET['page'] ?? '';
if ($rawPage === 'youtube') redirect('?page=aeliaplayer');
$user = current_user();
$page = $rawPage !== '' ? $rawPage : ($user ? 'home' : 'login');
$allow = ['login','home','scripts','aeliaplayer','history','admin','settings','export'];
if (!in_array($page, $allow, true)) $page = $user ? 'home' : 'login';
if (!$user && $page !== 'login') redirect('?page=login');
if ($user && $page === 'login' && !isset($_GET['step'])) redirect('?page=home');
if ($page === 'admin') require_admin();

/* ---------- Export vault (JSON, milik sendiri) ---------- */
if ($page === 'export') {
    require_login();
    $st = db()->prepare('SELECT title,description,language,tags,code,created_at,updated_at FROM scripts WHERE user_id=? ORDER BY updated_at DESC');
    $st->execute([uid()]); $scripts = $st->fetchAll();
    $hs = db()->prepare('SELECT action,detail,created_at FROM history WHERE user_id=? ORDER BY created_at DESC LIMIT 200');
    $hs->execute([uid()]);
    $payload = ['app'=>site_name(),'version'=>'php-2.3','vaultOwner'=>$user['username'],'exportedAt'=>date('c'),'scripts'=>$scripts,'history'=>$hs->fetchAll()];
    add_history('export', count($scripts).' script diexport');
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="aerlia-vault-'.e($user['username']).'-'.time().'.json"');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- Header keamanan + data brand ---------- */
send_app_headers();
$flashes = take_flashes();
$siteName = site_name(); $siteLogo = site_logo(); $favMime = favicon_mime();
$titles = [
    'home'=>['Beranda','Pilih fitur yang mau kamu pakai'],
    'scripts'=>['My Scripts','😶‍🌫 Vault pribadi — hanya akun kamu yang bisa melihat'],
    'aeliaplayer'=>['🫣 AerlioPL','©YTindexer 2026'],
    'history'=>['History','Riwayat aktivitas akun kamu'],
    'admin'=>['👥 User Management','Kode TOTP bersama, sesi device, dan kelola akun'],
    'settings'=>['⚙️ Settings','Akun, avatar, brand, keamanan, dan kuota vault'],
];
[$pageTitle, $pageSub] = $titles[$page] ?? [$siteName, ''];
$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (!is_file($pageFile) && $page === 'aeliaplayer') $pageFile = __DIR__ . '/pages/youtube.php';
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($siteName) ?> — <?= e($pageTitle) ?></title>
<link rel="icon" type="<?= e($favMime) ?>" href="favicon.php?s=32">
<link rel="icon" type="<?= e($favMime) ?>" sizes="16x16" href="favicon.php?s=16">
<link rel="icon" type="<?= e($favMime) ?>" sizes="32x32" href="favicon.php?s=32">
<link rel="icon" type="<?= e($favMime) ?>" sizes="192x192" href="favicon.php?s=192">
<link rel="apple-touch-icon" sizes="180x180" href="favicon.php?s=180">
<link rel="manifest" href="manifest.php">
<meta name="theme-color" content="#050814">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
<link rel="stylesheet" href="assets/app.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
</head><body>
<div class="toast-container" id="toastContainer">
<?php foreach ($flashes as $f): ?>
  <div class="toast <?= e($f['type']) ?>">
    <span style="font-size:18px"><?= $f['type']==='success'?'✓':($f['type']==='error'?'✕':'ℹ') ?></span>
    <span style="flex:1"><?= e($f['msg']) ?></span>
    <?php if (!empty($f['link'])): ?><a class="toast-action" href="<?= e($f['link']['url']) ?>"><?= e($f['link']['label']) ?></a><?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<?php if (!$user): ?>
<!-- ================= HALAMAN LOGIN ================= -->
<div class="login-screen"><div class="login-card">
  <div class="login-brand">
    <div class="login-logo"><?php if ($siteLogo): ?><img src="<?= e($siteLogo) ?>" alt=""><?php else: ?>🛡️<?php endif; ?></div>
    <h1 class="login-title"><?= e($siteName) ?></h1>
    <p class="login-subtitle">A Website Provided For Selected Individuals<?= (isset($_GET['step']) && $_GET['step']==='2fa') ? ' • Verifikasi User' : '' ?></p>
  </div>
  <?php if (isset($_GET['loggedout'])): ?><div class="vault-note" style="margin-bottom:16px"><span>👋</span><span>Kamu berhasil logout.</span></div><?php endif; ?>
  <?php if (isset($_GET['kicked'])): ?><div class="login-error show">Sesi kamu dihentikan oleh admin/owner dari perangkat ini.</div><?php endif; ?>
  <?php if (!empty($_SESSION['schema_error'])): ?><div class="login-error show">Database belum siap: <?= e($_SESSION['schema_error']) ?></div><?php endif; ?>

  <?php if (isset($_GET['step']) && $_GET['step']==='2fa' && !empty($_SESSION['pending_user'])): ?>
    <div class="vault-note" style="margin-bottom:16px"><span>🔐</span><span>
      Akun <strong><?= e($_SESSION['pending_user']) ?></strong> (role user) membutuhkan <strong>kode TOTP bersama</strong> 6 digit yang berganti tiap 30 detik, atau kode darurat sekali pakai.<br>
      Kode hanya bisa dilihat <strong>admin/owner yang sedang login</strong> — minta ke mereka. <em>Admin/owner sendiri login cukup dengan password.</em></span></div>
    <form class="login-form" method="post" action="?page=login">
      <?= csrf_field() ?><input type="hidden" name="action" value="login_2fa">
      <div class="form-group"><label class="form-label">Kode TOTP (6 digit)</label>
        <div class="input-wrapper"><span class="input-icon">🔢</span>
          <input type="text" name="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" required autofocus
                 style="letter-spacing:8px;text-align:center;font-size:20px"></div></div>
      <button class="login-btn" type="submit">Verifikasi & Masuk</button>
    </form>
    <form method="post" action="?page=login" style="margin-top:10px">
      <?= csrf_field() ?><input type="hidden" name="action" value="login_2fa_cancel">
      <button class="btn btn-ghost" type="submit" style="width:100%;justify-content:center">← Batal</button>
    </form>
  <?php else: ?>
    <form class="login-form" method="post" action="?page=login">
      <?= csrf_field() ?><input type="hidden" name="action" value="login">
      <div class="form-group"><label class="form-label">Username</label>
        <div class="input-wrapper"><span class="input-icon">👤</span>
          <input type="text" name="username" placeholder="Masukkan username" autocomplete="username" required autofocus></div></div>
      <div class="form-group"><label class="form-label">Password</label>
        <div class="input-wrapper"><span class="input-icon">🔒</span>
          <input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required></div></div>
      <button class="login-btn" type="submit">Masuk</button>
    </form>
    <div class="login-footer" style="margin-top:12px;font-size:11px;color:var(--text-muted)">Secret Website CSP</div>
  <?php endif; ?>
    <div class="login-footer">Protected by <span><?= e($siteName) ?></span> • Aerlia PRYO v.3</div>
</div></div>

<?php else: ?>
<!-- ================= APLIKASI ================= -->
<div class="app">
  <aside class="sidebar" id="sidebar">
    <a href="?page=home" style="text-decoration:none;color:inherit">
      <div class="logo">
        <div class="logo-icon"><?php if ($siteLogo): ?><img src="<?= e($siteLogo) ?>" alt=""><?php else: ?>🛡️<?php endif; ?></div>
        <div class="logo-text"><?= e($siteName) ?><small>Protection</small></div>
      </div>
    </a>
    <div class="vault-badge">🔒 Vault: <span class="vault-owner"><?= e($user['username']) ?></span></div>
    <div class="nav-section"><div class="nav-label">Menu</div>
      <a class="nav-item <?= $page==='home'?'active':'' ?>" href="?page=home"><span>🗿</span><span>Beranda</span></a>
      <a class="nav-item <?= $page==='scripts'?'active':'' ?>" href="?page=scripts"><span>😶‍🌫</span><span>My Scripts</span></a>
      <a class="nav-item <?= $page==='aeliaplayer'?'active':'' ?>" href="?page=aeliaplayer"><span>🫣</span><span>AerlioPL</span></a>
      <a class="nav-item <?= $page==='history'?'active':'' ?>" href="?page=history"><span>📜</span><span>History</span></a>
    </div>
    <?php if (is_admin()): ?>
    <div class="nav-section"><div class="nav-label">Administration</div>
      <a class="nav-item admin-only <?= $page==='admin'?'active':'' ?>" href="?page=admin"><span>👥</span><span>User Management</span></a>
    </div>
    <?php endif; ?>
    <div class="nav-section"><div class="nav-label">Account</div>
      <a class="nav-item <?= $page==='settings'?'active':'' ?>" href="?page=settings"><span>⚙️</span><span>Settings</span></a>
      <a class="nav-item" href="?page=export"><span>💾</span><span>Export Vault</span></a>
    </div>
    <div class="sidebar-footer">
      <div class="user-card">
        <?= avatar_html($user['avatar'], $user['username'], $user['role']) ?>
        <div class="user-info">
          <div class="user-name"><span><?= e($user['username']) ?></span><span class="role-badge <?= e($user['role']) ?>"><?= e($user['role']) ?></span></div>
          <div class="user-role"><?= $user['role']==='owner'?'Owner • Full Control':($user['role']==='admin'?'Administrator':'Regular User') ?></div>
        </div>
      </div>
      <form method="post" action="?page=home" data-confirm="Yakin ingin logout?">
        <?= csrf_field() ?><input type="hidden" name="action" value="logout">
        <button class="logout-btn" type="submit"><span>🚪</span><span>Logout</span></button>
      </form>
    </div>
  </aside>
  <main class="main">
    <div class="header">
      <div style="display:flex;align-items:center;gap:16px">
        <button class="menu-toggle" id="menuToggle" type="button">☰</button>
        <div><h1 class="page-title"><?= e($pageTitle) ?></h1><p class="page-subtitle"><?= e($pageSub) ?></p></div>
      </div>
      <div class="header-actions" id="headerActions"></div>
    </div>
    <?php
    if (!is_file($pageFile)) {
        echo '<div class="settings-card"><div class="settings-card-title"><span>❌</span><span>File halaman tidak ditemukan</span></div>'
           . '<div class="settings-card-desc">File <code>pages/'.e($page).'.php</code> tidak ada di server. Upload ulang folder <code>pages/</code>.</div></div>';
    } else {
        try { require $pageFile; }
        catch (Throwable $e) {
            echo '<div class="settings-card"><div class="settings-card-title"><span>⚠️</span><span>Halaman gagal dirender</span></div>'
               . '<div class="settings-card-desc">Ini penyebab halaman blank. Perbaiki sesuai pesan di bawah.</div>';
            if (cfg('debug')) echo '<div class="view-code"><pre>'.e($e->getMessage())."\n\nFile: ".e(basename($e->getFile())).':'.$e->getLine().'</pre></div>';
            echo '<div class="vault-note" style="margin-top:14px"><span>🛠️</span><span>Buka History → klik "Perbaiki Tabel".</span></div></div>';
        }
    }
    ?>
  </main>
</div>
<?php endif; ?>
<script src="assets/app.js"></script>
</body></html>