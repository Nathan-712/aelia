<?php
/* =========================================================
   AERLIA CSP PROTECTION — bootstrap.php (v2.3 FINAL)
   Serasi dengan actions.php v2.3 + index.php v2.3.
   Login role-based: admin/owner cukup password; user butuh kode.
   Hardened: HTTPS paksa, HSTS, bind UA, timeout sesi, batas device,
   TOTP bersama + kode darurat hash-only sekali pakai, rehash bcrypt.
   ========================================================= */
if (!defined('AERLIA')) { http_response_code(403); exit('Forbidden'); }
function aerlia_version(): string { return 'bootstrap-2.3'; }

$CONFIG = require __DIR__ . '/config.php';
date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');

/* Tampilkan error SESINGINI bila debug=true (agar fatal di actions pun terlihat) */
if (!empty($CONFIG['app']['debug'])) { @ini_set('display_errors', '1'); @error_reporting(E_ALL); }
else { @ini_set('display_errors', '0'); }

/* ---------- Paksa HTTPS ---------- */
function https_now(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
if (!empty($CONFIG['app']['force_https']) && php_sapi_name() !== 'cli' && !https_now()) {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');
session_name($CONFIG['app']['session_name']);
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>https_now(),'httponly'=>true,'samesite'=>'Lax']);
session_start();
@header_remove('X-Powered-By');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    global $CONFIG; $d = $CONFIG['db'];
    try {
        $pdo = new PDO("mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}", $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Koneksi database gagal. Periksa config.php.');
    }
    return $pdo;
}
function cfg(string $k, $default = null) { global $CONFIG; return $CONFIG['app'][$k] ?? $default; }
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function page_url(array $extra = [], string $page = 'scripts'): string {
    $base = ['page' => $page];
    if (!empty($_GET['lang'])) $base['lang'] = $_GET['lang'];
    return '?' . http_build_query(array_merge($base, $extra));
}
function flash(string $type, string $msg, ?array $link = null): void { $_SESSION['flash'][] = ['type'=>$type,'msg'=>$msg,'link'=>$link]; }
function take_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_ok(?string $t): bool { return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t); }
function csrf_guard(): void {
    if (!csrf_ok($_POST['csrf'] ?? null)) {
        flash('error', 'Sesi keamanan tidak valid. Muat ulang lalu coba lagi.');
        redirect(current_user() ? '?page=home' : '?page=login');
    }
}
function current_user(): ?array {
    static $done = false, $u = null;
    if ($done) return $u; $done = true;
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([(int)$_SESSION['uid']]);
    $row = $st->fetch();
    if (!$row) { unset($_SESSION['uid']); return null; }
    $u = $row; return $u;
}
function uid(): int { return (int)(current_user()['id'] ?? 0); }
function is_admin(): bool { return in_array(current_user()['role'] ?? '', ['admin','owner'], true); }
function is_owner(): bool { return (current_user()['role'] ?? '') === 'owner'; }
function require_login(): void { if (!current_user()) redirect('?page=login'); }
function require_admin(): void { require_login(); if (!is_admin()) { flash('error','Akses ditolak: khusus admin/owner.'); redirect('?page=home'); } }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }

/* ---------- Password helpers (cap 72 byte bcrypt + kebijakan) ---------- */
function pw_cap(string $pw): string { return substr($pw, 0, 72); }
function pw_ok(string $pw): bool { $L = strlen($pw); return $L >= (int)cfg('password_min', 8) && $L <= 200; }
function pw_hash(string $pw): string { return password_hash(pw_cap($pw), PASSWORD_DEFAULT); }
function pw_verify(string $pw, string $hash): bool { return password_verify(pw_cap($pw), $hash); }
function pw_rehash_if_needed(array $u, string $pw): void {
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([pw_hash($pw), (int)$u['id']]);
    }
}

