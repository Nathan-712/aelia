<?php
/* =========================================================
   AERLIA CSP PROTECTION — actions.php (v2.3 FINAL)
   Login role-based:
     - admin / owner  -> cukup password, LANGSUNG masuk (tanpa kode)
     - user biasa     -> password + kode TOTP bersama / kode darurat
   Aksi destruktif admin tetap butuh konfirmasi TOTP murni.
   Ganti/reset password tanpa TOTP. Pasangan: bootstrap.php v2.2.
   ========================================================= */
if (!defined('AERLIA')) { http_response_code(403); exit('Forbidden'); }

/* ---------- Undo hapus script (GET bertoken CSRF) ---------- */
if (isset($_GET['undo']) && current_user()) {
    if (!csrf_ok($_GET['csrf'] ?? null)) { flash('error','Token undo tidak valid.'); redirect('?page=scripts'); }
    $stash = $_SESSION['undo_script'] ?? null; unset($_SESSION['undo_script']);
    if (!$stash || (int)$stash['user_id'] !== uid()) { flash('error','Tidak ada yang bisa dipulihkan.'); redirect('?page=scripts'); }
    $q = quota_ok(uid(), strlen($stash['code']), 1);
    if ($q) { flash('error','Gagal memulihkan: ' . $q); redirect('?page=scripts'); }
    db()->prepare('INSERT INTO scripts (user_id,title,description,language,tags,code,created_at) VALUES (?,?,?,?,?,?,?)')
        ->execute([uid(), $stash['title'], $stash['description'], $stash['language'], $stash['tags'], $stash['code'], $stash['created_at']]);
    add_history('create', $stash['title'] . ' (dipulihkan)');
    flash('success','Script dipulihkan.'); redirect('?page=scripts');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
$action = $_POST['action'] ?? '';
csrf_guard();

/* Konfirmasi aksi destruktif admin: HANYA kode TOTP bersama (kode darurat ditolak di sini) */
function admin_totp_guard(string $fail = '?page=admin'): void {
    if (!totp_only_ok((string)($_POST['admin_totp'] ?? ''))) {
        flash('error','Kode TOTP bersama salah / kedaluwarsa.'); redirect($fail);
    }
}

/* ================= LOGIN (role-based) ================= */
if ($action === 'login') {
    $username = strtolower(trim((string)($_POST['username'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === '') { flash('error','Username dan password wajib diisi.'); redirect('?page=login'); }
    if (strlen($password) > 200) $password = substr($password, 0, 200);
    if (login_blocked($username)) { flash('error','Terlalu banyak percobaan gagal. Coba lagi 15 menit.'); redirect('?page=login'); }
    $st = db()->prepare('SELECT * FROM users WHERE username=?'); $st->execute([$username]);
    $u = $st->fetch();
    if (!$u || !pw_verify($password, $u['password_hash'])) {
        login_attempt($username, false);
        $d = (int)cfg('login_fail_delay', 1); if ($d > 0) sleep($d);
        flash('error','Username atau password salah.'); redirect('?page=login');
    }
    login_attempt($username, true);
    pw_rehash_if_needed($u, $password);

    /* Admin & Owner: cukup password, langsung masuk tanpa kode. */
    if (in_array($u['role'], ['admin', 'owner'], true)) {
        finish_login($u, 'password');
    }

    /* User biasa: wajib kode TOTP bersama / kode darurat (langkah 2FA). */
    $_SESSION['pending_uid']   = (int)$u['id'];
    $_SESSION['pending_exp']   = time() + 300;
    $_SESSION['pending_user']  = $u['username'];
    $_SESSION['pending_tries'] = 0;
    redirect('?page=login&step=2fa');
}

if ($action === 'login_2fa') {
    $pu = (int)($_SESSION['pending_uid'] ?? 0); $exp = (int)($_SESSION['pending_exp'] ?? 0);
    if (!$pu || time() > $exp) {
        unset($_SESSION['pending_uid'],$_SESSION['pending_exp'],$_SESSION['pending_user'],$_SESSION['pending_tries']);
        flash('error','Sesi verifikasi kedaluwarsa. Login ulang.'); redirect('?page=login');
    }
    $st = db()->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$pu]); $u = $st->fetch();
    if (!$u) { unset($_SESSION['pending_uid'],$_SESSION['pending_exp'],$_SESSION['pending_user'],$_SESSION['pending_tries']); redirect('?page=login'); }
    $tries = (int)($_SESSION['pending_tries'] ?? 0);
    $maxT  = (int)cfg('twofa_max_attempts', 5);
    $kind  = login_code_kind((string)($_POST['totp_code'] ?? ''));
    if ($kind === 'invalid') {
        $tries++;
        login_attempt($u['username'], false);
        if ($tries >= $maxT) {
            unset($_SESSION['pending_uid'],$_SESSION['pending_exp'],$_SESSION['pending_user'],$_SESSION['pending_tries']);
            flash('error','Terlalu banyak percobaan kode. Login ulang dari awal.'); redirect('?page=login');
        }
        $_SESSION['pending_tries'] = $tries;
        flash('error','Kode TOTP salah. Sisa percobaan: ' . ($maxT - $tries));
        redirect('?page=login&step=2fa');
    }
    unset($_SESSION['pending_tries']);
    finish_login($u,
        $kind === 'emergency' ? 'password+EMERGENCY' : 'password+TOTP',
        $kind === 'emergency' && (bool)cfg('emergency_single_use', true));
}

if ($action === 'login_2fa_cancel') {
    unset($_SESSION['pending_uid'],$_SESSION['pending_exp'],$_SESSION['pending_user'],$_SESSION['pending_tries']);
    redirect('?page=login');
}

/* ================= LOGOUT ================= */
if ($action === 'logout') {
    require_login();
    add_history('logout', 'Logout dari ' . current_user()['username'] . ' • ' . now_wib());
    sess_end('logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); }
    session_destroy();
    redirect('?page=login&loggedout=1');
}

require_login();

/* ================= HISTORY / REPAIR ================= */
if ($action === 'ping_history') {
    add_history('test', 'Tes rekam history manual • ' . now_wib());
    if (!empty($_SESSION['history_error'])) flash('error','Gagal rekam history: ' . $_SESSION['history_error']);
    else flash('success','Berhasil rekam history.');
    redirect('?page=history');
}
if ($action === 'repair_db') {
    require_admin();
    $err = ensure_schema(true);
    if ($err) flash('error','Perbaiki tabel gagal: ' . $err);
    else flash('success','Semua tabel siap.');
    redirect('?page=history');
}

/* ================= TOTP BERSAMA: ROTASI & REVEAL ================= */
if ($action === 'rotate_global_totp') {
    require_admin();
    if (!totp_only_ok((string)($_POST['admin_totp'] ?? ''))) { flash('error','Kode TOTP bersama salah. Rotasi dibatalkan.'); redirect('?page=admin'); }
    set_site_setting('global_totp_secret', totp_secret_new());
    add_history('2fa', 'Secret TOTP bersama dirotasi • ' . now_wib());
    flash('success','Secret TOTP bersama diganti. Authenticator lama tidak berlaku.');
    redirect('?page=admin');
}
if ($action === 'reveal_secret') {
    require_admin();
    if (!totp_only_ok((string)($_POST['admin_totp'] ?? ''))) { flash('error','Kode TOTP bersama salah.'); redirect('?page=admin'); }
    $_SESSION['reveal_secret_once'] = global_totp_secret();
    add_history('2fa', 'Secret TOTP bersama ditampilkan sekali oleh '.current_user()['username'].' • '.now_wib());
    flash('info','Secret ditampilkan sekali di panel. Salin sekarang.');
    redirect('?page=admin');
}

/* ================= KODE DARURAT: BUAT / HAPUS ================= */
if ($action === 'generate_emergency') {
    require_admin(); admin_totp_guard();
    $code = emergency_generate();
    $_SESSION['emergency_plain'] = $code;
    add_history('emergency', 'Kode darurat dibuat/dirotasi oleh '.current_user()['username'].' • '.now_wib());
    flash('success','Kode darurat baru dibuat. Tampil SEKALI di panel admin.');
    redirect('?page=admin');
}
if ($action === 'remove_emergency') {
    require_admin(); admin_totp_guard();
    emergency_clear();
    add_history('emergency', 'Kode darurat dihapus oleh '.current_user()['username'].' • '.now_wib());
    flash('success','Kode darurat dihapus.');
    redirect('?page=admin');
}

/* ================= KICK SESSION ================= */
if ($action === 'kick_session') {
    $id = (int)($_POST['session_id'] ?? 0);
    $back = (string)($_POST['back'] ?? '?page=settings');
    $st = db()->prepare('SELECT * FROM auth_sessions WHERE id=?'); $st->execute([$id]); $row = $st->fetch();
    if (!$row || $row['ended_at'] !== null) { flash('error','Sesi tidak ditemukan / sudah berakhir.'); redirect($back); }
    if ((int)$row['user_id'] === uid()) {
        if ($row['sid_hash'] === sess_sid()) { flash('error','Tidak bisa meng-kick sesi yang sedang dipakai.'); redirect($back); }
        sess_kick_id($id, 'kicked_self');
        add_history('session-kick','Sesi sendiri dihentikan ('.$row['device'].') • '.now_wib());
        flash('success','Sesi perangkat lain kamu dihentikan.');
    } else {
        require_admin(); admin_totp_guard($back);
        sess_kick_id($id, 'kicked_admin');
        $tu = db()->prepare('SELECT username FROM users WHERE id=?'); $tu->execute([(int)$row['user_id']]);
        add_history('session-kick','Sesi '.$tu->fetchColumn().' dihentikan admin ('.$row['device'].') • '.now_wib());
        flash('success','Sesi user dihentikan.');
    }
    redirect($back);
}

/* ================= BRAND ================= */
if ($action === 'save_site_identity') {
    require_admin();
    $name = trim((string)($_POST['site_name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 60) { flash('error','Nama situs 1–60 karakter.'); redirect('?page=settings'); }
    set_site_setting('site_name', $name);
    add_history('brand','Nama situs diubah menjadi "'.$name.'" • '.now_wib());
    flash('success','Nama situs diperbarui.'); redirect('?page=settings');
}
if ($action === 'upload_favicon') {
    require_admin();
    try { set_site_setting('favicon', brand_image_dataurl($_FILES['favicon'] ?? [], 'favicon')); add_history('brand','Favicon diperbarui • '.now_wib()); flash('success','Favicon tersimpan.'); }
    catch (Throwable $e) { flash('error','Favicon gagal: '.$e->getMessage()); }
    redirect('?page=settings');
}
if ($action === 'upload_site_logo') {
    require_admin();
    try { set_site_setting('site_logo', brand_image_dataurl($_FILES['site_logo'] ?? [], 'logo')); add_history('brand','Logo situs diperbarui • '.now_wib()); flash('success','Logo situs tersimpan.'); }
    catch (Throwable $e) { flash('error','Logo gagal: '.$e->getMessage()); }
    redirect('?page=settings');
}
if ($action === 'remove_favicon') { require_admin(); set_site_setting('favicon', null); add_history('brand','Favicon dikembalikan • '.now_wib()); flash('success','Favicon bawaan.'); redirect('?page=settings'); }
if ($action === 'remove_site_logo') { require_admin(); set_site_setting('site_logo', null); add_history('brand','Logo dikembalikan • '.now_wib()); flash('success','Logo bawaan.'); redirect('?page=settings'); }

/* ================= SCRIPTS ================= */
if ($action === 'save_script') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $description = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 500);
    $language = (string)($_POST['language'] ?? 'plaintext');
    $tags = normalize_tags((string)($_POST['tags'] ?? ''));
    $code = (string)($_POST['code'] ?? '');
    if ($title === '' || mb_strlen($title) > 200) { flash('error','Judul wajib diisi (maks 200).'); redirect('?page=scripts'); }
    if ($code === '') { flash('error','Kode script wajib diisi.'); redirect('?page=scripts'); }
    if (strlen($code) > 1024*1024) { flash('error','Kode terlalu besar (maks 1 MB per script).'); redirect('?page=scripts'); }
    if (!in_array($language, languages(), true)) $language = 'plaintext';
    if ($id > 0) {
        $old = owned_script($id);
        if (!$old) { flash('error','Script tidak ditemukan / bukan milik kamu.'); redirect('?page=scripts'); }
        $delta = strlen($code) - strlen($old['code']);
        if ($delta > 0) { $q = quota_ok(uid(), $delta, 0); if ($q) { flash('error',$q); redirect(page_url(['edit'=>$id])); } }
        db()->prepare('UPDATE scripts SET title=?,description=?,language=?,tags=?,code=? WHERE id=? AND user_id=?')->execute([$title,$description,$language,$tags,$code,$id,uid()]);
        add_history('edit', $title); flash('success','Script diperbarui.');
    } else {
        $q = quota_ok(uid(), strlen($code), 1);
        if ($q) { flash('error',$q); redirect('?page=scripts'); }
        db()->prepare('INSERT INTO scripts (user_id,title,description,language,tags,code) VALUES (?,?,?,?,?,?)')->execute([uid(),$title,$description,$language,$tags,$code]);
        add_history('create', $title); flash('success','Script disimpan ke vault kamu.');
    }
    redirect('?page=scripts');
}
if ($action === 'delete_script') {
    $id = (int)($_POST['id'] ?? 0);
    $s = owned_script($id);
    if (!$s) { flash('error','Script tidak ditemukan.'); redirect('?page=scripts'); }
    db()->prepare('DELETE FROM scripts WHERE id=? AND user_id=?')->execute([$id, uid()]);
    $_SESSION['undo_script'] = $s;
    add_history('delete', $s['title']);
    flash('info','"'.$s['title'].'" dihapus.', ['url'=>page_url(['undo'=>1,'csrf'=>csrf_token()]),'label'=>'Urungkan']);
    redirect('?page=scripts');
}

/* ================= ADMIN USER ================= */
if (in_array($action, ['add_user','update_user','delete_user','reset_password'], true)) {
    require_admin();
    $me = current_user();

    if ($action === 'add_user') {
        admin_totp_guard();
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'user');
        if (!preg_match('/^[a-z0-9_]{3,32}$/', $username)) { flash('error','Username: 3–32 karakter huruf kecil/angka/underscore.'); redirect('?page=admin'); }
        if (!pw_ok($password)) { flash('error','Password minimal '.(int)cfg('password_min',8).' karakter.'); redirect('?page=admin'); }
        if (!in_array($role, ['user','admin'], true)) $role = 'user';
        $q = db()->prepare('SELECT id FROM users WHERE username=?'); $q->execute([$username]);
        if ($q->fetch()) { flash('error','Username sudah digunakan.'); redirect('?page=admin'); }
        db()->prepare('INSERT INTO users (username,password_hash,role) VALUES (?,?,?)')->execute([$username, pw_hash($password), $role]);
        add_history('user-add', "User $username dibuat ($role) • " . now_wib());
        flash('success', "User $username dibuat. Saat login ia butuh kode TOTP bersama; admin/owner cukup password.");
        redirect('?page=admin');
    }

    $targetId = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$targetId]); $target = $st->fetch();
    if (!$target) { flash('error','User tidak ditemukan.'); redirect('?page=admin'); }
    if ($target['role'] === 'owner') { flash('error','👑 Akun OWNER dilindungi server.'); redirect('?page=admin'); }
    if ((int)$target['id'] === (int)$me['id']) { flash('error','Gunakan Settings untuk akun sendiri.'); redirect('?page=admin'); }

    if ($action === 'update_user') {
        admin_totp_guard();
        $role = (string)($_POST['role'] ?? $target['role']);
        if (!in_array($role, ['user','admin'], true)) $role = $target['role'];
        db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $targetId]);
        add_history('role-change', $target['username'].' sekarang '.$role.' • '.now_wib());
        flash('success','Role diperbarui.'); redirect('?page=admin');
    }
    /* Reset password TANPA TOTP + putus semua sesi user target */
    if ($action === 'reset_password') {
        $np = (string)($_POST['new_password'] ?? ''); $cp = (string)($_POST['confirm_password'] ?? '');
        if (!pw_ok($np)) { flash('error','Password minimal '.(int)cfg('password_min',8).' karakter.'); redirect(page_url(['reset_user'=>$targetId],'admin')); }
        if ($np !== $cp) { flash('error','Konfirmasi tidak cocok.'); redirect(page_url(['reset_user'=>$targetId],'admin')); }
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([pw_hash($np), $targetId]);
        db()->prepare('UPDATE auth_sessions SET ended_at=NOW(), end_reason=? WHERE user_id=?')->execute(['password', $targetId]);
        add_history('password-change','Password '.$target['username'].' direset & semua sesinya diputus • '.now_wib());
        flash('success','Password direset; semua sesi user itu diputus.'); redirect('?page=admin');
    }
    if ($action === 'delete_user') {
        admin_totp_guard();
        db()->prepare('DELETE FROM users WHERE id=?')->execute([$targetId]);
        add_history('user-delete','User '.$target['username'].' dihapus beserta vault-nya • '.now_wib());
        flash('success','User dihapus.'); redirect('?page=admin');
    }
}

