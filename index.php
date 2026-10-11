<?php
require __DIR__ . '/cms/bootstrap.php';
site_boot('home');

$page = 'home';
$sections = enabled_home_sections();
$resume = ['left' => [], 'right' => []];
foreach (items('resume') as $entry) {
    $resume[$entry['column'] === 'right' ? 'right' : 'left'][] = $entry;
}
$skills = items('skills');
$extraCss = ['css/projects.css'];

// Contact details shown beside the form: [icon class, label, text, link].
$phone = s('contact_phone');
$contactDetails = array_values(array_filter([
    s('contact_email') !== '' ? ['ion-ios-email-outline', 'Email', s('contact_email'), 'mailto:' . s('contact_email')] : null,
    $phone !== '' ? ['ion-ios-telephone', 'Phone', $phone, 'tel:' . (str_starts_with(trim($phone), '+') ? '+' : '') . phone_digits($phone)] : null,
    s('contact_location') !== '' ? ['ion-ios-location', 'Location', s('contact_location'), ''] : null,
]));
$hasContactDetails = $contactDetails || whatsapp_url() !== '' || viber_url() !== '';

require __DIR__ . '/cms/partials/header.php';
require __DIR__ . '/cms/partials/bottom-bar.php';
?>
    <div class="pagepiling">
<?php foreach ($sections as $key => [$anchor, $class]): ?>
      <div data-anchor="<?= $anchor ?>" class="pp-scrollable <?= $class ?>">
        <div class="scroll-wrap">
<?php if ($key === 'intro'): ?>
          <div class="section-bg" style="background-image:url('<?= e(img(s('intro_bg'), 1920)) ?>');"></div>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
                <div class="vertical-title hidden-xs hidden-sm"><span><?= e(s('intro_side')) ?></span></div>
                <div class="boxed">
                  <div class="container">
                    <div class="intro">
                      <div class="row">
                        <div class="col-md-8 col-lg-6">
                          <p class="subtitle-top"><br></p>
                          <h1 class="display-2 text-white"><?= hl(s('intro_heading')) ?></h1>
                          <br/><h6><?= hl(s('intro_sub')) ?></h6>
                          <div class="hr-bottom"></div>
<?php if (on('intro_cv_button') && s('resume_cv') !== ''): ?>
                          <div class="intro-actions"><?= cv_link_html('site-button') ?></div>
<?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

<?php elseif ($key === 'about'): ?>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
                <div class="vertical-title text-dark hidden-xs hidden-sm"><span><?= e(s('about_side')) ?></span></div>
                <div class="boxed">
                  <div class="container">
                    <div class="intro">
                      <div class="row">
                        <div class="col-md-5 col-lg-5">
                          <p class="subtitle-top text-dark"><?= e(s('about_subtitle')) ?></p>
                          <h2 class="title-uppercase"><?= hl(s('about_heading')) ?></h2>
                          <?= lines(s('about_text')) ?>

                        </div>
                        <div class="col-md-6 col-lg-5 col-md-offset-1 col-lg-offset-2">
                          <div class="progress-bars">
<?php foreach (items('progress') as $bar): $percent = max(0, min(100, (int) $bar['percent'])); ?>
                            <div class="clearfix">
                              <div class="number pull-left"><?= e($bar['label']) ?></div>
                              <div class="number pull-right"><?= $percent ?>%</div>
                            </div>
                            <div class="progress">
                              <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
<?php endforeach; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

<?php elseif ($key === 'skills'): ?>
          <div class="bg-changer">
<?php foreach ($skills as $skill): ?>
            <div class="section-bg" style="background-image:url('<?= e(img($skill['background'], 1600, 72)) ?>');"></div>
<?php endforeach; ?>
          </div>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
                <div class="vertical-title hidden-xs hidden-sm"><span><?= e(s('skills_side')) ?></span></div>
                <div class="boxed">
                  <div class="container">
                    <div class="intro">
                      <div class="row">
                        <div class="col-md-12">
                          <h2 class="title-uppercase text-white"><?= e(s('skills_heading')) ?></h2>
                          <div class="row-project-box row">
