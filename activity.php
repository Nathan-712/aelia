<?php
define('AERLIA', true);
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../realtime_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$u = current_user();
if (!$u) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
if (!rt_ready()) { echo json_encode(['ok'=>false,'error'=>'upgrade','message'=>'Jalankan upgrade_realtime.php dulu']); exit; }

rt_touch();
$since = max(0, (int)($_GET['since'] ?? 0));
$all = is_admin();
$rows = rt_events($since, $all, 40);
$online = $all ? rt_online() : [['user_id'=>(int)$u['id'],'username'=>$u['username'],'role'=>$u['role']]];

$events = [];
foreach ($rows as $r) {
    $events[] = [
        'id'=>(int)$r['id'], 'type'=>$r['type'],
        'user_id'=>(int)$r['user_id'], 'username'=>$r['username'],
        'ip'=>$all ? $r['ip'] : '', 'device'=>$r['device'],
        'ts'=>strtotime((string)$r['created_at']),
    ];
}
echo json_encode([
    'ok'=>true, 'now'=>time(), 'scope'=>$all?'all':'own',
    'events'=>$events, 'online'=>$online, 'online_count'=>count($online),
], JSON_UNESCAPED_UNICODE);