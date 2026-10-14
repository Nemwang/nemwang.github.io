<?php
// Loaded first by every admin page: session, login check, CSRF protection,
// page layout, icons and the form fields generated from cms/schema.php.

require __DIR__ . '/../cms/bootstrap.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('portfolio_admin');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/* ---------------------------------------------------------------------------
   "Remember me": a 30-day cookie of "selector:secret". Only a hash of the secret
   is stored, and the secret changes every time the cookie is used.
--------------------------------------------------------------------------- */

const REMEMBER_COOKIE = 'portfolio_remember';
const REMEMBER_DAYS = 30;

function remember_cookie(string $value, int $expires): void
{
    setcookie(REMEMBER_COOKIE, $value, [
        'expires' => $expires,
        'path' => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/x')), '/') . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

function remember_login(int $userId): void
{
    $selector = bin2hex(random_bytes(12));
    $secret = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO remember_tokens (selector, validator_hash, user_id, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ' . REMEMBER_DAYS . ' DAY)')
        ->execute([$selector, hash('sha256', $secret), $userId]);
    remember_cookie($selector . ':' . $secret, time() + REMEMBER_DAYS * 86400);
}

// Forget this device (log out).
function forget_login(): void
{
    $cookie = (string) ($_COOKIE[REMEMBER_COOKIE] ?? '');
    if ($cookie !== '') {
        db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([strstr($cookie, ':', true) ?: $cookie]);
        remember_cookie('', time() - 3600);
    }
}

// Forget every device of a user (after a password change or reset).
function forget_all_logins(int $userId): void
{
    db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
    remember_cookie('', time() - 3600);
}

// Logs a remembered device back in when its session has ended.
function remember_restore(): void
{
    $cookie = (string) ($_COOKIE[REMEMBER_COOKIE] ?? '');
    if (!empty($_SESSION['user_id']) || !str_contains($cookie, ':')) {
        return;
    }
    [$selector, $secret] = explode(':', $cookie, 2);
    $pdo = db();
    $pdo->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');
    $find = $pdo->prepare('SELECT user_id, validator_hash FROM remember_tokens WHERE selector = ?');
    $find->execute([$selector]);
    $token = $find->fetch();

    if (!$token) {
        remember_cookie('', time() - 3600);
        return;
    }
    if (!hash_equals($token['validator_hash'], hash('sha256', $secret))) {
        // A right selector with a wrong secret means the cookie was copied: sign out everywhere.
        forget_all_logins((int) $token['user_id']);
        return;
    }
    $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $token['user_id'];
    remember_login((int) $token['user_id']);
}

try {
    if (cms_installed()) {
        cms_upgrade();
        remember_restore();
    }
} catch (Throwable $e) {
    // Database not set up yet: the install page takes care of it.
}

// Address of the admin panel for links in emails. Order of trust: site_url in cms/config.php,
// then the address you last logged in at, then (first use only) the address of this request.
// Using a saved address stops a forged request from pointing a reset link at another website.
function admin_url(string $page, bool $forEmail = false): string
{
    $configured = cms_config()['site_url'] ?? '';
    if ($configured !== '') {
        return rtrim($configured, '/') . '/admin/' . $page;
    }
    if ($forEmail && s('admin_address') !== '') {
        return s('admin_address') . $page;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = preg_replace('/[^a-z0-9.\-:\[\]]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/x')), '/') . '/' . $page;
}

// Called after a successful login: saves where the admin panel is, for reset links.
function remember_admin_address(): void
{
    db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)')
        ->execute(['admin_address', admin_url('')]);
}

// True only when you are using the site on your own computer (http://localhost/...).
function is_local_request(): bool
{
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect(cms_installed() ? 'login.php' : 'install.php');
    }
}