<?php foreach ($skills as $skill): ?>
                            <div class="col-project-box col-sm-6 col-md-4 col-lg-3">
                              <a href="<?= e($skill['link']) ?>" class="project-box">
                                <div class="project-box-inner">
                                  <h5><?= e($skill['title']) ?></h5>
                                  <div class="project-category"><?= lines($skill['description']) ?></div>
                                </div>
                              </a>
                            </div>
<?php endforeach; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

<?php elseif ($key === 'services'): ?>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
                <div class="vertical-title text-dark hidden-xs hidden-sm"><span><?= e(s('services_side')) ?></span></div>
                <div class="boxed">
                  <div class="container">
                    <div class="intro">
                      <div class="row">
                        <div class="col-md-5 col-lg-5">
                          <p class="subtitle-top text-dark"><?= e(s('services_subtitle')) ?></p>
                          <h2 class="title-uppercase"><?= hl(s('services_heading')) ?></h2>
                          <ul class="service-list">
<?php foreach (items('services') as $i => $service): ?>
                            <li><a href="<?= e($service['link']) ?>"><?= sprintf('%02d', $i + 1) ?>.  <?= e($service['title']) ?></a></li>
<?php endforeach; ?>
                          </ul>
                        </div>
                        <div class="col-md-6 col-lg-5 col-md-offset-1 col-lg-offset-2">
                          <div class="dots-image-2">
<?php if (s('services_image') !== ''): ?>
                            <img alt="" class="img-responsive" src="<?= e(img(s('services_image'), 1000)) ?>">
<?php endif; ?>
                            <div class="dots"></div>
                            <div class="experience-info">
                              <div class="number"><?= e(s('services_badge_number')) ?></div>
                              <div class="text"><?= lines(s('services_badge_text')) ?></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

<?php elseif ($key === 'work'): ?>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
                <div class="vertical-title hidden-xs hidden-sm"><span><?= e(s('work_side')) ?></span></div>
                <div class="boxed">
                  <div class="container">
                    <div class="intro">
                      <div class="pj-home-head">
                        <div>
                          <p class="pj-sub"><?= e(s('work_subtitle')) ?></p>
                          <h2 class="title-uppercase text-white"><?= hl(s('work_heading')) ?></h2>
                        </div>
                        <a class="pj-visit" href="projects.php"><?= e(s('work_button')) ?> &rarr;</a>
                      </div>
                      <div class="pj-grid pj-grid-home">
<?php $cardClass = ''; foreach (featured_projects() as $project) {
    require __DIR__ . '/cms/partials/project-card.php';
} ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

<?php elseif ($key === 'resume'): ?>
          <div class="section-bg" style="background-image:url('<?= e(img(s('resume_bg'), 1920)) ?>');"></div>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
<?php if (s('resume_cv') !== ''): ?>
                <div class="vertical-title hidden-xs hidden-sm"><span><a href="<?= e(s('resume_cv')) ?>" target="_blank" download="<?= e(s('resume_cv_filename')) ?>"><?= e(s('resume_cv_label')) ?></a></span></div>
<?php endif; ?>
                <div class="boxed">
                  <div class="container">
                    <div class="intro">
                      <div class="row">
<?php foreach (['left', 'right'] as $column): ?>
                        <div class="col-md-6">
                          <div class="col-resume">
                            <h6 class="resume-title">
                              <?= e(s("resume_{$column}_title")) ?>

                            </h6>
                            <div class="resume-content">
                              <div class="resume-inner">
<?php foreach ($resume[$column] as $entry): ?>
                                <div class="resume-row">
                                  <h6 class="resume-type"><?= e($entry['title']) ?></h6>
<?php if ($entry['place'] !== ''): ?>
                                  <p class="resume-study"><?= e($entry['place']) ?></p>
