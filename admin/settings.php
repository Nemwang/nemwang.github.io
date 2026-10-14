<?php
// Edits one group of settings from cms/schema.php (?g=general, layout, home, ...).
require __DIR__ . '/_init.php';
require_login();

$key = (string) ($_GET['g'] ?? '');
$group = schema()['settings'][$key] ?? null;
if (!$group) {
    http_response_code(404);
    exit('Unknown settings page.');
}

$navTitle = '';
$pageUrl = '../';
foreach (schema()['nav'] as $heading => $navGroup) {
    if (in_array(['settings', $key], $navGroup['entries'], true)) {
        [$navTitle, $pageUrl] = [$heading, $navGroup['url']];
    }
}

$values = [];
foreach ($group['fields'] as $name => $field) {
    if ($field['type'] !== 'heading') {
        $values[$name] = s($name);
    }
}

if (is_post()) {
    try {
        foreach ($group['fields'] as $name => $field) {
            if ($field['type'] !== 'heading') {
                $values[$name] = read_field($name, $field);
            }
        }
        $save = db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        foreach ($values as $name => $value) {
            $save->execute([$name, $value]);
        }
        flash('Your changes are saved and live on the site.');
        redirect('settings.php?g=' . $key);
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
}

// One card per heading. A "..._enabled" switch right after a heading moves into the card's header.
$sections = [];
foreach ($group['fields'] as $name => $field) {
    if ($field['type'] === 'heading' || !$sections) {
        $title = $field['type'] === 'heading' ? $field['label'] : $group['label'];
        $sections[] = ['title' => $title, 'id' => 's' . count($sections), 'toggle' => null, 'fields' => []];
        if ($field['type'] === 'heading') {
            continue;
        }
    }
    $section = &$sections[count($sections) - 1];
    if (!$section['fields'] && !$section['toggle'] && $field['type'] === 'bool' && str_ends_with($name, '_enabled')) {
        $section['toggle'] = [$name, $field];
    } else {
        $section['fields'][$name] = $field;
    }
    unset($section);
}

admin_header($group['label'], 'settings:' . $key, [
    'crumb' => $navTitle,
    'description' => $group['description'] ?? '',
    'actions' => $key === 'resumepdf'
        ? '<a class="button ghost" href="../resume.php?download=1">' . icon('upload', 16) . '<span>Download</span></a>'
            . '<a class="button primary" href="../resume.php" target="_blank" rel="noopener">' . icon('file-text', 16) . '<span>Preview PDF</span></a>'
        : '<a class="button ghost" href="' . e($pageUrl) . '" target="_blank" rel="noopener">' . icon('external', 16) . '<span>View page</span></a>',
]);
?>
    <form method="post" enctype="multipart/form-data" class="edit-form" data-form>
      <?= csrf_field() ?>
<?php if (count($sections) > 2): ?>
      <nav class="section-nav" aria-label="Sections">
<?php foreach ($sections as $section): ?>
        <a href="#<?= $section['id'] ?>"><?= e($section['title']) ?></a>
<?php endforeach; ?>
      </nav>
<?php endif; ?>
<?php foreach ($sections as $section):
    $toggle = $section['toggle'];
    $isOn = $toggle ? $values[$toggle[0]] === '1' : true;
    $pageToggle = $toggle && $toggle[0] === $key . '_enabled'; ?>
      <section class="card section-card<?= $isOn ? '' : ' is-off' ?>" id="<?= $section['id'] ?>">
        <header class="card-head">
          <h2><?= e($section['title']) ?></h2>
<?php if ($toggle): ?>
          <label class="switch-inline" title="<?= e($toggle[1]['label']) ?>">
            <span data-switch-text data-on="<?= $pageToggle ? 'Online' : 'Shown on site' ?>" data-off="<?= $pageToggle ? 'Offline' : 'Hidden' ?>"><?= $isOn ? ($pageToggle ? 'Online' : 'Shown on site') : ($pageToggle ? 'Offline' : 'Hidden') ?></span>
            <?= switch_html($toggle[0], $isOn, ' data-section-toggle') ?>
          </label>
<?php endif; ?>
        </header>
<?php if ($section['fields']): ?>
        <div class="card-body field-grid">
<?php foreach ($section['fields'] as $name => $field) {
        render_field($name, $field, $values[$name] ?? '');
    } ?>
        </div>
<?php endif; ?>
      </section>
<?php endforeach; ?>
      <?= save_bar() ?>
    </form>
<?php admin_footer();