/* ================= SETTINGS (password TANPA TOTP + putus sesi lain) ================= */
if ($action === 'change_password') {
    $me = current_user();
    $old = (string)($_POST['old_password'] ?? ''); $new = (string)($_POST['new_password'] ?? ''); $conf = (string)($_POST['confirm_password'] ?? '');
    if (!pw_verify($old, $me['password_hash'])) { flash('error','Password lama salah.'); redirect('?page=settings'); }
    if (!pw_ok($new)) { flash('error','Password baru minimal '.(int)cfg('password_min',8).' karakter.'); redirect('?page=settings'); }
    if ($new !== $conf) { flash('error','Konfirmasi tidak cocok.'); redirect('?page=settings'); }
    db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([pw_hash($new), uid()]);
    db()->prepare('UPDATE auth_sessions SET ended_at=NOW(), end_reason=? WHERE user_id=? AND sid_hash<>?')->execute(['password', uid(), sess_sid()]);
    add_history('password-change','Password akun sendiri diubah & sesi lain diputus • '.now_wib());
    flash('success','Password diubah. Perangkat lain kamu telah di-logout.'); redirect('?page=settings');
}
if ($action === 'upload_avatar') {
    if (!function_exists('imagecreatefromstring')) { flash('error','GD tidak tersedia.'); redirect('?page=settings'); }
    $f = $_FILES['avatar'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { flash('error','Upload gagal / tidak ada file.'); redirect('?page=settings'); }
    if ($f['size'] > (int)cfg('avatar_max_bytes')) { flash('error','Maksimal 2 MB.'); redirect('?page=settings'); }
    $raw = file_get_contents($f['tmp_name']);
    $info = @getimagesizefromstring($raw);
    if (!$info || !in_array($info['mime'], ['image/jpeg','image/png','image/gif','image/webp'], true)) { flash('error','Format harus JPG/PNG/GIF/WebP.'); redirect('?page=settings'); }
    $src = @imagecreatefromstring($raw);
    if (!$src) { flash('error','Gambar tidak bisa dibaca.'); redirect('?page=settings'); }
    $w = imagesx($src); $h = imagesy($src); $m = min($w,$h);
    $dst = imagecreatetruecolor(128,128);
    imagecopyresampled($dst,$src,0,0,(int)(($w-$m)/2),(int)(($h-$m)/2),128,128,$m,$m);
    ob_start(); imagejpeg($dst,null,85); $jpg = ob_get_clean();
    imagedestroy($src); imagedestroy($dst);
    db()->prepare('UPDATE users SET avatar=? WHERE id=?')->execute(['data:image/jpeg;base64,'.base64_encode($jpg), uid()]);
    add_history('avatar-change','Foto profil diperbarui • '.now_wib());
    flash('success','Foto profil tersimpan.'); redirect('?page=settings');
}
if ($action === 'remove_avatar') {
    db()->prepare('UPDATE users SET avatar=NULL WHERE id=?')->execute([uid()]);
    add_history('avatar-change','Foto profil dihapus • '.now_wib());
    flash('success','Foto profil dihapus.'); redirect('?page=settings');
}
if ($action === 'import_vault') {
    $f = $_FILES['vault'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { flash('error','File import tidak ada.'); redirect('?page=settings'); }
    if ($f['size'] > (int)cfg('import_max_bytes')) { flash('error','File import maksimal 4 MB.'); redirect('?page=settings'); }
    $json = json_decode((string)file_get_contents($f['tmp_name']), true);
    $list = $json['scripts'] ?? null;
    if (!is_array($list)) { flash('error','Format JSON tidak valid (butuh key "scripts").'); redirect('?page=settings'); }
    $rows = []; $bytes = 0;
    foreach ($list as $s) {
        if (!is_array($s) || !isset($s['title'],$s['code']) || !is_string($s['title']) || !is_string($s['code'])) continue;
        $lang = in_array($s['language'] ?? '', languages(), true) ? $s['language'] : 'plaintext';
        $tags = is_array($s['tags'] ?? null) ? normalize_tags(implode(',', $s['tags'])) : normalize_tags((string)($s['tags'] ?? ''));
        $rows[] = [mb_substr($s['title'],0,200), mb_substr((string)($s['description'] ?? ''),0,500), $lang, $tags, $s['code']];
        $bytes += strlen($s['code']);
    }
    if (!$rows) { flash('error','Tidak ada script valid.'); redirect('?page=settings'); }
    $q = quota_ok(uid(), $bytes, count($rows));
    if ($q) { flash('error','Import dibatalkan: '.$q); redirect('?page=settings'); }
    $st = db()->prepare('INSERT INTO scripts (user_id,title,description,language,tags,code) VALUES (?,?,?,?,?,?)');
    foreach ($rows as $r) $st->execute([uid(), $r[0], $r[1], $r[2], $r[3], $r[4]]);
    add_history('import', count($rows).' script diimport • '.now_wib());
    flash('success', count($rows).' script masuk ke vault.'); redirect('?page=settings');
}

flash('error','Aksi tidak dikenal.'); redirect('?page=home');