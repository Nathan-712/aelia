<?php
if (!defined('AERLIA')) { http_response_code(403); exit('Forbidden'); }
return [
    'db' => [
        'host'    => 'sql102.infinityfree.com',      // ganti sesuai panel DB-mu
        'name'    => 'if0_43285919_AerliaSQL',           // ganti
        'user'    => 'if0_43285919',                  // ganti
        'pass'    => 'GPQ1YEnQbo',             // ganti
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name'                => 'Aerlia CSP Protection',
        'owner_username'      => 'raiys',
        'owner_password'      => 'raisyagantengbanget',
        'max_scripts'         => 300,
        'max_code_bytes'      => 2 * 1024 * 1024,
        'avatar_max_bytes'    => 2 * 1024 * 1024,
        'import_max_bytes'    => 4 * 1024 * 1024,
        'session_name'        => 'aerlia_sess',
        'debug'               => false,              // true HANYA untuk debug error, lalu matikan lagi
        'force_https'         => true,
        'session_idle'        => 1800,               // 30 menit diam → logout
        'session_absolute'    => 43200,              // 12 jam maks umur sesi
        'max_sessions'        => 5,                  // maks device login bersamaan per user
        'bind_user_agent'     => true,               // sesi invalid jika User-Agent berubah
        'login_max_fail'      => 8,
        'login_window'        => 900,
        'login_fail_delay'    => 1,                  // jeda detik saat login gagal (anti brute-force)
        'twofa_max_attempts'  => 5,                  // batas percobaan kode per sesi login user
        'password_min'        => 8,
        'emergency_single_use'=> true,               // kode darurat hangus setelah sekali pakai
    ],
];