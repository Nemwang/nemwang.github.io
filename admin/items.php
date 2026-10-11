<?php
// Lists, adds, edits, reorders, hides and deletes the rows of one collection
// from cms/schema.php (?c=menu, social, skills, gallery, projects, ...).
require __DIR__ . '/_init.php';
require_login();

$key = (string) ($_GET['c'] ?? '');
$collection = schema()['collections'][$key] ?? null;
if (!$collection) {
    http_response_code(404);
    exit('Unknown content type.');
}
$self = 'items.php?c=' . $key;
$fields = $collection['fields'];
$pdo = db();

$navTitle = '';
$pageUrl = '../';
foreach (schema()['nav'] as $heading => $navGroup) {
    if (in_array(['collection', $key], $navGroup['entries'], true)) {
        [$navTitle, $pageUrl] = [$heading, $navGroup['url']];
    }
}

$rows = items($key, false);
$byId = array_column($rows, null, 'id');

// Fields marked "suggest" offer the values already used, e.g. existing project categories.
foreach ($fields as $name => $field) {
    if (!empty($field['suggest'])) {
        $used = array_filter(array_map('trim', array_column($rows, $name)), 'strlen');
        $fields[$name]['suggestions'] = array_values(array_unique($used));
    }
}

$editing = isset($_GET['new']) || isset($_GET['id']);
$id = (int) ($_GET['id'] ?? 0);
if ($id && !isset($byId[$id])) {
    flash('That entry no longer exists.', 'error');
    redirect($self);
}
$values = $id ? $byId[$id] : array_map(fn($field) => (string) ($field['default'] ?? ''), $fields);
$visible = $id ? (bool) $byId[$id]['visible'] : true;

if (is_post()) {
    $action = (string) ($_POST['action'] ?? '');
    $target = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        try {
            $data = [];
            foreach ($fields as $name => $field) {
                $data[$name] = read_field($name, $field);
            }
            $values = $data;
            $visible = isset($_POST['visible']);
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($id) {
                $pdo->prepare('UPDATE items SET data = ?, visible = ? WHERE id = ? AND collection = ?')
                    ->execute([$json, (int) $visible, $id, $key]);
            } else {
                $last = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM items WHERE collection = ?');
                $last->execute([$key]);
                $pdo->prepare('INSERT INTO items (collection, data, sort_order, visible) VALUES (?, ?, ?, ?)')
                    ->execute([$key, $json, (int) $last->fetchColumn(), (int) $visible]);
            }
            flash(ucfirst($collection['singular']) . ' saved.');
            redirect($self);
        } catch (RuntimeException $e) {
            flash($e->getMessage(), 'error');
        }
    } elseif (isset($byId[$target])) {
        if ($action === 'delete') {
            $pdo->prepare('DELETE FROM items WHERE id = ? AND collection = ?')->execute([$target, $key]);
            flash(ucfirst($collection['singular']) . ' deleted.');
        } elseif ($action === 'toggle') {
            $pdo->prepare('UPDATE items SET visible = 1 - visible WHERE id = ? AND collection = ?')->execute([$target, $key]);
        } elseif ($action === 'up' || $action === 'down') {
            // Swap with the neighbour, then renumber the whole list.
            $order = array_column($rows, 'id');
            $from = array_search($target, $order, true);
            $to = $from + ($action === 'up' ? -1 : 1);
            if (isset($order[$to])) {
                [$order[$from], $order[$to]] = [$order[$to], $order[$from]];
                $move = $pdo->prepare('UPDATE items SET sort_order = ? WHERE id = ?');
                foreach ($order as $position => $itemId) {
                    $move->execute([$position, $itemId]);
                }
            }
        }
        redirect($self . '#row' . $target);
    } else {
        redirect($self);
    }
}

$display = function (string $fieldName, array $row) use ($fields): string {
    $value = $row[$fieldName] ?? '';
    if (($fields[$fieldName]['type'] ?? '') === 'select') {
        $value = $fields[$fieldName]['options'][$value] ?? $value;
    }
    $value = mb_strimwidth(str_replace("\n", ' ', (string) $value), 0, 90, '…');
    return $value !== '' ? $value . ($fields[$fieldName]['suffix'] ?? '') : '';
};

// Up, down, show/hide, edit and delete for one row.
$rowActions = function (array $row, int $index) use ($self, $collection, $rows): string {
    $singular = e($collection['singular']);
    return '<form method="post" class="row-actions">' . csrf_field()
        . '<input type="hidden" name="id" value="' . $row['id'] . '">'
        . '<button class="icon-button" name="action" value="up" title="Move up" aria-label="Move up"' . ($index === 0 ? ' disabled' : '') . '>' . icon('up', 16) . '</button>'
        . '<button class="icon-button" name="action" value="down" title="Move down" aria-label="Move down"' . ($index === count($rows) - 1 ? ' disabled' : '') . '>' . icon('down', 16) . '</button>'
        . '<button class="icon-button" name="action" value="toggle" title="' . ($row['visible'] ? 'Hide from site' : 'Show on site') . '" aria-label="' . ($row['visible'] ? 'Hide' : 'Show') . '">' . icon($row['visible'] ? 'eye' : 'eye-off', 16) . '</button>'
        . '<a class="icon-button" href="' . $self . '&amp;id=' . $row['id'] . '" title="Edit" aria-label="Edit">' . icon('pencil', 16) . '</a>'
        . '<button class="icon-button danger" name="action" value="delete" title="Delete" aria-label="Delete" data-confirm="Delete this ' . $singular . '? This cannot be undone.">' . icon('trash', 16) . '</button>'
        . '</form>';
};

