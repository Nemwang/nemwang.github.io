<?php
// sitemap.xml (see .htaccess): the public pages that are online, for search engines.
require __DIR__ . '/cms/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
try {
    foreach (['home', 'ux', 'projects'] as $page) {
        if (on($page . '_enabled')) {
            echo '  <url><loc>' . e(site_url() . '/' . page_path($page)) . "</loc></url>\n";
        }
    }
    if (on('projects_enabled')) {
        foreach (projects_by_slug() as $project) {
            echo '  <url><loc>' . e(site_url() . '/' . project_url($project)) . "</loc></url>\n";
        }
    }
} catch (Throwable $e) {
    // Not set up yet: an empty sitemap.
}
echo "</urlset>\n";
