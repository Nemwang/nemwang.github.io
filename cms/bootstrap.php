<?php
// Shared setup for the public pages and the admin panel:
// database connection, settings, content collections and output helpers.

define('CMS_ROOT', __DIR__);
define('SITE_ROOT', dirname(__DIR__));

require CMS_ROOT . '/images.php';

function cms_config(): array
{
    static $config;
    if ($config === null) {
        $file = CMS_ROOT . '/config.php';
        $config = is_file($file) ? require $file : [];
    }
    return $config;
}

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $c = cms_config();
        if (!$c) {
            throw new RuntimeException('cms/config.php is missing.');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['db_host'] ?? 'localhost',
            $c['db_port'] ?? 3306,
            $c['db_name'] ?? ''
        );
        $pdo = new PDO($dsn, $c['db_user'] ?? '', $c['db_pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function cms_create_tables(): void
{
    $tables = [
        'CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(100) NOT NULL PRIMARY KEY,
            value MEDIUMTEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            collection VARCHAR(50) NOT NULL,
            data MEDIUMTEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            visible TINYINT(1) NOT NULL DEFAULT 1,
            KEY collection_order (collection, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(200) NULL,
            subject VARCHAR(200) NULL,
            message TEXT NOT NULL,
            ip VARCHAR(45) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            KEY ip_time (ip, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS login_attempts (
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL,
            KEY ip_time (ip, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    ];
    foreach ($tables as $sql) {
        db()->exec($sql);
    }
}

// True once the tables exist and an admin account has been created.
function cms_installed(): bool
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* ---------------------------------------------------------------------------
   Settings and collections (their definitions live in schema.php)
--------------------------------------------------------------------------- */

function schema(): array
{
    static $schema;
    if ($schema === null) {
        $schema = require CMS_ROOT . '/schema.php';
    }
    return $schema;
}

function setting_defaults(): array
{
    static $defaults;
    if ($defaults === null) {
        $defaults = [];
        foreach (schema()['settings'] as $group) {
            foreach ($group['fields'] as $name => $field) {
                if ($field['type'] !== 'heading') {
                    $defaults[$name] = (string) ($field['default'] ?? '');
                }
            }
        }
    }
    return $defaults;
}

function all_settings(bool $reload = false): array
{
    static $settings;
    if ($settings === null || $reload) {
        $settings = [];
        foreach (db()->query('SELECT name, value FROM settings') as $row) {
            $settings[$row['name']] = $row['value'];
        }
    }
    return $settings;
}

// One setting; falls back to the default from schema.php until it is saved.
function s(string $name): string
{
    $saved = all_settings();
    return $saved[$name] ?? setting_defaults()[$name] ?? '';
}

function on(string $name): bool
{
    return s($name) === '1';
}

// Rows of a collection in display order, each as [id, visible, field => value...].
function items(string $collection, bool $visibleOnly = true): array
{
    $sql = 'SELECT id, data, sort_order, visible FROM items WHERE collection = ?'
        . ($visibleOnly ? ' AND visible = 1' : '')
        . ' ORDER BY sort_order, id';
    $stmt = db()->prepare($sql);
    $stmt->execute([$collection]);

    $blank = [];
    foreach (schema()['collections'][$collection]['fields'] as $name => $field) {
        $blank[$name] = (string) ($field['default'] ?? '');
    }

    $rows = [];
    foreach ($stmt as $row) {
        $data = json_decode($row['data'], true) ?: [];
        $rows[] = ['id' => (int) $row['id'], 'visible' => (int) $row['visible']] + array_merge($blank, $data);
    }
    return $rows;
}

/* ---------------------------------------------------------------------------
   Output helpers
--------------------------------------------------------------------------- */

function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// "© Nemwang {year}" shows the current year, so the copyright never goes stale.
function with_year(string $text): string
{
    return str_replace('{year}', date('Y'), $text);
}

// Escaped text with line breaks kept.
function lines(?string $text): string
{
    return nl2br(e($text), false);
}

// Like lines(), and words wrapped in *stars* get the theme's highlight colour.
function hl(?string $text): string
{
    return preg_replace('/\*([^*\n]+)\*/', '<span class="text-primary">$1</span>', lines($text));
}

function social_networks(): array
{
    // key => [label, icon font class, iconify icon (for logos the icon font lacks)]
    return [
        'twitter' => ['Twitter / X', 'ion-social-twitter', ''],
        'facebook' => ['Facebook', 'ion-social-facebook', ''],
        'linkedin' => ['LinkedIn', 'ion-social-linkedin', ''],
        'github' => ['GitHub', 'ion-social-github-outline', ''],
        'dribbble' => ['Dribbble', 'ion-social-dribbble-outline', ''],
        'behance' => ['Behance', '', 'ion:logo-behance'],
        'instagram' => ['Instagram', 'ion-social-instagram-outline', ''],
        'youtube' => ['YouTube', 'ion-social-youtube', ''],
        'pinterest' => ['Pinterest', 'ion-social-pinterest', ''],
        'whatsapp' => ['WhatsApp', 'ion-social-whatsapp', ''],
        'tiktok' => ['TikTok', '', 'ion:logo-tiktok'],
        'website' => ['Website', 'ion-earth', ''],
        'email' => ['Email', 'ion-email', ''],
    ];
}

function social_links_html(): string
{
    $networks = social_networks();
    $html = '';
    foreach (items('social') as $link) {
        [$label, $class, $iconify] = $networks[$link['network']] ?? $networks['website'];
        $icon = $iconify ? '<iconify-icon icon="' . e($iconify) . '"></iconify-icon>' : '';
        $html .= '<a href="' . e($link['url']) . '" class="icon ' . e($class) . '" title="' . e($label)
            . '" aria-label="' . e($label) . '" target="_blank" rel="noopener me">' . $icon . "</a>\n";
    }
    return $html;
}

/* ---------------------------------------------------------------------------
   Public pages
--------------------------------------------------------------------------- */

function site_unavailable(int $status, string $title, string $text): void
{
    http_response_code($status);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . '</title></head>'
        . '<body style="font-family:sans-serif;text-align:center;padding:15vh 20px;color:#333">'
        . '<h1>' . e($title) . '</h1><p>' . $text . '</p></body></html>';
    exit;
}

// Call at the top of a public page. $page is "home", "ux" or "projects".
function site_boot(string $page): void
{
    try {
        all_settings();
    } catch (Throwable $e) {
        site_unavailable(503, 'Site is being set up', 'Finish the setup at <a href="admin/install.php">admin/install.php</a>.');
    }
    if (!on($page . '_enabled')) {
        site_unavailable(404, 'Page not found', '<a href="./">Back to the home page</a>');
    }
}

// Sections of the home page in display order: key => [pagepiling anchor, css class].
function home_sections(): array
{
    return [
        'intro' => ['page1', 'text-white section section-1'],
        'about' => ['page2', 'section section-2'],
        'skills' => ['page3', 'text-white section section-3'],
        'services' => ['page4', 'section section-4'],
        'work' => ['page7', 'text-white section section-work'],
        'resume' => ['page5', 'text-white section section-6'],
        'contact' => ['page6', 'section section-6'],
    ];
}

function enabled_home_sections(): array
{
    return array_filter(home_sections(), fn($key) => on($key . '_enabled'), ARRAY_FILTER_USE_KEY);
}

// Menu entries for one page. Links to switched-off home sections are left out.
function menu_for(string $page): array
{
    $anchors = array_column(enabled_home_sections(), 0);
    $menu = [];
    foreach (items('menu') as $item) {
        // "wordpress" is the old name of the Projects page.
        $where = $item['show_on'] === 'wordpress' ? 'projects' : $item['show_on'];
        $shown = $where === 'all' || $where === $page || ($where === 'inner' && $page !== 'home');
        if (!$shown) {
            continue;
        }
        $item['anchor'] = '';
        if (str_starts_with($item['url'], '#')) {
            $item['anchor'] = substr($item['url'], 1);
            if (!in_array($item['anchor'], $anchors, true)) {
                continue;
            }
        }
        $menu[] = $item;
    }
    return $menu;
}

/* ---------------------------------------------------------------------------
   Search engines and link previews
--------------------------------------------------------------------------- */

// Address of the site without a trailing slash, e.g. https://example.com
function site_url(): string
{
    $configured = cms_config()['site_url'] ?? '';
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^a-z0-9.\-:\[\]]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function absolute_url(string $path): string
{
    return preg_match('#^(https?:)?//#', $path) ? $path : site_url() . '/' . ltrim($path, '/');
}

function page_path(string $page): string
{
    return ['home' => '', 'ux' => 'ux.php', 'projects' => 'projects.php'][$page] ?? '';
}

function page_description(string $page): string
{
    $text = s($page . '_description') ?: s('meta_description');
    if ($text === '') {
        $text = s('about_text');
    }
    $text = trim(preg_replace('/\s+/', ' ', str_replace('*', '', $text)));
    return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157)) . '…' : $text;
}

function share_image(): string
{
    $image = s('share_image') ?: s('intro_bg');
    return $image === '' ? '' : absolute_url(img($image, 1200, 82, 'jpg'));
}

/* ---------------------------------------------------------------------------
   Projects
--------------------------------------------------------------------------- */

function slugify(string $text): string
{
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    return $slug !== '' ? $slug : 'project';
}

// Visible projects keyed by their address name (e.g. "ultra-led-display"), in display order.
function projects_by_slug(): array
{
    static $projects;
    if ($projects === null) {
        $projects = [];
        foreach (items('projects') as $project) {
            $slug = slugify($project['name']);
            if (isset($projects[$slug])) {
                $slug .= '-' . $project['id'];
            }
            $project['slug'] = $slug;
            $project['category'] = trim($project['category']) !== '' ? trim($project['category']) : 'Other';
            $projects[$slug] = $project;
        }
    }
    return $projects;
}

function project_url(array $project): string
{
    return 'projects.php?p=' . rawurlencode($project['slug']);
}

// Up to 3 projects for the home page: those marked "Show on the home page", or the first 3.
function featured_projects(): array
{
    $projects = array_values(projects_by_slug());
    $featured = array_values(array_filter($projects, fn($project) => $project['featured'] === '1'));
    return array_slice($featured ?: $projects, 0, 3);
}

/* ---------------------------------------------------------------------------
   Contact details
--------------------------------------------------------------------------- */

// "+977 981-234 5678" => "9779812345678"
function phone_digits(string $number): string
{
    return preg_replace('/\D+/', '', $number);
}

function whatsapp_url(): string
{
    $digits = phone_digits(s('contact_whatsapp'));
    return $digits !== '' ? 'https://wa.me/' . $digits : '';
}

function viber_url(): string
{
    $digits = phone_digits(s('contact_viber'));
    return $digits !== '' ? 'viber://chat?number=%2B' . $digits : '';
}

function cv_link_html(string $class): string
{
    if (s('resume_cv') === '') {
        return '';
    }
    return '<a class="' . e($class) . '" href="' . e(s('resume_cv')) . '" target="_blank" download="' . e(s('resume_cv_filename')) . '">'
        . '<i class="icon ion-android-download" aria-hidden="true"></i> ' . e(s('resume_cv_label')) . '</a>';
}
