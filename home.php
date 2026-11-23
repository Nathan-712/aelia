<?php if (!defined('AERLIA')) exit;
$me = current_user();
$usage = vault_usage(uid());
$hc = db()->prepare('SELECT COUNT(*) c FROM history WHERE user_id=?'); $hc->execute([uid()]);
$histCount = (int)$hc->fetch()['c'];
$rc = db()->prepare('SELECT * FROM history WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 5');
$rc->execute([uid()]); $recent = $rc->fetchAll();

$features = [
    ['href'=>'?page=scripts',     'icon'=>'📦', 'title'=>'My Scripts',      'desc'=>'Vault pribadi kamu. Simpan, cari, edit, dan preview kode dengan aman.', 'cta'=>'Buka Vault'],
    ['href'=>'?page=aeliaplayer', 'icon'=>'🎬', 'title'=>'Aerlia Player',   'desc'=>'Putar YouTube mode no-cookie dengan fallback otomatis.',              'cta'=>'Putar Video'],
    ['href'=>'?page=history',     'icon'=>'📜', 'title'=>'History',         'desc'=>'Riwayat login, logout, dan aktivitas akun dengan jam WIB.',           'cta'=>'Lihat Aktivitas'],
];
if (is_admin()) $features[] = ['href'=>'?page=admin','icon'=>'👥','title'=>'User Management','desc'=>'Kelola akun user & admin. Owner dilindungi server.','cta'=>'Kelola Akun'];
$features[] = ['href'=>'?page=settings','icon'=>'⚙️','title'=>'Settings','desc'=>'Avatar, ubah password, kuota vault, backup & restore.','cta'=>'Buka Settings'];
$features[] = ['href'=>'?page=export', 'icon'=>'💾','title'=>'Export Vault','desc'=>'Download seluruh vault kamu sebagai file JSON.','cta'=>'Export Sekarang'];

$icons = ['create'=>'➕','edit'=>'✏️','delete'=>'🗑️','login'=>'🔐','logout'=>'🚪','user-add'=>'👤','user-delete'=>'🗑️','password-change'=>'🔑','role-change'=>'⬆️','avatar-change'=>'🖼️','import'=>'📥','export'=>'📤','test'=>'🧪'];
$labels = ['create'=>'Script dibuat','edit'=>'Script diedit','delete'=>'Script dihapus','login'=>'Login','logout'=>'Logout','user-add'=>'User ditambahkan','user-delete'=>'User dihapus','password-change'=>'Password diubah','role-change'=>'Role diubah','avatar-change'=>'Foto profil diubah','import'=>'Vault diimport','export'=>'Vault diexport','test'=>'Tes rekam history'];
?>
<style>
.home-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,rgba(0,212,255,.12),rgba(124,58,237,.12));border:1px solid var(--glass-border);border-radius:20px;padding:32px;margin-bottom:24px;display:flex;gap:20px;align-items:center;flex-wrap:wrap}
.home-hero-icon{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,var(--accent-1),var(--accent-3));display:flex;align-items:center;justify-content:center;font-size:32px;box-shadow:0 12px 32px rgba(0,212,255,.35);flex-shrink:0}
.home-greet{font-size:13px;color:var(--text-secondary);margin-bottom:6px}
.home-title{font-size:26px;font-weight:700;margin-bottom:6px}
.home-sub{font-size:14px;color:var(--text-secondary);max-width:640px;line-height:1.6}
.home-section-title{font-size:16px;font-weight:700;margin:26px 0 14px;display:flex;align-items:center;gap:8px}
.home-section-title a{margin-left:auto;font-size:13px;font-weight:600;color:var(--accent-1);text-decoration:none}
.feature-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.feature-card{position:relative;display:flex;flex-direction:column;gap:10px;background:var(--glass);border:1px solid var(--glass-border);border-radius:16px;padding:20px;text-decoration:none;color:inherit;transition:.25s;overflow:hidden}
.feature-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent-1),var(--accent-3));opacity:0;transition:.25s}
.feature-card:hover{transform:translateY(-4px);border-color:rgba(0,212,255,.4);box-shadow:0 12px 32px rgba(0,0,0,.3)}
.feature-card:hover::before{opacity:1}
.feature-icon{width:44px;height:44px;border-radius:12px;background:rgba(0,212,255,.12);display:flex;align-items:center;justify-content:center;font-size:22px}
.feature-title{font-size:16px;font-weight:600}
.feature-desc{font-size:13px;color:var(--text-secondary);line-height:1.5;flex:1}
.feature-cta{font-size:13px;font-weight:600;color:var(--accent-1);display:flex;align-items:center;gap:6px}
</style>

<div class="home-hero">
  <div class="home-hero-icon">🛡️</div>
  <div style="flex:1;min-width:240px">
    <div class="home-greet">Selamat datang kembali,</div>
    <div class="home-title"><?= e($me['username']) ?>
      <span class="role-badge <?= e($me['role']) ?>" style="vertical-align:middle;margin-left:8px"><?= e($me['role']) ?></span>
    </div>
    <div class="home-sub">Pilih fitur yang mau kamu pakai di bawah. Vault kamu terisolasi per akun — hanya kamu yang bisa melihat isinya.</div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Script di Vault</div><div class="stat-value"><?= $usage['count'] ?></div></div>
  <div class="stat-card"><div class="stat-label">Storage</div><div class="stat-value"><?= e(fmt_bytes($usage['bytes'])) ?></div></div>
  <div class="stat-card"><div class="stat-label">Aktivitas</div><div class="stat-value"><?= $histCount ?></div></div>
  <div class="stat-card"><div class="stat-label">Login Terakhir</div><div class="stat-value" style="font-size:16px"><?= e(time_ago($me['last_login_at'])) ?></div></div>
</div>

<div class="home-section-title">🧭 Pilih Fitur</div>
<div class="feature-grid">
  <?php foreach ($features as $f): ?>
    <a class="feature-card" href="<?= e($f['href']) ?>">
      <div class="feature-icon"><?= $f['icon'] ?></div>
      <div class="feature-title"><?= e($f['title']) ?></div>
      <div class="feature-desc"><?= e($f['desc']) ?></div>
      <div class="feature-cta"><?= e($f['cta']) ?> <span>→</span></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="home-section-title">🕒 Aktivitas Terbaru <a href="?page=history">Lihat semua →</a></div>
<?php if (!$recent): ?>
  <div class="empty-state" style="padding:40px 20px"><div class="empty-icon">📜</div>
    <div class="empty-title">Belum ada aktivitas</div><p>Mulai dengan membuka Vault dan menambah script pertama.</p></div>
<?php else: ?>
<div class="history-list">
  <?php foreach ($recent as $h): ?>
    <div class="history-item">
      <div class="history-icon <?= e($h['action']) ?>"><?= $icons[$h['action']] ?? '•' ?></div>
      <div class="history-content">
        <div class="history-title"><?= e($labels[$h['action']] ?? $h['action']) ?></div>
        <div class="history-meta"><?= e($h['detail']) ?></div>
      </div>
      <div class="history-time" style="text-align:right">
        <div style="font-weight:600;color:var(--text-secondary)"><?= e(fmt_datetime($h['created_at'])) ?></div>
        <div style="opacity:.7;font-size:11px;margin-top:2px"><?= e(time_ago($h['created_at'])) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>