<?php if (!defined('AERLIA')) exit;
$rows = db()->query('SELECT u.*, (SELECT COUNT(*) FROM scripts s WHERE s.user_id=u.id) sc,
  (SELECT COALESCE(SUM(LENGTH(s.code)),0) FROM scripts s WHERE s.user_id=u.id) sb
  FROM users u ORDER BY FIELD(u.role,\'owner\',\'admin\',\'user\'), u.created_at ASC')->fetchAll();
$editUser = $resetUser = null;
if (!empty($_GET['edit_user'])) { $st = db()->prepare('SELECT * FROM users WHERE id=?'); $st->execute([(int)$_GET['edit_user']]); $editUser = $st->fetch(); if ($editUser && ($editUser['role']==='owner' || (int)$editUser['id']===uid())) $editUser = null; }
if (!empty($_GET['reset_user'])) { $st = db()->prepare('SELECT * FROM users WHERE id=?'); $st->execute([(int)$_GET['reset_user']]); $resetUser = $st->fetch(); if ($resetUser && ($resetUser['role']==='owner' || (int)$resetUser['id']===uid())) $resetUser = null; }
$sessions = sess_list_all();
$mySid = sess_sid();
$gSecret = global_totp_secret();
?>
<style>
.totp-card{background:rgba(255,255,255,.02);border:1px solid var(--glass-border);border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:10px}
.totp-code{font-family:'JetBrains Mono',monospace;font-size:34px;font-weight:700;letter-spacing:8px;color:var(--accent-1);text-align:center;background:rgba(0,212,255,.06);border:1px dashed rgba(0,212,255,.3);border-radius:12px;padding:12px}
.totp-left{font-size:12px;color:var(--text-muted);text-align:center}
.totp-secret{font-family:'JetBrains Mono',monospace;font-size:12px;word-break:break-all;background:rgba(0,0,0,.35);border:1px solid var(--glass-border);border-radius:8px;padding:10px;color:var(--warning)}
.sess-table{width:100%;border-collapse:collapse;font-size:13px}
.sess-table th,.sess-table td{padding:10px 12px;border-bottom:1px solid var(--glass-border);text-align:left;vertical-align:middle}
.sess-table th{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted)}
.sess-cur{background:rgba(16,185,129,.06)}
.mini-input{width:92px;padding:6px 8px;background:var(--glass);border:1px solid var(--glass-border);border-radius:8px;color:#fff;font-family:'JetBrains Mono',monospace;font-size:12px}
</style>

<div class="settings-card">
  <div class="settings-card-title"><span>🔢</span><span>Kode TOTP Bersama (hanya admin/owner)</span></div>
  <div class="settings-card-desc">Satu kode untuk SEMUA login (user, admin, owner), berganti tiap <strong>30 detik</strong>. Bagikan ke siapa pun yang mau login. Secret base32 di bawah bisa di-enroll ke Google Authenticator/Authy sebagai cadangan saat tidak ada admin yang sedang login.</div>
  <div class="totp-card" data-global-totp>
    <div class="totp-code">------</div>
    <div class="totp-left">memuat…</div>
    <details>
      <summary style="cursor:pointer;font-size:12px;color:var(--text-secondary)">👁️ Lihat secret base32 (320-bit)</summary>
      <div class="totp-secret" style="margin-top:8px"><?= e($gSecret) ?></div>
    </details>
    <form method="post" action="?page=admin" data-confirm="Rotasi secret TOTP bersama? Kode lama & authenticator yang enroll lama langsung tidak berlaku." style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?= csrf_field() ?><input type="hidden" name="action" value="rotate_global_totp">
      <input class="mini-input" name="admin_totp" placeholder="kode sekarang" inputmode="numeric" maxlength="6" required>
      <button class="btn btn-danger" type="submit" style="padding:8px 14px;font-size:12px">🔄 Rotasi Secret</button>
    </form>
  </div>
</div>

<?php if (!empty($_SESSION['emergency_plain'])): $ep = $_SESSION['emergency_plain']; unset($_SESSION['emergency_plain']); ?>
<div class="vault-note" style="margin:0 0 14px;border-color:rgba(245,158,11,.5);background:rgba(245,158,11,.08)">
  <span>⚠️</span><span><strong>Kode darurat BARU — tampil sekali ini saja:</strong><br>
  <code style="font-size:18px;letter-spacing:2px;color:var(--owner)"><?= e($ep) ?></code><br>
  Salin & simpan sekarang. Setelah halaman di-refresh kode tidak bisa dilihat lagi; yang tersimpan di database hanya hash bcrypt.</span></div>
<?php endif; ?>

<div class="settings-card">
  <div class="settings-card-title"><span>🚨</span><span>Kode Darurat (Emergency Bypass)</span></div>
  <div class="settings-card-desc">
    Jalan keluar saat tidak ada admin/owner yang sedang login (deadlock TOTP). Kode darurat <strong>menggantikan langkah TOTP saja</strong> — password tetap wajib benar.
    Plaintext tidak pernah disimpan (hanya hash bcrypt), muncul sekali saat dibuat, dan setiap pemakaian tercatat di History beserta IP-nya.
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
    <?php if (emergency_hash()): ?>
      <span style="color:var(--success);font-weight:700">● AKTIF</span>
      <span style="font-size:12px;color:var(--text-muted)">hash tersimpan • dibuat <?= e(time_ago(emergency_set_at())) ?> • plaintext tersamarkan permanen</span>
    <?php else: ?>
      <span style="color:var(--danger);font-weight:700">○ BELUM ADA</span>
      <span style="font-size:12px;color:var(--text-muted)">sangat disarankan membuat satu sebagai cadangan</span>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <form method="post" action="?page=admin" style="display:flex;gap:8px;align-items:center" data-confirm="Buat/rotasi kode darurat? Kode lama langsung tidak berlaku.">
      <?= csrf_field() ?><input type="hidden" name="action" value="generate_emergency">
      <input class="mini-input" name="admin_totp" placeholder="kode TOTP" inputmode="numeric" maxlength="6" required>
      <button class="btn btn-admin" type="submit"><?= emergency_hash() ? '🔄 Rotasi Kode Darurat' : '🚨 Buat Kode Darurat' ?></button>
    </form>
    <?php if (emergency_hash()): ?>
    <form method="post" action="?page=admin" style="display:flex;gap:8px;align-items:center" data-confirm="Hapus kode darurat? Login kembali murni pakai TOTP bersama.">
      <?= csrf_field() ?><input type="hidden" name="action" value="remove_emergency">
      <input class="mini-input" name="admin_totp" placeholder="kode TOTP" inputmode="numeric" maxlength="6" required>
      <button class="btn btn-danger" type="submit">🗑️ Hapus</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="settings-card">
  <div class="settings-card-title"><span>🖥️</span><span>Session Management (semua device)</span></div>
  <div class="settings-card-desc">Device aktif seluruh user. Kick = sesi langsung terputus di request berikutnya. Kick sesi user lain butuh kode TOTP bersama.</div>
  <div style="overflow-x:auto">
  <table class="sess-table">
    <thead><tr><th>User</th><th>Device</th><th>IP</th><th>Terakhir aktif</th><th>Status</th><th style="text-align:right">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($sessions as $s): $own = $s['sid_hash'] === $mySid; ?>
      <tr class="<?= $own ? 'sess-cur' : '' ?>">
        <td><strong><?= e($s['username']) ?></strong> <span class="role-badge <?= e($s['role']) ?>"><?= e($s['role']) ?></span></td>
        <td><?= e($s['device']) ?></td>
        <td><code><?= e($s['ip']) ?></code></td>
        <td><?= e(time_ago($s['last_seen'])) ?></td>
        <td><?= $own ? '<span style="color:var(--success);font-weight:700">● Sesi ini</span>' : '<span style="color:var(--success)">● Online</span>' ?></td>
        <td style="text-align:right">
          <?php if (!$own): ?>
          <form method="post" action="?page=admin" style="display:flex;gap:6px;justify-content:flex-end" data-confirm="Kick sesi <?= e($s['username']) ?> di <?= e($s['device']) ?>?">
            <?= csrf_field() ?><input type="hidden" name="action" value="kick_session">
            <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
            <input type="hidden" name="back" value="?page=admin">
            <?php if ((int)$s['user_id'] !== uid()): ?><input class="mini-input" name="admin_totp" placeholder="kode TOTP" inputmode="numeric" maxlength="6" required><?php endif; ?>
            <button class="icon-btn danger" type="submit" title="Kick">🔌</button>
          </form>
          <?php else: ?><span style="color:var(--text-muted);font-size:11px">—</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="toolbar"><div style="flex:1"></div>
  <a class="btn btn-admin" href="<?= e(page_url(['add_user'=>1],'admin')) ?>"><span>+</span><span>Tambah User</span></a></div>

<div class="users-table">
  <div class="users-table-header"><div>User</div><div>Role</div><div>Vault (privat)</div><div>Dibuat</div><div style="text-align:right">Aksi</div></div>
  <?php foreach ($rows as $u): $self = (int)$u['id']===uid(); ?>
    <div class="user-row <?= $self?'current':'' ?>">
      <div class="user-row-info">
        <?= avatar_html($u['avatar'], $u['username'], $u['role'], 'user-row-avatar') ?>
        <div class="user-row-name"><?= e($u['username']) ?><?php if ($self): ?><span class="you-tag">YOU</span><?php endif; ?>
          <small><?= $u['role']==='owner'?'Owner kebal proteksi server':($u['role']==='admin'?'Kelola akun + vault sendiri':'Vault pribadi saja') ?></small></div>
      </div>
      <div><span class="role-badge <?= e($u['role']) ?>"><?= e($u['role']) ?></span></div>
      <div class="vault-cell">🔒 <?= (int)$u['sc'] ?> script<small><?= e(fmt_bytes((int)$u['sb'])) ?> • terisolasi</small></div>
      <div style="font-size:12px;color:var(--text-muted)"><?= e(time_ago($u['created_at'])) ?></div>
      <div class="user-row-actions">
        <?php if ($u['role']==='owner'): ?><span class="owner-lock">👑 Dilindungi</span>
        <?php elseif ($self): ?><a class="icon-btn" href="?page=settings">⚙️</a>
        <?php else: ?>
          <a class="icon-btn" href="<?= e(page_url(['edit_user'=>$u['id']],'admin')) ?>">✏️</a>
          <a class="icon-btn" href="<?= e(page_url(['reset_user'=>$u['id']],'admin')) ?>">🔑</a>
          <form method="post" action="?page=admin" data-confirm="Hapus user &quot;<?= e($u['username']) ?>&quot; beserta vault-nya?" style="display:flex;gap:4px;align-items:center">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <input class="mini-input" name="admin_totp" placeholder="kode" inputmode="numeric" maxlength="6" required>
            <button class="icon-btn danger" type="submit">🗑️</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<div class="vault-note" style="margin-top:16px"><span>👑</span><span><strong>Owner protection + isolasi vault aktif.</strong> Semua login butuh kode TOTP bersama (atau kode darurat hash-only). Aksi destruktif admin juga butuh kode itu. Ganti/reset password tanpa TOTP.</span></div>

<div class="modal-overlay <?= isset($_GET['add_user'])?'is-open':'' ?>" id="userModal"><div class="modal small">
  <div class="modal-header"><h2 class="modal-title">Tambah User Baru</h2><a class="icon-btn" href="?page=admin">✕</a></div>
  <form method="post" action="?page=admin"><?= csrf_field() ?><input type="hidden" name="action" value="add_user">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Username</label><input class="form-input" name="username" required pattern="[a-z0-9_]{3,32}">
        <div class="form-hint">Huruf kecil, angka, underscore • 3–32 karakter.</div></div>
      <div class="form-group"><label class="form-label">Password</label>
        <div class="input-wrapper"><input class="form-input" type="password" name="password" id="newPass" required minlength="6" style="padding-right:40px">
          <button class="password-toggle" type="button" data-target="newPass">👁️</button></div></div>
      <div class="form-group"><label class="form-label">Role</label>
        <select class="form-select" name="role"><option value="user">User (vault pribadi)</option><option value="admin">Admin (kelola akun)</option></select>
        <div class="form-hint">Semua role saat login tetap butuh kode TOTP bersama.</div></div>
      <div class="form-group"><label class="form-label">Kode TOTP bersama (konfirmasi)</label>
        <input class="form-input" name="admin_totp" inputmode="numeric" maxlength="6" placeholder="6 digit kode live" style="letter-spacing:6px;text-align:center"></div>
    </div>
    <div class="modal-footer"><a class="btn btn-ghost" href="?page=admin">Batal</a><button class="btn btn-admin" type="submit">Simpan User</button></div>
  </form>
</div></div>

<div class="modal-overlay <?= $editUser?'is-open':'' ?>" id="editUserModal"><div class="modal small">
  <div class="modal-header"><h2 class="modal-title">Ubah Role: <?= e($editUser['username'] ?? '') ?></h2><a class="icon-btn" href="?page=admin">✕</a></div>
  <form method="post" action="?page=admin"><?= csrf_field() ?><input type="hidden" name="action" value="update_user"><input type="hidden" name="id" value="<?= (int)($editUser['id'] ?? 0) ?>">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Role</label>
        <select class="form-select" name="role">
          <option value="user" <?= (($editUser['role'] ?? '')==='user')?'selected':'' ?>>User</option>
          <option value="admin" <?= (($editUser['role'] ?? '')==='admin')?'selected':'' ?>>Admin</option>
        </select></div>
      <div class="form-group"><label class="form-label">Kode TOTP bersama</label>
        <input class="form-input" name="admin_totp" inputmode="numeric" maxlength="6" placeholder="6 digit" style="letter-spacing:6px;text-align:center"></div>
    </div>
    <div class="modal-footer"><a class="btn btn-ghost" href="?page=admin">Batal</a><button class="btn btn-admin" type="submit">Update Role</button></div>
  </form>
</div></div>

<div class="modal-overlay <?= $resetUser?'is-open':'' ?>" id="resetUserModal"><div class="modal small">
  <div class="modal-header"><h2 class="modal-title">Reset Password: <?= e($resetUser['username'] ?? '') ?></h2><a class="icon-btn" href="?page=admin">✕</a></div>
  <form method="post" action="?page=admin"><?= csrf_field() ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?= (int)($resetUser['id'] ?? 0) ?>">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Password Baru</label>
        <div class="input-wrapper"><input class="form-input" type="password" name="new_password" id="rp1" required minlength="6" style="padding-right:40px">
          <button class="password-toggle" type="button" data-target="rp1">👁️</button></div></div>
      <div class="form-group"><label class="form-label">Konfirmasi</label>
        <div class="input-wrapper"><input class="form-input" type="password" name="confirm_password" id="rp2" required minlength="6" style="padding-right:40px">
          <button class="password-toggle" type="button" data-target="rp2">👁️</button></div>
        <div class="form-hint">Reset password tidak membutuhkan TOTP.</div></div>
    </div>
    <div class="modal-footer"><a class="btn btn-ghost" href="?page=admin">Batal</a><button class="btn btn-primary" type="submit">Reset Password</button></div>
  </form>
</div></div>

<script>
(function(){
  const box = document.querySelector('[data-global-totp]');
  if (!box) return;
  const codeEl = box.querySelector('.totp-code'), leftEl = box.querySelector('.totp-left');
  let left = 0, code = '';
  function show(){ codeEl.textContent = code ? code.slice(0,3)+' '+code.slice(3) : '------'; leftEl.textContent = 'berganti dalam '+Math.max(0,left)+' detik'; }
  function load(){ fetch('api/totp_code.php',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{ if(j.ok){ code=j.code; left=j.expires_in; show(); } }).catch(()=>{}); }
  setInterval(()=>{ left--; if(left<=0) load(); else show(); }, 1000);
  load();
})();
</script>
