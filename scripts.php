<?php if (!defined('AERLIA')) exit;
$dbError = null; $scripts = []; $langCounts = []; $histCount = 0; $usage = ['count'=>0,'bytes'=>0]; $langFilter = (string)($_GET['lang'] ?? '');
try {
    $usage = vault_usage(uid());
    $params = [uid()]; $sql = 'SELECT * FROM scripts WHERE user_id = ?';
    if ($langFilter !== '' && in_array($langFilter, languages(), true)) { $sql .= ' AND language = ?'; $params[] = $langFilter; }
    $sql .= ' ORDER BY updated_at DESC, id DESC';
    $st = db()->prepare($sql); $st->execute($params); $scripts = $st->fetchAll();
    $hc = db()->prepare('SELECT COUNT(*) c FROM history WHERE user_id=?'); $hc->execute([uid()]); $histCount = (int)$hc->fetch()['c'];
    $lc = db()->prepare('SELECT language, COUNT(*) c FROM scripts WHERE user_id=? GROUP BY language ORDER BY c DESC'); $lc->execute([uid()]); $langCounts = $lc->fetchAll();
} catch (Throwable $e) { $dbError = $e->getMessage(); }
$edit = $view = $prev = null;
try {
    if (!empty($_GET['edit'])) $edit = owned_script((int)$_GET['edit']);
    if (!empty($_GET['view'])) $view = owned_script((int)$_GET['view']);
    if (!empty($_GET['preview'])) $prev = owned_script((int)$_GET['preview']);
} catch (Throwable $e) { $dbError = $dbError ?: $e->getMessage(); }
$isNew = isset($_GET['new']);
?>
<?php if ($dbError): ?>
<div class="settings-card" style="border-color:rgba(239,68,68,.4)">
  <div class="settings-card-title"><span>⚠️</span><span>Database belum siap</span></div>
  <div class="settings-card-desc"><code><?= e($dbError) ?></code><br>Buka History → <strong>Perbaiki Tabel</strong>, atau jalankan install.php.</div>
</div>
<?php endif; ?>
<div class="stats-grid">
  <div class="stat-card"><div class="stat-label">Script di Vault</div><div class="stat-value"><?= $usage['count'] ?></div></div>
  <div class="stat-card"><div class="stat-label">Bahasa</div><div class="stat-value"><?= count($langCounts) ?></div></div>
  <div class="stat-card"><div class="stat-label">Aktivitas</div><div class="stat-value"><?= $histCount ?></div></div>
  <div class="stat-card"><div class="stat-label">Storage</div><div class="stat-value"><?= e(fmt_bytes($usage['bytes'])) ?></div></div>
</div>
<div class="toolbar">
  <div class="search-box"><input type="text" id="searchInput" placeholder="Cari di vault kamu... (Ctrl+K)"></div>
  <select class="sort-select" id="sortSelect">
    <option value="newest">Terbaru</option><option value="oldest">Terlama</option>
    <option value="az">Judul A–Z</option><option value="za">Judul Z–A</option>
  </select>
  <a class="btn btn-primary" href="<?= e(page_url(['new'=>1])) ?>"><span>+</span><span>Tambah Script</span></a>
</div>
<?php if ($langCounts): ?>
<div class="lang-chips">
  <a class="chip <?= $langFilter===''?'active':'' ?>" href="?page=scripts">Semua</a>
  <?php foreach ($langCounts as $l): ?>
    <a class="chip <?= $langFilter===$l['language']?'active':'' ?>" href="<?= e(page_url(['lang'=>$l['language']])) ?>">
      <?= lang_icon($l['language']) ?> <?= e($l['language']) ?> <span class="count"><?= (int)$l['c'] ?></span></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="scripts-grid" id="scriptsGrid">
<?php if (!$scripts): ?>
  <div class="empty-state" style="grid-column:1/-1"><div class="empty-icon">🔐</div>
    <div class="empty-title">Vault kamu masih kosong</div>
    <p>Script disimpan di MySQL, hanya akun kamu yang bisa melihat.<br>Punya backup? Settings → Import Vault.</p>
    <a class="btn btn-primary" href="<?= e(page_url(['new'=>1])) ?>"><span>+</span><span>Tambah Script Pertama</span></a></div>