/* ---------- Rate limit login ---------- */
function login_blocked(string $username): bool {
    $st = db()->prepare('SELECT COUNT(*) c FROM login_attempts WHERE ok=0 AND created_at > (NOW() - INTERVAL ? SECOND) AND (username=? OR ip=?)');
    $st->execute([cfg('login_window'), $username, client_ip()]);
    return (int)$st->fetch()['c'] >= (int)cfg('login_max_fail');
}
function login_attempt(string $username, bool $ok): void {
    $st = db()->prepare('INSERT INTO login_attempts (username, ip, ok) VALUES (?,?,?)');
    $st->execute([$username, client_ip(), $ok ? 1 : 0]);
    if (random_int(1,20) === 1) db()->exec('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}

/* ---------- Schema + migrasi ---------- */
function schema_ddl(): array {
    return [
"CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','admin','user') NOT NULL DEFAULT 'user',
  avatar MEDIUMTEXT NULL,
  totp_secret VARCHAR(128) NULL,
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  UNIQUE KEY uniq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS scripts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  language VARCHAR(32) NOT NULL DEFAULT 'plaintext',
  tags VARCHAR(300) NOT NULL DEFAULT '',
  code MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_user_updated (user_id, updated_at),
  KEY idx_lang (language),
  CONSTRAINT fk_scripts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  action VARCHAR(40) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_created (user_id, created_at),
  CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  ok TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_time (username, created_at),
  KEY idx_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS site_settings (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v MEDIUMTEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE TABLE IF NOT EXISTS auth_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sid_hash CHAR(64) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  device VARCHAR(160) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at DATETIME NULL,
  end_reason VARCHAR(20) NULL,
  UNIQUE KEY uniq_sid (sid_hash),
  KEY idx_user (user_id),
  KEY idx_open (ended_at, last_seen),
  CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}
function migrate_legacy_columns(): void {
    static $done = false;
    if ($done) return; $done = true;
    try {
        $has = db()->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='totp_secret'")->fetchColumn();
        if (!$has) db()->exec("ALTER TABLE users ADD COLUMN totp_secret VARCHAR(128) NULL AFTER avatar, ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret");
    } catch (Throwable $e) {}
}
function ensure_schema(bool $force = false): ?string {
    if ($force) { unset($_SESSION['schema_ok'], $_SESSION['schema_error']); }
    try {
        foreach (schema_ddl() as $q) db()->exec($q);
        migrate_legacy_columns();
        $_SESSION['schema_ok'] = 1; unset($_SESSION['schema_error']);
        return null;
    } catch (Throwable $e) { $_SESSION['schema_error'] = $e->getMessage(); return $e->getMessage(); }
}

/* ---------- Brand ---------- */
function site_settings_all(bool $refresh = false): array {
    static $cache = null;
    if ($refresh || $cache === null) {
        $cache = [];
        try { foreach (db()->query('SELECT k, v FROM site_settings') as $r) $cache[$r['k']] = $r['v']; }
        catch (Throwable $e) { $cache = []; }
    }
    return $cache;
}
function site_setting(string $k, ?string $default = null): ?string { $v = site_settings_all()[$k] ?? null; return ($v === null || $v === '') ? $default : $v; }
function set_site_setting(string $k, ?string $v): void {
    db()->prepare('INSERT INTO site_settings (k, v) VALUES (?,?) ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
    site_settings_all(true);
}
function site_name(): string { return site_setting('site_name') ?: cfg('name'); }
function default_favicon(): string {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#00d4ff"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><rect width="64" height="64" rx="14" fill="#050814"/><path d="M32 9l17 6.2v12.3c0 12.6-7.4 21-17 27.5-9.6-6.5-17-14.9-17-27.5V15.2z" fill="url(#g)"/><path d="M32 17l10 3.6v7.2c0 8.6-4.3 14.3-10 18.8-5.7-4.5-10-10.2-10-18.8v-7.2z" fill="#050814" opacity=".5"/><path d="M24.5 31.5l5.2 5.2 9.8-11" stroke="#ffffff" stroke-width="3.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    return 'data:image/svg+xml,' . rawurlencode($svg);
}
function site_favicon(): string { return site_setting('favicon') ?: default_favicon(); }
function favicon_mime(): string { return strpos(site_favicon(), 'data:image/svg') === 0 ? 'image/svg+xml' : 'image/png'; }
function site_logo(): ?string { return site_setting('site_logo'); }
function brand_image_dataurl(array $f, string $mode): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal / tidak ada file.');
    if ($f['size'] > 2*1024*1024) throw new RuntimeException('Ukuran maksimal 2 MB.');
    $raw = file_get_contents($f['tmp_name']);
    if ($raw === false || $raw === '') throw new RuntimeException('File kosong.');
    $info = @getimagesizefromstring($raw);
    $mime = $info['mime'] ?? '';
    if ($mime === 'image/svg+xml' || (!$info && preg_match('/<svg/i', $raw))) {
        if (strlen($raw) > 150000) throw new RuntimeException('SVG terlalu besar (maks 150 KB).');
        if (!preg_match('/<svg/i', $raw)) throw new RuntimeException('File SVG tidak valid.');
        return 'data:image/svg+xml;base64,' . base64_encode($raw);
    }
    if (!in_array($mime, ['image/png','image/jpeg','image/webp','image/gif'], true)) throw new RuntimeException('Format harus PNG, JPG, WebP, GIF, atau SVG.');
    if (!function_exists('imagecreatefromstring')) throw new RuntimeException('GD tidak tersedia.');
    $src = @imagecreatefromstring($raw);
    if (!$src) throw new RuntimeException('Gambar tidak bisa dibaca.');
    $size = ($mode === 'favicon') ? 64 : 128;
    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, true); imagealphablending($dst, false);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagealphablending($dst, true);
    $w = imagesx($src); $h = imagesy($src);
    if ($mode === 'favicon') { $m = min($w,$h); $sx=(int)(($w-$m)/2); $sy=(int)(($h-$m)/2); $dx=0;$dy=0;$dw=$size;$dh=$size;$sw=$m;$sh=$m; }
    else { $sc = min($size/$w,$size/$h); $dw=(int)round($w*$sc); $dh=(int)round($h*$sc); $dx=(int)(($size-$dw)/2); $dy=(int)(($size-$dh)/2); $sx=0;$sy=0;$sw=$w;$sh=$h; }
    imagecopyresampled($dst,$src,$dx,$dy,$sx,$sy,$dw,$dh,$sw,$sh);
    ob_start(); imagepng($dst, null, 9); $png = ob_get_clean();
    imagedestroy($src); imagedestroy($dst);
    if (!$png) throw new RuntimeException('Gagal memproses gambar.');
    return 'data:image/png;base64,' . base64_encode($png);
}

/* ---------- History ---------- */
function add_history(string $action, string $detail): void {
    $u = current_user(); if (!$u) return;
    $uid = (int)$u['id']; $detail = mb_substr($detail, 0, 500);
    $insert = function () use ($uid, $action, $detail) {
        $st = db()->prepare('INSERT INTO history (user_id, action, detail) VALUES (?,?,?)');
        $st->execute([$uid, $action, $detail]);
    };
    try { $insert(); unset($_SESSION['history_error']); }
    catch (Throwable $e) {
        $err = ensure_schema(true);
        if ($err) { $_SESSION['history_error'] = $err; return; }
        try { $insert(); unset($_SESSION['history_error']); }
        catch (Throwable $e2) { $_SESSION['history_error'] = $e2->getMessage(); return; }
    }
    try {
        $c = db()->prepare('SELECT COUNT(*) c FROM history WHERE user_id=?'); $c->execute([$uid]);
        $n = (int)$c->fetch()['c'];
        if ($n > 600) {
            $del = db()->prepare('DELETE FROM history WHERE user_id=? ORDER BY created_at ASC, id ASC LIMIT ?');
            $del->bindValue(1, $uid, PDO::PARAM_INT); $del->bindValue(2, $n-500, PDO::PARAM_INT); $del->execute();
        }
    } catch (Throwable $e) {}
}

/* ---------- Vault ---------- */
function vault_usage(int $userId): array {
    $st = db()->prepare('SELECT COUNT(*) c, COALESCE(SUM(LENGTH(code)),0) b FROM scripts WHERE user_id=?');
    $st->execute([$userId]); $r = $st->fetch();
    return ['count'=>(int)$r['c'], 'bytes'=>(int)$r['b']];
}
function quota_ok(int $userId, int $addBytes, int $addCount = 1): ?string {
    $u = vault_usage($userId);
    if ($u['count'] + $addCount > (int)cfg('max_scripts')) return 'Kuota jumlah script penuh (' . cfg('max_scripts') . ').';
    if ($u['bytes'] + $addBytes > (int)cfg('max_code_bytes')) return 'Kuota storage penuh (' . fmt_bytes((int)cfg('max_code_bytes')) . ').';
    return null;
}
function owned_script(int $id): ?array {
    $st = db()->prepare('SELECT * FROM scripts WHERE id=? AND user_id=?'); $st->execute([$id, uid()]);
    return $st->fetch() ?: null;
}

/* ---------- Waktu (koreksi timezone MySQL → WIB) ---------- */
function db_server_offset(): int {
    static $off = null;
    if ($off !== null) return $off;
    try { $off = (int)db()->query('SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(UTC_TIMESTAMP())')->fetchColumn(); }
    catch (Throwable $e) { $off = 0; }
    return $off;
}
function db_epoch(?string $dt): ?int { if (!$dt) return null; $t = strtotime($dt . ' UTC'); if ($t === false) return null; return $t - db_server_offset(); }
function now_wib(): string { return gmdate('j M Y, H:i', time() + 7*3600) . ' WIB'; }
function fmt_datetime(?string $dt): string { $t = db_epoch($dt); if ($t === null) return '—'; return gmdate('j M Y, H:i', $t + 7*3600) . ' WIB'; }
function time_ago(?string $dt): string {
    $t = db_epoch($dt); if ($t === null) return '—';
    $d = time() - $t; if ($d < 0) $d = 0;
    if ($d < 60) return 'Baru saja';
    if ($d < 3600) return floor($d/60) . ' menit lalu';
    if ($d < 86400) return floor($d/3600) . ' jam lalu';
    if ($d < 604800) return floor($d/86400) . ' hari lalu';
    return gmdate('j M Y', $t + 7*3600);
}

/* ---------- Format ---------- */
function fmt_bytes(int $b): string { if ($b >= 1048576) return round($b/1048576,2).' MB'; if ($b >= 1024) return round($b/1024,1).' KB'; return $b.' B'; }
function languages(): array { return ['javascript','typescript','html','css','php','python','bash','sql','json','java','cpp','plaintext']; }
function previewable(string $l): bool { return in_array($l, ['html','css','javascript'], true); }
function lang_icon(string $l): string { return ['javascript'=>'🟨','typescript'=>'🔷','html'=>'🌐','css'=>'🎨','php'=>'🐘','python'=>'🐍','bash'=>'💻','sql'=>'🗄️','json'=>'📋','java'=>'☕','cpp'=>'⚙️','plaintext'=>'📄'][$l] ?? '📄'; }
function normalize_tags(string $s): string {
    $t = array_values(array_unique(array_filter(array_map('trim', explode(',', $s)), fn($x) => $x !== '')));
    $t = array_slice($t, 0, 10);
    return implode(',', array_map(fn($x) => mb_substr($x, 0, 24), $t));
}
function avatar_html(?string $data, string $username, string $role, string $extra = ''): string {
    $cls = 'user-avatar ' . e($role) . ($extra ? ' ' . $extra : '');
    if ($data) return '<div class="' . $cls . '"><img src="' . e($data) . '" alt=""></div>';
    return '<div class="' . $cls . '">' . e(mb_strtoupper(mb_substr($username, 0, 1))) . '</div>';
}

/* ---------- Header keamanan ---------- */
function send_app_headers(): void {
    @header_remove('X-Powered-By');
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: no-store');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if (https_now()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://www.youtube.com https://s.ytimg.com https://www.gstatic.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; "
        . "img-src 'self' data: blob: https:; "
        . "connect-src 'self' https://yt-admin.yusant0304.workers.dev https://indexer.yusant0304.workers.dev; "
        . "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
);
}

/* ================= TOTP primitives ================= */
function base32_encode_str(string $bin): string {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = ''; $out = '';
    foreach (str_split($bin) as $ch) $bits .= str_pad(decbin(ord($ch)), 8, '0', STR_PAD_LEFT);
    for ($i = 0; $i + 5 <= strlen($bits); $i += 5) $out .= $map[bindec(substr($bits, $i, 5))];
    return $out;
}
function base32_decode_str(string $b32): string {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));
    $bits = ''; $out = '';
    foreach (str_split($b32) as $c) { $i = strpos($map, $c); if ($i === false) continue; $bits .= str_pad(decbin($i), 5, '0', STR_PAD_LEFT); }
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) $out .= chr(bindec(substr($bits, $i, 8)));
    return $out;
}
function totp_secret_new(): string { return base32_encode_str(random_bytes(40)); }
function totp_code(string $secret, ?int $ts = null, int $period = 30): string {
    $ts = $ts ?? time();
    $counter = intdiv($ts, $period);
    $key = base32_decode_str($secret);
    $bin = pack('N*', 0, $counter);
    $hash = hash_hmac('sha1', $bin, $key, true);
    $off = ord(substr($hash, -1)) & 0x0F;
    $code = ((ord($hash[$off]) & 0x7F) << 24) | (ord($hash[$off+1]) << 16) | (ord($hash[$off+2]) << 8) | ord($hash[$off+3]);
    return str_pad((string)($code % 1000000), 6, '0', STR_PAD_LEFT);
}
function totp_verify(string $secret, string $code, int $window = 1): bool {
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) return false;
    $now = time();
    for ($i = -$window; $i <= $window; $i++) if (hash_equals(totp_code($secret, $now + $i*30), $code)) return true;
    return false;
}
function global_totp_secret(): string {
    $s = site_setting('global_totp_secret');
    if (!$s) { $s = totp_secret_new(); set_site_setting('global_totp_secret', $s); }
    return $s;
}
function global_totp_code(): string { return totp_code(global_totp_secret()); }
function global_totp_verify(string $code): bool { return totp_verify(global_totp_secret(), $code); }
/* Konfirmasi aksi admin: HANYA TOTP murni (kode darurat tidak berlaku di sini) */
function totp_only_ok(string $code): bool { return global_totp_verify($code); }