if ($editing):
    admin_header(($id ? 'Edit ' : 'New ') . $collection['singular'], 'collection:' . $key, [
        'crumb' => $collection['label'],
        'back' => $self,
    ]); ?>
    <form method="post" enctype="multipart/form-data" class="edit-form" data-form>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <section class="card section-card<?= $visible ? '' : ' is-off' ?>">
        <header class="card-head">
          <h2>Details</h2>
          <label class="switch-inline">
            <span data-switch-text data-on="Shown on site" data-off="Hidden"><?= $visible ? 'Shown on site' : 'Hidden' ?></span>
            <?= switch_html('visible', $visible, ' data-section-toggle') ?>
          </label>
        </header>
        <div class="card-body field-grid">
<?php foreach ($fields as $name => $field) {
    render_field($name, $field, (string) ($values[$name] ?? ''));
} ?>
        </div>
      </section>
      <?= save_bar($self) ?>
    </form>
<?php else:
    $hiddenCount = count(array_filter($rows, fn($row) => !$row['visible']));
    admin_header($collection['label'], 'collection:' . $key, [
        'crumb' => $navTitle,
        'description' => $collection['description'] ?? '',
        'actions' => '<a class="button ghost" href="' . e($pageUrl) . '" target="_blank" rel="noopener">' . icon('external', 16) . '<span>View page</span></a>'
            . '<a class="button primary" href="' . $self . '&amp;new=1">' . icon('plus', 16) . '<span>Add ' . e($collection['singular']) . '</span></a>',
    ]); ?>
<?php if (!$rows): ?>
    <div class="card empty">
      <?= icon($collection['icon'] ?? 'list', 28) ?>
      <p>No <?= e(strtolower($collection['label'])) ?> yet.</p>
      <a class="button primary" href="<?= $self ?>&amp;new=1"><?= icon('plus', 16) ?><span>Add the first <?= e($collection['singular']) ?></span></a>
    </div>
<?php else: ?>
    <p class="list-summary"><?= count($rows) ?> <?= e(count($rows) === 1 ? $collection['singular'] : plural($collection['singular'])) ?><?= $hiddenCount ? ' · ' . $hiddenCount . ' hidden' : '' ?></p>
<?php if (($collection['view'] ?? '') === 'grid'): ?>
    <div class="tile-grid">
<?php foreach ($rows as $index => $row): $thumb = $row[$collection['thumb']]; ?>
      <div class="tile<?= $row['visible'] ? '' : ' is-hidden' ?>" id="row<?= $row['id'] ?>">
        <a class="tile-image" href="<?= $self ?>&amp;id=<?= $row['id'] ?>">
          <?= $thumb !== '' ? '<img src="' . e(asset_url(img($thumb, 400))) . '" alt="" loading="lazy">' : icon('image', 28) ?>
<?php if (!$row['visible']): ?><span class="pill">Hidden</span><?php endif; ?>
        </a>
        <div class="tile-foot">
          <a class="tile-title" href="<?= $self ?>&amp;id=<?= $row['id'] ?>"><?= e($display($collection['title'], $row)) ?: '(untitled)' ?></a>
          <?= $rowActions($row, $index) ?>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card list">
<?php foreach ($rows as $index => $row): ?>
      <div class="list-row<?= $row['visible'] ? '' : ' is-hidden' ?>" id="row<?= $row['id'] ?>">
        <span class="list-index"><?= $index + 1 ?></span>
<?php if (!empty($collection['thumb'])): $thumb = $row[$collection['thumb']]; ?>
        <span class="list-thumb"><?= $thumb !== '' ? '<img src="' . e(asset_url(img($thumb, 400))) . '" alt="" loading="lazy">' : icon('image', 18) ?></span>
<?php endif; ?>
        <a class="list-main" href="<?= $self ?>&amp;id=<?= $row['id'] ?>">
          <span class="list-title"><?= e($display($collection['title'], $row)) ?: '(untitled)' ?></span>
<?php if (!empty($collection['subtitle']) && $display($collection['subtitle'], $row) !== ''): ?>
          <span class="list-sub"><?= e($display($collection['subtitle'], $row)) ?></span>
<?php endif; ?>
        </a>
<?php if (!$row['visible']): ?>
        <span class="pill">Hidden</span>
<?php endif; ?>
        <?= $rowActions($row, $index) ?>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php admin_footer();
