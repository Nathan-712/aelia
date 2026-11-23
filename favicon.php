<?php
/* Favicon dinamis: resize logo brand ke ukuran apa pun. Publik (tanpa login). */
define('AERLIA', true);
require __DIR__ . '/bootstrap.php';

$ALLOW = [16, 32, 48, 64, 128, 180, 192, 512];
$s = (int)($_GET['s'] ?? 32);
if (!in_array($s, $ALLOW, true)) $s = 32;

function fav_data_decode(string $dataUrl): array {
    $pos = strpos($dataUrl, ',');
    $meta = substr($dataUrl, 0, $pos);
    $payload = substr($dataUrl, $pos + 1);
    $isB64 = (stripos($meta, ';base64') !== false);
    $raw = $isB64 ? base64_decode($payload) : rawurldecode($payload);
    $mime = 'image/png';
    if (preg_match('~data:(image/[a-z0-9.+-]+)~i', $meta, $m)) $mime = strtolower($m[1]);
    return [$raw, $mime];
}

[$raw, $mime] = fav_data_decode(site_favicon());
if ($raw === false || $raw === '') { http_response_code(500); exit('brand kosong'); }

/* SVG: vector, kirim apa adanya untuk semua ukuran (tetap tajam) */
if ($mime === 'image/svg+xml') {
    $etag = '"' . md5($raw . '|svg') . '"';
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=604800');
    header('ETag: ' . $etag);
    if ((trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
    echo $raw; exit;
}

/* Raster: resize cover ke s x s */
if (!function_exists('imagecreatefromstring')) { http_response_code(500); exit('GD tidak tersedia'); }
$src = @imagecreatefromstring($raw);
if (!$src) { http_response_code(500); exit('logo tidak bisa dibaca'); }
$w = imagesx($src); $h = imagesy($src); $m = min($w, $h);
$dst = imagecreatetruecolor($s, $s);
imagesavealpha($dst, true);
imagealphablending($dst, false);
imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
imagealphablending($dst, true);
imagecopyresampled($dst, $src, 0, 0, (int)(($w - $m) / 2), (int)(($h - $m) / 2), $s, $s, $m, $m);
ob_start(); imagepng($dst, null, 9); $png = ob_get_clean();
imagedestroy($src); imagedestroy($dst);

$etag = '"' . md5($png) . '"';
header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800');
header('ETag: ' . $etag);
if ((trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
echo $png;