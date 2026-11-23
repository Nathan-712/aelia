<?php
/* Kode TOTP bersama — HANYA admin/owner. */
define('AERLIA', true);
require __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$me = current_user();
if (!$me) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Hanya admin/owner yang boleh melihat kode TOTP bersama.']); exit; }
echo json_encode([
    'ok' => true,
    'code' => global_totp_code(),
    'expires_in' => 30 - (time() % 30),
], JSON_UNESCAPED_UNICODE);
