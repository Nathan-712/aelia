<?php if (!defined('AERLIA')) exit;
$me = current_user(); $usage = vault_usage(uid());
$maxB = (int)cfg('max_code_bytes'); $maxC = (int)cfg('max_scripts');
$pctB = min(100, round($usage['bytes']/max(1,$maxB)*100));
$pctC = min(100, round($usage['count']/max(1,$maxC)*100));
$curFav = site_favicon(); $curLogo = site_logo();
$hasCustomFav = (bool)site_setting('favicon'); $hasCustomLogo = (bool)$curLogo;
$mySessions = sess_list_user(uid()); $mySid = sess_sid();
?>
<style>
.brand-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-bottom:18px}
.brand-box{background:rgba(255,255,255,.02);border:1px solid var(--glass-border);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:12px}
.brand-label{font-size:12px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.6px}
.brand-tabmock{display:flex;align-items:center;gap:8px;background:#171b23;border:1px solid var(--glass-border);border-bottom:none;border-radius:10px 10px 0 0;padding:9px 12px;font-size:12px;color:var(--text-secondary);max-width:260px}
.brand-tabmock img{width:16px;height:16px;flex-shrink:0;border-radius:3px}
.brand-tabmock span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.brand-tabbody{height:26px;background:#0d1117;border:1px solid var(--glass-border);border-radius:0 0 10px 10px;max-width:260px}
.brand-logopreview{width:76px;height:76px;border-radius:18px;background:linear-gradient(135deg,var(--accent-1),var(--accent-3));display:flex;align-items:center;justify-content:center;font-size:34px;overflow:hidden;box-shadow:0 10px 26px rgba(0,212,255,.3)}
.brand-logopreview img{width:100%;height:100%;object-fit:cover}
.brand-hint{font-size:11px;color:var(--text-muted);line-height:1.5}
.totp-code{font-family:'JetBrains Mono',monospace;font-size:30px;font-weight:700;letter-spacing:8px;color:var(--accent-1);text-align:center;background:rgba(0,212,255,.06);border:1px dashed rgba(0,212,255,.3);border-radius:12px;padding:10px}
.sess-table{width:100%;border-collapse:collapse;font-size:13px}
.sess-table th,.sess-table td{padding:8px 10px;border-bottom:1px solid var(--glass-border);text-align:left}
.sess-table th{font-size:11px;text-transform:uppercase;color:var(--text-muted)}
</style>

<?php if (is_admin()): ?>
<div class="settings-card">
  <div class="settings-card-title"><span>🎨</span><span>Brand & Logo Website</span></div>
  <div class="settings-card-desc">Upload logo untuk favicon (ikon tab) dan logo situs. Tersimpan di database, berlaku untuk semua pengunjung.</div>
  <div class="brand-grid">
    <div class="brand-box">
      <div class="brand-label">Favicon · ikon tab</div>
      <div><div class="brand-tabmock"><img src="<?= e($curFav) ?>" alt=""><span><?= e(site_name()) ?></span></div><div class="brand-tabbody"></div></div>
      <form method="post" action="?page=settings" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload_favicon">
        <input class="form-input" type="file" name="favicon" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/png,image/jpeg,image/webp,image/gif,image/svg+xml" required style="margin-bottom:8px">
        <button class="btn btn-primary" type="submit">🖼️ Upload Favicon</button>
      </form>
      <?php if ($hasCustomFav): ?><form method="post" data-confirm="Kembalikan favicon ke bawaan?"><?= csrf_field() ?><input type="hidden" name="action" value="remove_favicon"><button class="btn btn-danger" type="submit">↩️ Pakai Bawaan</button></form><?php endif; ?>
      <div class="brand-hint">PNG/JPG/WebP/GIF/SVG. Di-crop 64×64. Hard-refresh tab setelah upload.</div>
    </div>
    <div class="brand-box">
      <div class="brand-label">Logo situs · sidebar & login</div>
      <div class="brand-logopreview"><?php if ($curLogo): ?><img src="<?= e($curLogo) ?>" alt=""><?php else: ?>🛡️<?php endif; ?></div>
      <form method="post" action="?page=settings" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload_site_logo">
        <input class="form-input" type="file" name="site_logo" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/png,image/jpeg,image/webp,image/gif,image/svg+xml" required style="margin-bottom:8px">
        <button class="btn btn-primary" type="submit">🛡️ Upload Logo</button>
      </form>
      <?php if ($hasCustomLogo): ?><form method="post" data-confirm="Kembalikan logo ke bawaan?"><?= csrf_field() ?><input type="hidden" name="action" value="remove_site_logo"><button class="btn btn-danger" type="submit">↩️ Pakai Bawaan</button></form><?php endif; ?>
      <div class="brand-hint">Di-fit 128×128 transparan. Logo lebar tetap utuh.</div>
    </div>
  </div>
  <form method="post" action="?page=settings"><?= csrf_field() ?><input type="hidden" name="action" value="save_site_identity">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Nama situs</label><input class="form-input" name="site_name" maxlength="60" required value="<?= e(site_name()) ?>"></div>
      <div class="form-group" style="display:flex;align-items:flex-end"><button class="btn btn-ghost" type="submit">💾 Simpan Nama</button></div>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="settings-card">
  <div class="settings-card-title"><span>🔐</span><span>Keamanan Login & Sesi</span></div>
  <div class="settings-card-desc">
    Semua login (user, admin, owner) membutuhkan <strong>kode TOTP bersama</strong> 6 digit yang berganti tiap 30 detik.
    <?php if (is_admin()): ?>Kode live tampil di bawah dan di panel User Management; secret base32 ada di panel tersebut.<?php else: ?>Kode hanya bisa diberikan oleh admin/owner yang sedang login — hubungi mereka saat butuh.<?php endif; ?>
  </div>
  <?php if (is_admin()): ?>
  <div data-global-totp style="max-width:300px;margin-bottom:14px;background:rgba(255,255,255,.02);border:1px solid var(--glass-border);border-radius:12px;padding:16px">
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px">KODE LIVE BERSAMA</div>
    <div class="totp-code">------</div>
    <div data-totp-left style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:6px">memuat…</div>
  </div>
  <script>
  (function(){
    const box = document.querySelector('[data-global-totp]'); if (!box) return;
    const codeEl = box.querySelector('.totp-code'), leftEl = box.querySelector('[data-totp-left]');
    let left = 0, code = '';
    function show(){ codeEl.textContent = code ? code.slice(0,3)+' '+code.slice(3) : '------'; leftEl.textContent = 'berganti dalam '+Math.max(0,left)+' detik'; }
    function load(){ fetch('api/totp_code.php',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{ if(j.ok){ code=j.code; left=j.expires_in; show(); } }).catch(()=>{}); }
    setInterval(()=>{ left--; if(left<=0) load(); else show(); }, 1000);
    load();
  })();
  </script>
  <?php endif; ?>
  <div style="font-size:13px;font-weight:700;margin-bottom:8px">Perangkat aktif kamu</div>
  <div style="overflow-x:auto">
  <table class="sess-table">
    <thead><tr><th>Device</th><th>IP</th><th>Aktif</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($mySessions as $s): $own = $s['sid_hash'] === $mySid; ?>
      <tr<?= $own ? ' style="background:rgba(16,185,129,.06)"' : '' ?>>
        <td><?= e($s['device']) ?></td><td><code><?= e($s['ip']) ?></code></td><td><?= e(time_ago($s['last_seen'])) ?></td>
        <td style="text-align:right">
          <?php if (!$own): ?>
          <form method="post" style="display:inline" data-confirm="Kick perangkat ini?"><?= csrf_field() ?>
            <input type="hidden" name="action" value="kick_session"><input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="back" value="?page=settings">
            <button class="icon-btn danger" type="submit">🔌</button></form>
          <?php else: ?><span style="color:var(--success);font-size:11px;font-weight:700">● ini</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="settings-card">
  <div class="settings-card-title"><span>🔒</span><span>Private Vault</span></div>
  <div class="settings-card-desc">Script disimpan di MySQL dengan <code>user_id</code>. Query selalu difilter milik kamu.</div>
  <div class="vault-info-grid">
    <div><div class="stat-label">Pemilik</div><div style="font-size:18px;font-weight:700"><?= e($me['username']) ?></div></div>
    <div><div class="stat-label">Script</div><div style="font-size:18px;font-weight:700;color:var(--accent-1)"><?= $usage['count'] ?> / <?= $maxC ?></div></div>
    <div><div class="stat-label">Storage</div><div style="font-size:18px;font-weight:700;color:var(--accent-1)"><?= e(fmt_bytes($usage['bytes'])) ?> / <?= e(fmt_bytes($maxB)) ?></div></div>
  </div>
  <div class="quota-bar"><div class="quota-fill" style="width:<?= $pctC ?>%"></div></div>
  <div class="quota-bar" style="margin-top:8px"><div class="quota-fill" style="width:<?= $pctB ?>%"></div></div>
</div>

<div class="settings-card">
  <div class="settings-card-title"><span>🖼️</span><span>Profile Picture</span></div>
  <div class="settings-card-desc">JPG/PNG/GIF/WebP maks 2 MB → crop & resize 128×128 → data URL di database.</div>
  <div class="avatar-upload-area">
    <div class="avatar-preview <?= e($me['role']) ?> <?= $me['avatar']?'has-image':'' ?>" id="avatarPreview">
      <?php if ($me['avatar']): ?><img src="<?= e($me['avatar']) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($me['username'],0,1))) ?><?php endif; ?></div>
    <div class="avatar-upload-controls">
      <form method="post" action="?page=settings" enctype="multipart/form-data" id="avatarForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload_avatar">
        <div class="avatar-dropzone" id="avatarDrop">
          <div class="avatar-dropzone-icon">📤</div>
          <div class="avatar-dropzone-text"><strong>Klik untuk pilih</strong> atau drag & drop</div>
          <div class="avatar-dropzone-hint">Gambar di-resize otomatis</div></div>
        <input type="file" name="avatar" id="avatarFile" accept="image/*" hidden>
        <div class="avatar-actions"><button class="btn btn-primary" type="submit">📷 Upload / Ganti Foto</button></div>
      </form>
      <?php if ($me['avatar']): ?>
      <form method="post" action="?page=settings" data-confirm="Hapus foto profil?" style="margin-top:8px">
        <?= csrf_field() ?><input type="hidden" name="action" value="remove_avatar">
        <button class="btn btn-danger" type="submit">🗑️ Hapus Foto</button></form>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="settings-card">
  <div class="settings-card-title"><span>👤</span><span>Informasi Akun</span></div>
  <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:center">
    <?= avatar_html($me['avatar'], $me['username'], $me['role'], 'avatar-md') ?>
    <div><div style="font-size:18px;font-weight:700;margin-bottom:4px"><?= e($me['username']) ?></div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <span class="role-badge <?= e($me['role']) ?>"><?= e($me['role']) ?></span>
        <span style="font-size:12px;color:var(--text-muted)">Bergabung <?= e(time_ago($me['created_at'])) ?></span>
        <span style="font-size:12px;color:var(--text-muted)">Login terakhir <?= e(time_ago($me['last_login_at'])) ?></span>
      </div></div>
  </div>
</div>

<div class="settings-card">
  <div class="settings-card-title"><span>🔑</span><span>Ubah Password</span></div>
  <div class="settings-card-desc">Berlaku di semua device. Tidak membutuhkan TOTP.</div>
  <form method="post" action="?page=settings"><?= csrf_field() ?><input type="hidden" name="action" value="change_password">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Password Lama</label>
        <div class="input-wrapper"><input class="form-input" type="password" name="old_password" id="op" required style="padding-right:40px">
          <button class="password-toggle" type="button" data-target="op">👁️</button></div></div>
      <div class="form-group"><label class="form-label">Password Baru</label>
        <div class="input-wrapper"><input class="form-input" type="password" name="new_password" id="np" required minlength="6" style="padding-right:40px">
          <button class="password-toggle" type="button" data-target="np">👁️</button></div></div>
    </div>
    <div class="form-group"><label class="form-label">Konfirmasi Password Baru</label>
      <div class="input-wrapper"><input class="form-input" type="password" name="confirm_password" id="cp" required minlength="6" style="padding-right:40px">
        <button class="password-toggle" type="button" data-target="cp">👁️</button></div></div>
    <button class="btn btn-primary" type="submit">Simpan Password</button>
  </form>
</div>

<div class="settings-card">
  <div class="settings-card-title"><span>💾</span><span>Backup & Restore</span></div>
  <div class="settings-card-desc">Export vault ke JSON, atau import backup. Import hanya masuk ke vault akun kamu.</div>
  <div class="avatar-actions"><a class="btn btn-ghost" href="?page=export">📤 Export Vault JSON</a></div>
  <form method="post" action="?page=settings" enctype="multipart/form-data" style="margin-top:14px">
    <?= csrf_field() ?><input type="hidden" name="action" value="import_vault">
    <div class="form-group"><label class="form-label">File backup (.json)</label>
      <input class="form-input" type="file" name="vault" accept=".json,application/json" required></div>
    <button class="btn btn-primary" type="submit">📥 Import ke Vault Saya</button>
  </form>
</div>