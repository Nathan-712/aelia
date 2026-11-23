<?php if (!defined('AERLIA')) exit;
$err = $_SESSION['history_error'] ?? ($_SESSION['schema_error'] ?? null);
$rows = []; $count = 0;
try {
    $st = db()->prepare('SELECT * FROM history WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 200');
    $st->execute([uid()]); $rows = $st->fetchAll();
    $c = db()->prepare('SELECT COUNT(*) c FROM history WHERE user_id=?'); $c->execute([uid()]);
    $count = (int)$c->fetch()['c'];
} catch (Throwable $e) { $err = $err ?: $e->getMessage(); }
$icons = ['create'=>'➕','edit'=>'✏️','delete'=>'🗑️','login'=>'🔐','logout'=>'🚪','user-add'=>'👤','user-delete'=>'🗑️','password-change'=>'🔑','role-change'=>'⬆️','avatar-change'=>'🖼️','import'=>'📥','export'=>'📤','test'=>'🧪'];
$labels = ['create'=>'Script dibuat','edit'=>'Script diedit','delete'=>'Script dihapus','login'=>'Login','logout'=>'Logout','user-add'=>'User ditambahkan','user-delete'=>'User dihapus','password-change'=>'Password diubah','role-change'=>'Role diubah','avatar-change'=>'Foto profil diubah','import'=>'Vault diimport','export'=>'Vault diexport','test'=>'Tes rekam history'];
?>
<?php if ($err): ?>
<div class="settings-card" style="border-color:rgba(239,68,68,.4)">
  <div class="settings-card-title"><span>⚠️</span><span>History bermasalah</span></div>
  <div class="settings-card-desc">Penyebab: <code><?= e($err) ?></code><br>Klik <strong>Perbaiki Tabel</strong>. Kalau soal permission, user MySQL kurang hak CREATE/INSERT.</div>
</div>
<?php endif; ?>

<div class="toolbar">
  <div class="search-box" style="max-width:none;flex:1">
    <input type="text" value="<?= $count ?> aktivitas tercatat untuk <?= e($user['username']) ?>" disabled>
  </div>
  <form method="post" action="?page=history"><?= csrf_field() ?>
    <input type="hidden" name="action" value="ping_history">
    <button class="btn btn-ghost" type="submit">🧪 Tes Rekam</button>
  </form>
  <?php if (is_admin()): ?>
  <form method="post" action="?page=history"><?= csrf_field() ?>
    <input type="hidden" name="action" value="repair_db">
    <button class="btn btn-primary" type="submit">🛠️ Perbaiki Tabel</button>
  </form>
  <?php endif; ?>
</div>

<?php if (!$rows): ?>
  <div class="empty-state"><div class="empty-icon">📜</div>
    <div class="empty-title">Belum ada aktivitas</div>
    <p>Klik "Tes Rekam", atau login/logout lalu buka halaman ini lagi.</p></div>
<?php else: ?>
<div class="history-list">
  <?php foreach ($rows as $h): ?>
    <div class="history-item">
      <div class="history-icon <?= e($h['action']) ?>"><?= $icons[$h['action']] ?? '•' ?></div>
      <div class="history-content">
        <div class="history-title"><?= e($labels[$h['action']] ?? $h['action']) ?></div>
        <div class="history-meta"><?= e($h['detail']) ?></div>
      </div>
      <div class="history-time" style="text-align:right">
        <div style="font-weight:600;color:var(--text-secondary)"><?= e(fmt_datetime($h['created_at'])) ?></div>
        <div style="opacity:.7;font-size:11px;margin-top:2px"><?= e(time_ago($h['created_at'])) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>