<?php
/* Web manifest dinamis: nama & ikon mengikuti brand yang diupload. */
define('AERLIA', true);
require __DIR__ . '/bootstrap.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$name = site_name();
$short = mb_substr(trim(preg_split('/\s+/', $name)[0] ?? $name), 0, 12) ?: 'Aerlia';
$isSvg = (favicon_mime() === 'image/svg+xml');

$icons = $isSvg
    ? [
        ['src' => 'favicon.php?s=512', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
        ['src' => 'favicon.php?s=512', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'maskable'],
      ]
    : [
        ['src' => 'favicon.php?s=192', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'favicon.php?s=512', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'favicon.php?s=512', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
      ];

echo json_encode([
    'name'             => $name,
    'short_name'       => $short,
    'description'      => 'Private code vault dengan proteksi CSP.',
    'start_url'        => '/?page=home',
    'scope'            => '/',
    'display'          => 'standalone',
    'orientation'      => 'portrait',
    'background_color' => '#050814',
    'theme_color'      => '#050814',
    'icons'            => $icons,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);