/* ================= Kode darurat (hash-only, sekali pakai) ================= */
function emergency_hash(): ?string { return site_setting('emergency_code_hash'); }
function emergency_set_at(): ?string { return site_setting('emergency_code_set_at'); }
function emergency_verify(string $code): bool {
    $h = emergency_hash(); $code = trim($code);
    if (!$h || $code === '') return false;
    return password_verify($code, $h);
}
function emergency_generate(): string {
    $alpha = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $seg = function (int $n) use ($alpha) { $s=''; for ($i=0;$i<$n;$i++) $s .= $alpha[random_int(0, strlen($alpha)-1)]; return $s; };
    $code = 'AERLIA-' . $seg(6) . '-' . $seg(6);
    set_site_setting('emergency_code_hash', password_hash($code, PASSWORD_DEFAULT));
    set_site_setting('emergency_code_set_at', date('c'));
    return $code;
}
function emergency_clear(): void { set_site_setting('emergency_code_hash', null); set_site_setting('emergency_code_set_at', null); }
function emergency_burn(): void { emergency_clear(); set_site_setting('emergency_code_used_at', date('c')); }
function login_code_kind(string $code): string {
    if (global_totp_verify($code)) return 'totp';
    if (emergency_verify($code)) return 'emergency';
    return 'invalid';
}
function login_code_ok(string $code): bool { return login_code_kind($code) !== 'invalid'; }

