<?php
// One project card. Expects $project; optional $cardClass for extra classes.
$categorySlug = slugify($project['category']);
$meta = implode(' · ', array_filter([$project['client'], $project['completion']]));
?>
          <a class="pj-card<?= $cardClass ?? '' ?>" href="<?= e(project_url($project)) ?>" data-category="<?= e($categorySlug) ?>">
            <span class="pj-card-image">
<?php if ($project['background'] !== ''): ?>
              <img src="<?= e(img($project['background'], 800)) ?>" alt="" loading="lazy">
<?php endif; ?>
            </span>
            <span class="pj-card-body">
              <span class="pj-card-cat"><?= e($project['category']) ?></span>
              <span class="pj-card-name"><?= e($project['name']) ?></span>
<?php if ($meta !== ''): ?>
              <span class="pj-card-meta"><?= e($meta) ?></span>
<?php endif; ?>
            </span>
          </a>
