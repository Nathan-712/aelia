<?php
define('AERLIA', true);
require __DIR__ . '/bootstrap.php';
require_login();

/* ============================================================
   PREVIEW SANDBOX — Render script user di iframe terisolasi
   Diakses via: <iframe src="preview.php?id=123" sandbox="allow-scripts">
   ============================================================ */

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID tidak valid');
}

// Verifikasi ownership: hanya pemilik yang bisa preview
$script = owned_script($id);
if (!$script) {
    http_response_code(403);
    exit('Script tidak ditemukan atau bukan milik kamu');
}

// Cek apakah bahasa bisa di-preview
if (!previewable($script['language'])) {
    http_response_code(400);
    exit('Bahasa "' . e($script['language']) . '" tidak bisa dipreview. Hanya HTML, CSS, dan JavaScript.');
}

/* ---------- Header Keamanan Ketat untuk Sandbox ---------- */
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');

// CSP sangat ketat: hanya izinkan inline script/style, tidak ada network request
header("Content-Security-Policy: default-src 'none'; "
    . "script-src 'unsafe-inline'; "
    . "style-src 'unsafe-inline'; "
    . "img-src data: blob:; "
    . "font-src data:; "
    . "connect-src 'none'; "
    . "frame-src 'none'; "
    . "object-src 'none'; "
    . "base-uri 'none'; "
    . "form-action 'none'");

/* ---------- Render Berdasarkan Bahasa ---------- */
$code = $script['code'];
$lang = $script['language'];
$title = e($script['title']);

// Template wrapper yang aman
$html = '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview: ' . $title . '</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    padding: 20px; 
    background: #fff; 
    color: #333;
    line-height: 1.6;
  }
  .preview-header {
    background: linear-gradient(135deg, #00d4ff, #7c3aed);
    color: #fff;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    font-weight: 600;
  }
  .preview-header small {
    display: block;
    font-size: 11px;
    opacity: 0.85;
    margin-top: 4px;
    font-weight: 400;
  }
</style>';

if ($lang === 'html') {
    // HTML: render langsung
    $html .= '</head><body>';
    $html .= '<div class="preview-header">🌐 HTML Preview<small>' . $title . '</small></div>';
    $html .= $code; // Raw HTML dari user
    $html .= '</body></html>';
    
} elseif ($lang === 'css') {
    // CSS: wrap dalam <style> + contoh elemen
    $html .= '<style>' . $code . '</style>';
    $html .= '</head><body>';
    $html .= '<div class="preview-header">🎨 CSS Preview<small>' . $title . '</small></div>';
    $html .= '<div class="css-demo">';
    $html .= '<h1>Heading 1</h1>';
    $html .= '<h2>Heading 2</h2>';
    $html .= '<h3>Heading 3</h3>';
    $html .= '<p>Ini adalah paragraf contoh untuk melihat efek CSS kamu.</p>';
    $html .= '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
    $html .= '<ul><li>List item 1</li><li>List item 2</li><li>List item 3</li></ul>';
    $html .= '<button>Button</button> ';
    $html .= '<a href="#">Link</a>';
    $html .= '<div class="box">Box dengan class</div>';
    $html .= '<div id="demo">Div dengan ID</div>';
    $html .= '</div>';
    $html .= '</body></html>';
    
} elseif ($lang === 'javascript') {
    // JavaScript: wrap dalam <script> + console output area
    $html .= '<style>
      .console-output {
        background: #1e1e2e;
        color: #a6e3a1;
        padding: 16px;
        border-radius: 8px;
        font-family: "JetBrains Mono", "Fira Code", monospace;
        font-size: 13px;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 400px;
        overflow-y: auto;
      }
      .console-error { color: #f38ba8; }
      .console-warn { color: #f9e2af; }
    </style>';
    $html .= '</head><body>';
    $html .= '<div class="preview-header">🟨 JavaScript Preview<small>' . $title . '</small></div>';
    $html .= '<div class="console-output" id="consoleOutput">Menjalankan script...</div>';
    $html .= '<script>';
    $html .= '(function() {';
    $html .= '  var output = document.getElementById("consoleOutput");';
    $html .= '  var log = function(msg, type) {';
    $html .= '    var div = document.createElement("div");';
    $html .= '    div.textContent = msg;';
    $html .= '    if (type) div.className = "console-" + type;';
    $html .= '    output.appendChild(div);';
    $html .= '  };';
    $html .= '  ';
    $html .= '  // Override console methods';
    $html .= '  var origLog = console.log;';
    $html .= '  var origWarn = console.warn;';
    $html .= '  var origError = console.error;';
    $html .= '  ';
    $html .= '  console.log = function() {';
    $html .= '    var args = Array.prototype.slice.call(arguments);';
    $html .= '    log(args.map(function(a) { return typeof a === "object" ? JSON.stringify(a, null, 2) : String(a); }).join(" "));';
    $html .= '    origLog.apply(console, arguments);';
    $html .= '  };';
    $html .= '  ';
    $html .= '  console.warn = function() {';
    $html .= '    var args = Array.prototype.slice.call(arguments);';
    $html .= '    log(args.map(function(a) { return typeof a === "object" ? JSON.stringify(a, null, 2) : String(a); }).join(" "), "warn");';
    $html .= '    origWarn.apply(console, arguments);';
    $html .= '  };';
    $html .= '  ';
    $html .= '  console.error = function() {';
    $html .= '    var args = Array.prototype.slice.call(arguments);';
    $html .= '    log(args.map(function(a) { return typeof a === "object" ? JSON.stringify(a, null, 2) : String(a); }).join(" "), "error");';
    $html .= '    origError.apply(console, arguments);';
    $html .= '  };';
    $html .= '  ';
    $html .= '  try {';
    $html .= '    ' . $code; // JavaScript dari user
    $html .= '    log("✅ Script selesai dijalankan.");';
    $html .= '  } catch (e) {';
    $html .= '    log("❌ Error: " + e.message, "error");';
    $html .= '  }';
    $html .= '})();';
    $html .= '</script>';
    $html .= '</body></html>';
}

// Output HTML yang sudah di-render
echo $html;