<?php endif; ?>
<?php if ($entry['date'] !== ''): ?>
                                  <p class="resume-date text-primary"><?= e($entry['date']) ?></p>
<?php endif; ?>
<?php if ($entry['text'] !== ''): ?>
                                  <p class="resume-text"><?= lines($entry['text']) ?></p>
<?php endif; ?>
                                </div>
<?php endforeach; ?>
                              </div>
                            </div>
                          </div>
                        </div>
<?php endforeach; ?>
                      </div>
<?php if (on('resume_cv_button') && s('resume_cv') !== ''): ?>
                      <div class="resume-actions"><?= cv_link_html('site-button') ?></div>
<?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

<?php elseif ($key === 'contact'): ?>
          <div class="scrollable-content">
            <div class="vertical-centred">
              <div class="boxed boxed-inner">
                <div class="vertical-title text-dark hidden-xs hidden-sm"><span><?= e(s('contact_side')) ?></span></div>
                <div class="boxed">
                  <div class="container">
                    <div class="intro overflow-hidden">
                      <div class="row">
                        <div class="col-md-12">
                          <h2 class="title-uppercase"><?= e(s('contact_heading')) ?></h2>
                        </div>
<?php if ($hasContactDetails): ?>
                        <div class="col-md-4 contact-details">
<?php if (s('contact_intro') !== ''): ?>
                          <p class="contact-intro"><?= lines(s('contact_intro')) ?></p>
<?php endif; ?>
<?php if ($contactDetails): ?>
                          <ul class="contact-list">
<?php foreach ($contactDetails as [$iconClass, $label, $value, $href]): ?>
                            <li>
                              <span class="contact-icon"><i class="icon <?= $iconClass ?>" aria-hidden="true"></i></span>
                              <span><span class="contact-label"><?= $label ?></span><?= $href !== '' ? '<a class="contact-value" href="' . e($href) . '">' . e($value) . '</a>' : '<span class="contact-value">' . e($value) . '</span>' ?></span>
                            </li>
<?php endforeach; ?>
                          </ul>
<?php endif; ?>
<?php if (whatsapp_url() !== '' || viber_url() !== ''): ?>
                          <div class="chat-buttons">
<?php if (whatsapp_url() !== ''): ?>
                            <a class="chat-button whatsapp" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener"><i class="icon ion-social-whatsapp" aria-hidden="true"></i> WhatsApp</a>
<?php endif; ?>
<?php if (viber_url() !== ''): ?>
                            <a class="chat-button viber" href="<?= e(viber_url()) ?>"><iconify-icon icon="simple-icons:viber" aria-hidden="true"></iconify-icon> Viber</a>
<?php endif; ?>
                          </div>
<?php endif; ?>
                        </div>
<?php endif; ?>
                        <div class="<?= $hasContactDetails ? 'col-md-8' : 'col-md-12' ?>">
                          <div class="contact-info">
                            <form class="js-form" novalidate="novalidate">
                              <div class="row">
                                <div class="form-group col-sm-6">
                                  <input type="text" name="name" required="" maxlength="100" placeholder="Name*" aria-required="true">
                                </div>
                                <div class="form-group col-sm-6">
                                  <input type="email" name="email" maxlength="200" placeholder="Email">
                                </div>
                                <div class="form-group col-sm-12">
                                  <input type="text" name="subject" maxlength="200" placeholder="Subject (Optional)">
                                </div>
                                <div class="form-group col-sm-12">
                                  <textarea name="message" required="" maxlength="5000" placeholder="Message*" aria-required="true"></textarea>
                                </div>
                                <!-- Left empty by people; spam bots fill it in and get ignored. -->
                                <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="display:none">
                                <div class="col-sm-12"><button type="submit" class="btn"><?= e(s('contact_button')) ?></button></div>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
<?php endif; ?>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php
$beforeScripts = '<script>window.SECTION_ANCHORS = ' . json_encode(array_column($sections, 0)) . ';</script>' . "\n";
require __DIR__ . '/cms/partials/footer.php';