function current_username(): string
{
    static $name;
    if ($name === null) {
        $find = db()->prepare('SELECT username FROM users WHERE id = ?');
        $find->execute([$_SESSION['user_id'] ?? 0]);
        $name = (string) $find->fetchColumn();
    }
    return $name;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function is_post(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('This form has expired. Go back, reload the page and try again.');
    }
    return true;
}

function flash(string $message, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function unread_messages(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
}

// "skill box" => "skill boxes", "resume entry" => "resume entries".
function plural(string $word): string
{
    if (preg_match('/[^aeiou]y$/', $word)) {
        return substr($word, 0, -1) . 'ies';
    }
    return $word . (preg_match('/(s|x|ch|sh)$/', $word) ? 'es' : 's');
}

function time_ago(string $datetime): string
{
    $seconds = time() - strtotime($datetime);
    if ($seconds < 60) {
        return 'just now';
    }
    foreach ([86400 * 7 => null, 86400 => 'day', 3600 => 'hour', 60 => 'minute'] as $unit => $name) {
        if ($seconds >= $unit) {
            if ($name === null) {
                return date('j M Y', strtotime($datetime));
            }
            $n = intdiv($seconds, $unit);
            return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return '';
}

/* ---------------------------------------------------------------------------
   Icons (outline style, drawn with currentColor)
--------------------------------------------------------------------------- */

function icon(string $name, int $size = 18): string
{
    static $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'id-card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8.5" cy="11" r="2.2"/><path d="M5 16.5c.6-1.5 2-2.3 3.5-2.3s2.9.8 3.5 2.3M15 10h4M15 14h3"/>',
        'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 16h18"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V13h6v9"/>',
        'bars' => '<path d="M3 3v18h18"/><path d="M7 16h7M7 11h11M7 6h4"/>',
        'code' => '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M2 13h20"/>',
        'file-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h6M16 13H8M16 17H8M10 9H8"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
        'folder' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9l-.8-1.2A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
        'settings' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'external' => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'up' => '<path d="m18 15-6-6-6 6"/>',
        'down' => '<path d="m6 9 6 6 6-6"/>',
        'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M9.9 9.9a3 3 0 1 0 4.2 4.2M10.7 5.1A10.4 10.4 0 0 1 12 5c7 0 10 7 10 7a13 13 0 0 1-1.7 2.7M6.6 6.6A13.5 13.5 0 0 0 2 12s3 7 10 7a9.7 9.7 0 0 0 5.4-1.6M2 2l20 20"/>',
        'pencil' => '<path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
        'trash' => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        'back' => '<path d="m15 18-6-6 6-6"/>',
        'reply' => '<path d="M9 17 4 12l5-5"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    ];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['dashboard']) . '</svg>';
}

/* ---------------------------------------------------------------------------
   Layout
--------------------------------------------------------------------------- */

function nav_link(string $href, string $iconName, string $label, bool $active, string $extra = ''): string
{
    return '<a href="' . $href . '"' . ($active ? ' class="active" aria-current="page"' : '') . '>'
        . icon($iconName) . '<span>' . e($label) . '</span>' . $extra . "</a>\n";
}

function brand_mark(): string
{
    $logo = s('logo');
    if ($logo === '') {
        return '<span class="brand-mark">' . e(mb_substr(s('site_name'), 0, 1)) . '</span>';
    }
    $url = preg_match('#^(https?:)?//#', $logo) ? $logo : '../' . $logo;
    return '<span class="brand-mark"><img src="' . e($url) . '" alt=""></span>';
}

// $active is "dashboard", "messages", "account" or "<kind>:<key>" from the schema nav.
// $opt: crumb, description, actions (html for the right side of the title), back (url).
function admin_header(string $title, string $active = '', array $opt = []): void
{
    $loggedIn = !empty($_SESSION['user_id']);
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Admin</title>
<link rel="stylesheet" href="admin.css">
<script src="admin.js" defer></script>
</head>
<?php if (!$loggedIn): ?>
<body class="auth">
<main class="auth-box">
  <div class="auth-brand"><?php try { echo brand_mark(); } catch (Throwable $e) { echo '<span class="brand-mark">A</span>'; } ?></div>
  <h1><?= e($title) ?></h1>
<?php if (!empty($opt['description'])): ?>
  <p class="lead"><?= e($opt['description']) ?></p>
<?php endif; ?>
<?php else: $unread = unread_messages(); ?>
<body>
<input type="checkbox" id="nav-toggle" class="nav-toggle" hidden>
<aside class="sidebar">
  <a class="brand" href="index.php">
    <?= brand_mark() ?>
    <span class="brand-text"><strong><?= e(s('site_name')) ?></strong><small>Admin panel</small></span>
  </a>
  <nav class="nav">
    <?= nav_link('index.php', 'dashboard', 'Dashboard', $active === 'dashboard') ?>
    <?= nav_link('messages.php', 'inbox', 'Messages', $active === 'messages', $unread ? '<span class="nav-badge">' . $unread . '</span>' : '') ?>
<?php foreach (schema()['nav'] as $heading => $group): ?>
    <div class="nav-heading"><?= e($heading) ?></div>
<?php foreach ($group['entries'] as [$kind, $key]):
        $def = schema()[$kind === 'settings' ? 'settings' : 'collections'][$key];
        $href = $kind === 'settings' ? "settings.php?g=$key" : "items.php?c=$key";
        echo nav_link($href, $def['icon'] ?? 'dashboard', $def['label'], $active === "$kind:$key");
    endforeach; ?>
<?php endforeach; ?>
  </nav>
  <div class="sidebar-user">
    <span class="avatar"><?= e(mb_strtoupper(mb_substr(current_username(), 0, 1))) ?></span>
    <a class="user-name" href="account.php" title="Username & password"><?= e(current_username()) ?><small>Account settings</small></a>
    <form method="post" action="logout.php"><?= csrf_field() ?><button class="icon-button" title="Log out" aria-label="Log out"><?= icon('logout') ?></button></form>
  </div>
</aside>
<label for="nav-toggle" class="sidebar-backdrop" aria-hidden="true"></label>
<div class="shell">
  <header class="topbar">
    <label for="nav-toggle" class="icon-button menu-button" aria-label="Open menu"><?= icon('menu') ?></label>
    <span class="topbar-title"><?= e(s('site_name')) ?></span>
    <a class="button ghost" href="../" target="_blank" rel="noopener"><?= icon('external', 16) ?><span>View site</span></a>
  </header>
  <main class="content">
    <div class="page-head">
      <div>
<?php if (!empty($opt['back'])): ?>
        <a class="back-link" href="<?= e($opt['back']) ?>"><?= icon('back', 16) ?><?= e($opt['crumb'] ?? 'Back') ?></a>
<?php elseif (!empty($opt['crumb'])): ?>
        <div class="crumb"><?= e($opt['crumb']) ?></div>
<?php endif; ?>
        <h1><?= e($title) ?></h1>
<?php if (!empty($opt['description'])): ?>
        <p class="lead"><?= e($opt['description']) ?></p>
<?php endif; ?>
      </div>
<?php if (!empty($opt['actions'])): ?>
      <div class="page-actions"><?= $opt['actions'] ?></div>
<?php endif; ?>
    </div>
<?php endif; ?>
<?php foreach ($flashes as [$type, $message]): ?>
    <div class="flash <?= e($type) ?>" role="status"><?= icon($type === 'ok' ? 'check' : 'alert', 18) ?><span><?= e($message) ?></span></div>
<?php endforeach; ?>
    <?php
}

function admin_footer(): void
{
    if (!empty($_SESSION['user_id'])) {
        echo "  </main>\n</div>\n";
    } else {
        echo "</main>\n";
    }
    echo "</body>\n</html>\n";
}

/* ---------------------------------------------------------------------------
   Form fields
--------------------------------------------------------------------------- */

// Short inputs share a row; long ones take the full width of the form.
function field_is_wide(array $field): bool
{
    return in_array($field['type'], ['textarea', 'image', 'file', 'bool', 'checklist'], true)
        || !empty($field['wide'])
        || strlen($field['help'] ?? '') > 70;
}

function switch_html(string $name, bool $checked, string $extra = ''): string
{
    return '<span class="switch"><input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . $extra . '><span class="slider"></span></span>';
}

function asset_url(string $path): string
{
    return preg_match('#^(https?:)?//#', $path) ? $path : '../' . $path;
}

function render_field(string $name, array $field, string $value): void
{
    $id = 'f_' . $name;
    $type = $field['type'];
    $help = !empty($field['help']) ? '<p class="help">' . e($field['help']) . '</p>' : '';

    echo '<div class="field' . (field_is_wide($field) ? ' wide' : '') . ' field-' . $type . '">';

    if ($type === 'bool') {
        echo '<label class="switch-row">' . switch_html($name, $value === '1')
            . '<span><span class="switch-label">' . e($field['label']) . '</span>' . $help . '</span></label></div>' . "\n";
        return;
    }

    echo '<label class="label" for="' . $id . '">' . e($field['label']) . '</label>';

    if ($type === 'text') {
        $suggestions = $field['suggestions'] ?? [];
        echo '<input type="text" id="' . $id . '" name="' . $name . '" value="' . e($value) . '"' . ($suggestions ? ' list="' . $id . '_list" autocomplete="off"' : '') . '>';
        if ($suggestions) {
            echo '<datalist id="' . $id . '_list">';
            foreach ($suggestions as $suggestion) {
                echo '<option value="' . e($suggestion) . '">';
            }
            echo '</datalist>';
        }
    } elseif ($type === 'number') {
        echo '<input type="number" id="' . $id . '" name="' . $name . '" value="' . e($value) . '"'
            . (isset($field['min']) ? ' min="' . (int) $field['min'] . '"' : '')
            . (isset($field['max']) ? ' max="' . (int) $field['max'] . '"' : '') . '>';
    } elseif ($type === 'textarea') {
        $rows = max(3, min(8, (int) ceil(mb_strlen($value) / 80) + substr_count($value, "\n") + 1));
        echo '<textarea id="' . $id . '" name="' . $name . '" rows="' . $rows . '">' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select id="' . $id . '" name="' . $name . '">';
        foreach ($field['options'] as $key => $label) {
            echo '<option value="' . e((string) $key) . '"' . ((string) $key === $value ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'checklist') {
        $chosen = array_filter(explode(',', $value), 'strlen');
        $options = checklist_options($field);
        echo '<div class="checklist" id="' . $id . '">';
        if (!$options) {
            echo '<p class="help">Nothing to choose from yet.</p>';
        }
        foreach ($options as $optionId => $optionLabel) {
            echo '<label class="check"><input type="checkbox" name="' . $name . '[]" value="' . (int) $optionId . '"'
                . (in_array((string) $optionId, $chosen, true) ? ' checked' : '') . '> ' . e($optionLabel) . '</label>';
        }
        echo '</div>';
    } elseif ($type === 'image' || $type === 'file') {
        render_media_field($id, $name, $type, $value);
    }

    echo $help . "</div>\n";
}

function render_media_field(string $id, string $name, string $type, string $value): void
{
    $isImage = $type === 'image';
    $thumb = $value === ''
        ? icon($isImage ? 'image' : 'file-text', 26)
        : ($isImage
            ? '<img src="' . e(asset_url(img($value, 400))) . '" alt="">'
            : '<a href="' . e(asset_url($value)) . '" target="_blank" rel="noopener" title="Open current file">' . icon('file-text', 26) . '<span>PDF</span></a>');
    ?>
<div class="media" data-media="<?= $type ?>">
  <div class="media-thumb<?= $value === '' ? ' is-empty' : '' ?>" data-thumb><?= $thumb ?></div>
  <div class="media-body">
    <div class="media-buttons">
      <label class="button">
        <?= icon('upload', 16) ?><span><?= $value === '' ? 'Upload' : 'Replace' ?></span>
        <input type="file" class="visually-hidden" name="upload_<?= e($name) ?>" accept="<?= $isImage ? 'image/jpeg,image/png,image/gif,image/webp,image/x-icon' : 'application/pdf' ?>" data-file>
      </label>
      <button type="button" class="button ghost danger-text" data-clear<?= $value === '' ? ' hidden' : '' ?>><?= icon('x', 16) ?><span>Remove</span></button>
      <span class="media-note" data-note></span>
    </div>
    <input type="text" class="input-sm" id="<?= $id ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="<?= $isImage ? 'images/photo.jpg' : 'file/cv.pdf' ?>" aria-label="File path" data-path>
    <p class="help"><?= $isImage ? 'JPG, PNG, GIF, WebP or ICO.' : 'PDF only.' ?> Or type the path of a file already on the site.</p>
  </div>
</div>
    <?php
}

// The sticky bar at the bottom of every edit form.
function save_bar(string $cancelUrl = ''): string
{
    return '<div class="savebar"><span class="savebar-status" data-status>No unsaved changes</span>'
        . ($cancelUrl ? '<a class="button ghost" href="' . e($cancelUrl) . '">Cancel</a>' : '')
        . '<button class="button primary">' . icon('check', 16) . '<span>Save changes</span></button></div>';
}

// Value of one field from the submitted form. Throws RuntimeException with a
// readable message if an upload is rejected.
function read_field(string $name, array $field): string
{
    $raw = is_array($_POST[$name] ?? null) ? '' : (string) ($_POST[$name] ?? '');

    switch ($field['type']) {
        case 'bool':
            return isset($_POST[$name]) ? '1' : '0';
        case 'number':
            $number = (int) $raw;
            if (isset($field['min'])) {
                $number = max((int) $field['min'], $number);
            }
            if (isset($field['max'])) {
                $number = min((int) $field['max'], $number);
            }
            return (string) $number;
        case 'select':
            return array_key_exists($raw, $field['options']) ? $raw : (string) ($field['default'] ?? '');
        case 'checklist':
            // Only ids that are really on offer, stored as "12,15,20".
            $valid = array_map('strval', array_keys(checklist_options($field)));
            $picked = array_map('strval', (array) ($_POST[$name] ?? []));
            return implode(',', array_values(array_intersect($valid, $picked)));
        case 'textarea':
            return trim(str_replace("\r\n", "\n", $raw));
        case 'image':
        case 'file':
            return save_upload('upload_' . $name, $field['type']) ?? trim($raw);
        default:
            return trim(preg_replace('/\s+/', ' ', $raw));
    }
}

// Stores an uploaded file under uploads/ and returns its path, or null if none was sent.
function save_upload(string $input, string $kind): ?string
{
    $file = $_FILES[$input] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('That file is too large for this server (limit ' . ini_get('upload_max_filesize') . ').');
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The upload failed, please try again.');
    }

    // Allowed extension => the content types the file may really contain.
    $allowed = $kind === 'image'
        ? [
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'gif' => ['image/gif'],
            'webp' => ['image/webp'], 'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
        ]
        : ['pdf' => ['application/pdf']];

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$extension]) || !in_array($mime, $allowed[$extension], true)) {
        throw new RuntimeException('"' . $file['name'] . '" is not an allowed file type (' . implode(', ', array_keys($allowed)) . ').');
    }

    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME))), '-') ?: 'file';
    $path = 'uploads/' . substr($base, 0, 60) . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], SITE_ROOT . '/' . $path)) {
        throw new RuntimeException('Could not save the file. Check that the uploads folder is writable.');
    }
    return $path;
}
