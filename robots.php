<?php
// robots.txt (see .htaccess): keeps search engines out of the admin panel and points them to the sitemap.
require __DIR__ . '/cms/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nDisallow: /admin/\nDisallow: /cms/\n\nSitemap: " . site_url() . "/sitemap.xml\n";
