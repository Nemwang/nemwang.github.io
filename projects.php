<?php
// The Projects page: a grid of project cards with category filters (projects.php),
// and a page for each project (projects.php?p=ultra-led-display).
require __DIR__ . '/cms/bootstrap.php';
site_boot('projects');

$page = 'projects';
$projects = projects_by_slug();
$slugs = array_keys($projects);

// Category name => [address name, number of projects], in the order they first appear.
$categories = [];
foreach ($projects as $project) {
    $name = $project['category'];
    $categories[$name] = [slugify($name), ($categories[$name][1] ?? 0) + 1];
}
$activeCategory = (string) ($_GET['cat'] ?? '');

$current = null;
$slug = (string) ($_GET['p'] ?? '');
if ($slug !== '') {
    $current = $projects[$slug] ?? null;
    if (!$current) {
        site_unavailable(404, 'Project not found', '<a href="projects.php">See all projects</a>');
    }
    $position = array_search($slug, $slugs, true);
    $previous = $projects[$slugs[($position - 1 + count($slugs)) % count($slugs)]];
    $next = $projects[$slugs[($position + 1) % count($slugs)]];

    $summary = trim(preg_replace('/\s+/', ' ', $current['description']));
    $pageTitle = $current['name'] . ' — ' . s('projects_title');
    $pageDescription = mb_strlen($summary) > 160 ? rtrim(mb_substr($summary, 0, 157)) . '…' : ($summary ?: page_description('projects'));
    $pageUrl = project_url($current);
    if ($current['background'] !== '') {
        $pageImage = absolute_url(img($current['background'], 1200, 82, 'jpg'));
    }
}

$extraCss = ['css/projects.css'];

require __DIR__ . '/cms/partials/header.php';
?>
    <div class="page-wrapper">
<?php require __DIR__ . '/cms/partials/bottom-bar.php'; ?>
    </div>

    <main class="pj">
      <div class="container">
<?php if ($current): ?>
        <a class="pj-back" href="projects.php">&larr; <?= e(s('projects_back_label')) ?></a>
        <article class="pj-detail">
          <div class="pj-detail-image">
<?php if ($current['background'] !== ''): ?>
            <img src="<?= e(img($current['background'], 1600)) ?>" alt="<?= e($current['name']) ?>">
<?php endif; ?>
          </div>
          <div>
            <p class="pj-sub"><?= e($current['category']) ?></p>
            <h1 class="pj-title"><?= e($current['name']) ?></h1>
<?php if ($current['heading'] !== ''): ?>
            <p class="pj-detail-heading"><?= e($current['heading']) ?></p>
<?php endif; ?>
<?php if ($current['description'] !== ''): ?>
            <p class="pj-detail-text"><?= lines($current['description']) ?></p>
<?php endif; ?>
            <dl class="pj-facts">
<?php if ($current['logo'] !== ''): ?>
              <div class="pj-fact"><dt>Logo</dt><dd><img class="pj-logo" src="<?= e(img($current['logo'], 400)) ?>" alt="<?= e($current['client'] !== '' ? $current['client'] . ' logo' : '') ?>"></dd></div>
<?php endif; ?>
<?php foreach (['client' => 'Client', 'completion' => 'Completion', 'type' => 'Project type', 'services' => 'Other services'] as $field => $label): ?>
<?php if ($current[$field] !== ''): ?>
              <div class="pj-fact"><dt><?= $label ?></dt><dd><?= e($current[$field]) ?></dd></div>
<?php endif; ?>
<?php endforeach; ?>
            </dl>
<?php if ($current['link_url'] !== ''): ?>
            <a class="pj-visit" href="<?= e($current['link_url']) ?>" target="_blank" rel="noopener"><?= e(s('projects_visit_label')) ?> &rarr;</a>
<?php endif; ?>
<?php if (items('social')): ?>
            <div class="pj-share">
              <span><?= e(s('projects_share_label')) ?></span>
              <div class="social-list"><?= $socialLinks ?></div>
            </div>
<?php endif; ?>
          </div>
        </article>
<?php if (count($projects) > 1): ?>
        <nav class="pj-pager" aria-label="More projects">
          <a href="<?= e(project_url($previous)) ?>"><span>&larr; Previous</span><?= e($previous['name']) ?></a>
          <a class="pj-next" href="<?= e(project_url($next)) ?>"><span>Next &rarr;</span><?= e($next['name']) ?></a>
        </nav>
<?php endif; ?>

<?php else: ?>
        <header>
<?php if (s('projects_subtitle') !== ''): ?>
          <p class="pj-sub"><?= e(s('projects_subtitle')) ?></p>
<?php endif; ?>
          <h1 class="pj-title"><?= e(s('projects_heading')) ?></h1>
<?php if (s('projects_intro') !== ''): ?>
          <p class="pj-intro"><?= lines(s('projects_intro')) ?></p>
<?php endif; ?>
        </header>
<?php if (!$projects): ?>
        <p class="pj-empty">No projects yet.</p>
<?php else: ?>
<?php if (count($categories) > 1): ?>
        <nav class="pj-filters" aria-label="Filter projects">
          <a class="pj-filter<?= $activeCategory === '' ? ' is-active' : '' ?>" href="projects.php" data-filter=""><?= e(s('projects_all_label')) ?> <span><?= count($projects) ?></span></a>
<?php foreach ($categories as $name => [$categorySlug, $count]): ?>
          <a class="pj-filter<?= $activeCategory === $categorySlug ? ' is-active' : '' ?>" href="projects.php?cat=<?= e($categorySlug) ?>" data-filter="<?= e($categorySlug) ?>"><?= e($name) ?> <span><?= $count ?></span></a>
<?php endforeach; ?>
        </nav>
<?php endif; ?>
        <div class="pj-grid">
<?php foreach ($projects as $project):
    $cardClass = $activeCategory !== '' && $activeCategory !== slugify($project['category']) ? ' is-hidden' : '';
    require __DIR__ . '/cms/partials/project-card.php';
endforeach; ?>
        </div>
<?php endif; ?>
<?php endif; ?>
      </div>
    </main>
<?php
// Filter buttons switch instantly; without JavaScript they are ordinary links.
$beforeScripts = <<<HTML
<script>
document.querySelectorAll('.pj-filter').forEach(function (button) {
  button.addEventListener('click', function (event) {
    event.preventDefault();
    var filter = button.getAttribute('data-filter');
    document.querySelectorAll('.pj-filter').forEach(function (b) { b.classList.toggle('is-active', b === button); });
    document.querySelectorAll('.pj-card').forEach(function (card) {
      card.classList.toggle('is-hidden', filter !== '' && card.getAttribute('data-category') !== filter);
    });
    history.replaceState(null, '', button.getAttribute('href'));
  });
});
</script>

HTML;
require __DIR__ . '/cms/partials/footer.php';
