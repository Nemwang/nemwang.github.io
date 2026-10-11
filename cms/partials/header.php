<?php
// Top of every public page: <head>, slide-out menu and navbar.
// Expects $page ("home", "ux" or "projects"); optional $extraCss (array) and $extraHead (html).
// A page can set $pageTitle, $pageDescription, $pageUrl and $pageImage to replace the page defaults.
$socialLinks = social_links_html();
$title = $pageTitle ?? s($page . '_title');
$description = $pageDescription ?? page_description($page);
$canonical = site_url() . '/' . ($pageUrl ?? page_path($page));
$shareImage = $pageImage ?? share_image();

// Lets Google connect the site with you and your social profiles.
$person = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => s('meta_author'),
    'url' => site_url() . '/',
    'image' => share_image(),
    'sameAs' => array_values(array_filter(array_column(items('social'), 'url'), fn($url) => str_starts_with($url, 'http'))),
]);
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e($description) ?>">
<meta name="author" content="<?= e(s('meta_author')) ?>">
<meta name="theme-color" content="#272727">
<link rel="canonical" href="<?= e($canonical) ?>">

<!-- Link previews (LinkedIn, Facebook, WhatsApp, X) -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(s('site_name') . ' ' . s('site_tagline')) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($shareImage !== ''): ?>
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<script type="application/ld+json"><?= json_encode($person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?= s('head_code') ?>


<!-- Favicons -->
<link rel="shortcut icon" href="<?= e(s('favicon')) ?>">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="72x72" href="apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="114x114" href="apple-touch-icon-114x114.png">

<title><?= e($title) ?></title>
<script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
<!-- Styles -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="css/bootstrap.css" rel="stylesheet" media="screen">
<link href="css/font-awesome.min.css" rel="stylesheet" media="screen">
<link href="css/ionicons.min.css" rel="stylesheet" media="screen">
<link href="css/linearicons.css" rel="stylesheet" media="screen">
<link href="css/jquery.fullPage.css" rel="stylesheet" media="screen">
<link href="css/jquery.pagepiling.css" rel="stylesheet" media="screen">
<link href="css/owl.carousel.css" rel="stylesheet" media="screen">
<link href="https://fonts.googleapis.com/css?family=Playfair+Display:400,400i,700,700i|Poppins:400,500,600,700&amp;subset=cyrillic&amp;display=swap" rel="stylesheet">
<!-- main.css is style.css with its repeated rules removed; style.css stays for the old .html pages. -->
<link href="css/main.css" rel="stylesheet" media="screen">
<link href="css/extras.css" rel="stylesheet" media="screen">
<?php foreach ($extraCss ?? [] as $css): ?>
<link href="<?= e($css) ?>" rel="stylesheet" media="screen">
<?php endforeach; ?>
<?= $extraHead ?? '' ?>

</head>
<body class="body-full-page">

<?php if (on('show_loader')): ?>
    <div class="loader"><div class="spinner"><div class="double-bounce1"></div><div class="double-bounce2"></div></div></div>
<?php endif; ?>

    <!-- Content CLick Capture-->

    <div class="click-capture"></div>

    <!-- Sidebar Menu-->

    <div class="menu">
      <span class="close-menu icon-cross2 right-boxed"></span>
<?php if (on('show_language')): ?>
      <div class="menu-lang right-boxed">
        <a href="" class="active"><?= e(s('language_label')) ?></a>
      </div>
<?php endif; ?>
      <ul class="menu-list right-boxed">
<?php foreach (menu_for($page) as $item): ?>
        <li<?= $item['anchor'] ? ' data-menuanchor="' . e($item['anchor']) . '"' : '' ?>>
          <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
        </li>
<?php endforeach; ?>
      </ul>
      <div class="menu-footer right-boxed">
<?php if (on('menu_show_social')): ?>
        <div class="social-list">
          <?= $socialLinks ?>
        </div>
<?php endif; ?>
        <div class="copy"><?= lines(with_year(s('menu_copyright'))) ?></div>
      </div>
    </div>

    <!-- Navbar -->

    <header class="navbar navbar-2 navbar-white boxed">
      <div class="navbar-bg"></div>
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse" aria-expanded="false">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>

      <a class="brand" href="./">
<?php if (on('header_show_logo') && s('logo') !== ''): ?>
        <img class="brand-img" alt="<?= e(s('site_name') . ' ' . s('site_tagline')) ?> logo" src="<?= e(s('logo')) ?>">
<?php endif; ?>
<?php if (on('header_show_name')): ?>
        <div class="brand-info">
          <div class="brand-name"><?= e(s('site_name')) ?></div>
          <div class="brand-text"><?= e(s('site_tagline')) ?></div>
        </div>
<?php endif; ?>
      </a>

<?php if (on('header_show_social')): ?>
      <div class="social-list hidden-xs">
        <?= $socialLinks ?>
      </div>
<?php endif; ?>
    </header>