<?php else: foreach ($scripts as $s): ?>
  <div class="script-card" data-title="<?= e(mb_strtolower($s['title'])) ?>" data-desc="<?= e(mb_strtolower($s['description'])) ?>"
       data-tags="<?= e(mb_strtolower($s['tags'])) ?>" data-updated="<?= e($s['updated_at']) ?>" data-created="<?= e($s['created_at']) ?>">
    <div class="script-header">
      <div style="flex:1;min-width:0"><div class="script-title"><?= e($s['title']) ?></div>
        <div class="script-desc"><?= e($s['description'] ?: 'Tidak ada deskripsi') ?></div></div>
      <span class="script-lang"><?= lang_icon($s['language']) ?> <?= e($s['language']) ?></span>
    </div>
    <div class="script-code-preview"><?= e(mb_substr($s['code'],0,220)) ?></div>
    <textarea hidden class="card-code"><?= e($s['code']) ?></textarea>
    <div class="script-footer"><span><?= e(time_ago($s['updated_at'])) ?></span>
      <div class="script-actions">
        <a class="icon-btn" href="<?= e(page_url(['view'=>$s['id']])) ?>">👁️</a>
        <?php if (previewable($s['language'])): ?><a class="icon-btn" href="<?= e(page_url(['preview'=>$s['id']])) ?>">▶️</a><?php endif; ?>
        <button class="icon-btn" type="button" data-copy-card>📋</button>
        <a class="icon-btn" href="<?= e(page_url(['edit'=>$s['id']])) ?>">✏️</a>
        <form method="post" action="?page=scripts" data-confirm="Hapus script &quot;<?= e($s['title']) ?>&quot;?" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_script"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <button class="icon-btn danger" type="submit">🗑️</button></form>
      </div>
    </div>
  </div>
<?php endforeach; endif; ?>
</div>

<div class="modal-overlay <?= ($edit||$isNew)?'is-open':'' ?>" id="scriptModal"><div class="modal">
  <div class="modal-header"><h2 class="modal-title"><?= $edit?'Edit Script':'Tambah Script Baru' ?></h2><a class="icon-btn" href="<?= e(page_url()) ?>">✕</a></div>
  <form method="post" action="?page=scripts"><?= csrf_field() ?>
    <input type="hidden" name="action" value="save_script"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Judul</label><input class="form-input" name="title" maxlength="200" required value="<?= e($edit['title'] ?? '') ?>"></div>
      <div class="form-group"><label class="form-label">Deskripsi</label><input class="form-input" name="description" maxlength="500" value="<?= e($edit['description'] ?? '') ?>"></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Bahasa</label>
          <select class="form-select" name="language"><?php foreach (languages() as $l): ?>
            <option value="<?= e($l) ?>" <?= (($edit['language'] ?? 'javascript')===$l)?'selected':'' ?>><?= e($l) ?></option><?php endforeach; ?></select>
          <div class="form-hint">Preview live hanya HTML/CSS/JS.</div></div>
        <div class="form-group"><label class="form-label">Tags (koma)</label><input class="form-input" name="tags" value="<?= e($edit['tags'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Kode Script</label><textarea class="form-textarea" name="code" required><?= e($edit['code'] ?? '') ?></textarea></div>
    </div>
    <div class="modal-footer"><a class="btn btn-ghost" href="<?= e(page_url()) ?>">Batal</a><button class="btn btn-primary" type="submit">Simpan ke Vault</button></div>
  </form>
</div></div>

<div class="modal-overlay <?= $view?'is-open':'' ?>" id="viewModal"><div class="modal">
  <div class="modal-header"><div><h2 class="modal-title"><?= e($view['title'] ?? '') ?></h2>
    <p style="font-size:13px;color:var(--text-muted);margin-top:4px"><?= e($view['language'] ?? '') ?> • <?= e(time_ago($view['updated_at'] ?? null)) ?> • 🔒 <?= e($user['username']) ?></p></div>
    <a class="icon-btn" href="<?= e(page_url()) ?>">✕</a></div>
  <div class="modal-body"><div class="view-code"><pre><code id="viewCode" class="language-<?= e($view['language'] ?? 'plaintext') ?>"><?= e($view['code'] ?? '') ?></code></pre></div></div>
  <div class="modal-footer">
    <button class="btn btn-ghost" type="button" data-copy-target="#viewCode">📋 Copy Code</button>
    <?php if ($view && previewable($view['language'])): ?><a class="btn btn-ghost" href="<?= e(page_url(['preview'=>$view['id']])) ?>">▶️ Preview</a><?php endif; ?>
    <?php if ($view): ?><a class="btn btn-primary" href="<?= e(page_url(['edit'=>$view['id']])) ?>">✏️ Edit</a><?php endif; ?>
  </div>
</div></div>

<div class="modal-overlay <?= $prev?'is-open':'' ?>" id="previewModal"><div class="modal" style="max-width:960px">
  <div class="modal-header"><div><h2 class="modal-title">▶️ Preview: <?= e($prev['title'] ?? '') ?></h2>
    <p style="font-size:12px;color:var(--text-muted);margin-top:4px">Sandbox: tanpa cookie/session, tanpa network, tanpa popup.</p></div>
    <a class="icon-btn" href="<?= e(page_url()) ?>">✕</a></div>
  <div class="modal-body"><?php if ($prev): ?>
    <div class="preview-frame"><iframe src="preview.php?id=<?= (int)$prev['id'] ?>" sandbox="allow-scripts" referrerpolicy="no-referrer" title="preview"></iframe></div>
    <div class="vault-note" style="margin-top:14px"><span>🛡️</span><span>Kode berjalan di iframe terisolasi. PHP/SQL tidak pernah dieksekusi server.</span></div>
  <?php endif; ?></div>
</div></div>