/* ================= Session tracking ================= */
function device_label(): string {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $os = 'Unknown';
    if (preg_match('/Windows NT/i',$ua)) $os='Windows'; elseif (preg_match('/Android/i',$ua)) $os='Android';
    elseif (preg_match('/iPhone|iPad|iPod/i',$ua)) $os='iOS'; elseif (preg_match('/Mac OS X/i',$ua)) $os='macOS';
    elseif (preg_match('/Linux/i',$ua)) $os='Linux';
    $br = 'Browser';
    if (preg_match('/Edg\//i',$ua)) $br='Edge'; elseif (preg_match('/OPR\//i',$ua)) $br='Opera';
    elseif (preg_match('/Chrome\//i',$ua)) $br='Chrome'; elseif (preg_match('/Firefox\//i',$ua)) $br='Firefox';
    elseif (preg_match('/Safari\//i',$ua)) $br='Safari';
    return mb_substr($br.' • '.$os, 0, 160);
}
function sess_sid(): string { return hash('sha256', session_id()); }
function sess_current(): ?array {
    try { $st = db()->prepare('SELECT * FROM auth_sessions WHERE sid_hash=?'); $st->execute([sess_sid()]); return $st->fetch() ?: null; }
    catch (Throwable $e) { return null; }
}
function sess_start(int $uid): void {
    try {
        db()->prepare('INSERT INTO auth_sessions (sid_hash,user_id,ip,device) VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE ended_at=NULL, end_reason=NULL, last_seen=NOW(), ip=VALUES(ip), device=VALUES(device)')
            ->execute([sess_sid(), $uid, client_ip(), device_label()]);
        $cap = (int)cfg('max_sessions', 5);
        $st = db()->prepare('SELECT id FROM auth_sessions WHERE user_id=? AND ended_at IS NULL ORDER BY last_seen DESC LIMIT 100');
        $st->execute([$uid]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) > $cap) {
            $old = array_slice($ids, $cap);
            $in = implode(',', array_fill(0, count($old), '?'));
            db()->prepare("UPDATE auth_sessions SET ended_at=NOW(), end_reason='cap' WHERE id IN ($in)")->execute($old);
        }
    } catch (Throwable $e) {}
}
function sess_touch(): void { try { db()->prepare('UPDATE auth_sessions SET last_seen=NOW() WHERE sid_hash=? AND ended_at IS NULL')->execute([sess_sid()]); } catch (Throwable $e) {} }
function sess_end(string $reason = 'logout'): void { try { db()->prepare('UPDATE auth_sessions SET ended_at=NOW(), end_reason=? WHERE sid_hash=? AND ended_at IS NULL')->execute([$reason, sess_sid()]); } catch (Throwable $e) {} }
function sess_kick_id(int $id, string $reason = 'kicked'): void { try { db()->prepare('UPDATE auth_sessions SET ended_at=NOW(), end_reason=? WHERE id=? AND ended_at IS NULL')->execute([$reason, $id]); } catch (Throwable $e) {} }
function sess_list_user(int $uid): array {
    try { $st = db()->prepare('SELECT * FROM auth_sessions WHERE user_id=? AND ended_at IS NULL ORDER BY last_seen DESC'); $st->execute([$uid]); return $st->fetchAll(); }
    catch (Throwable $e) { return []; }
}
function sess_list_all(): array {
    try { return db()->query('SELECT s.*, u.username, u.role FROM auth_sessions s JOIN users u ON u.id=s.user_id WHERE s.ended_at IS NULL ORDER BY s.last_seen DESC')->fetchAll(); }
    catch (Throwable $e) { return []; }
}
function finish_login(array $u, string $via = 'password', bool $burnEmergency = false): void {
    session_regenerate_id(true);
    $_SESSION['uid']  = (int)$u['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['ua_hash'] = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $_SESSION['login_at'] = time();
    $_SESSION['last_active'] = time();
    unset($_SESSION['pending_uid'], $_SESSION['pending_exp'], $_SESSION['pending_user'], $_SESSION['pending_tries']);
    db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$u['id']]);
    sess_start((int)$u['id']);
    add_history('login', 'Login sebagai '.$u['username'].' ('.$u['role'].') via '.$via.' • '.now_wib());
    if ($burnEmergency) {
        emergency_burn();
        add_history('emergency', 'KODE DARURAT dipakai (sekali pakai, kini hangus) oleh '.$u['username'].' dari '.client_ip().' • '.now_wib());
    }
    flash('success', 'Selamat datang, '.$u['username'].'!');
    redirect('?page=home');
}

/* ---------- Init + pengerasan sesi ---------- */
if (empty($_SESSION['schema_ok'])) ensure_schema();
migrate_legacy_columns();
if (!empty($_SESSION['uid'])) {
    $now = time();
    if (cfg('bind_user_agent')) {
        $ua = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (($_SESSION['ua_hash'] ?? null) !== $ua) { $_SESSION = []; session_destroy(); redirect('?page=login&kicked=1'); }
    }
    $idle = (int)cfg('session_idle', 1800);
    $abs  = (int)cfg('session_absolute', 43200);
    if (($now - (int)($_SESSION['last_active'] ?? $now)) > $idle || ($now - (int)($_SESSION['login_at'] ?? $now)) > $abs) {
        sess_end('timeout'); $_SESSION = []; session_destroy(); redirect('?page=login&loggedout=1');
    }
    $_SESSION['last_active'] = $now;
    $cur = sess_current();
    if ($cur && $cur['ended_at'] !== null) { $_SESSION = []; session_destroy(); redirect('?page=login&kicked=1'); }
    elseif ($cur) sess_touch();
}