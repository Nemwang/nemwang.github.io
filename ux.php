<?php
require __DIR__ . '/cms/bootstrap.php';
site_boot('ux');

$page = 'ux';
$extraCss = ['css/style2.css'];
$extraHead = <<<HTML
<style>
  /* Gallery styles */
  .gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    grid-gap: 20px;
    padding: 20px;
  }
  .gallery-item {
    overflow: hidden;
    border-radius: 5px;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: zoom-in;
  }
  .gallery-item img {
    width: 100%;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease-in-out;
  }
  .gallery-item:hover img,
  .gallery-item:focus img {
    transform: scale(1.1);
  }
  .gallery-item:focus {
    outline: 2px solid #f3ca2f;
    outline-offset: 3px;
  }
  .image-name {
    background-color: rgba(0, 0, 0, 0.8);
    color: #fff;
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 10px;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
  }
  .gallery-item:hover .image-name,
  .gallery-item:focus .image-name {
    opacity: 1;
  }

  /* Full-size viewer */
  body.lb-open { overflow: hidden; }
  .lb { position: fixed; inset: 0; z-index: 3000; display: flex; align-items: center; justify-content: center; padding: 64px 84px; background: rgba(12, 12, 12, .95); }
  .lb[hidden] { display: none; }
  .lb-figure { display: flex; flex-direction: column; align-items: center; max-width: 100%; max-height: 100%; margin: 0; }
  .lb-img { display: block; max-width: 100%; max-height: calc(100vh - 150px); border-radius: 4px; object-fit: contain; box-shadow: 0 20px 60px rgba(0, 0, 0, .5); }
  .lb-caption { display: flex; gap: 14px; align-items: baseline; margin-top: 16px; color: #eee; font-size: 15px; font-weight: 500; text-align: center; }
  .lb-count { color: #8d8d8d; font-size: 13px; }
  .lb button { position: absolute; display: grid; place-items: center; padding: 0; border: 0; border-radius: 50%; background: rgba(255, 255, 255, .08); color: #fff; font-size: 30px; line-height: 1; cursor: pointer; transition: background .2s, color .2s; }
  .lb button:hover, .lb button:focus { background: #f3ca2f; color: #1d1d1d; outline: none; }
  .lb-close { top: 18px; right: 18px; width: 46px; height: 46px; }
  .lb-prev, .lb-next { top: 50%; width: 54px; height: 54px; margin-top: -27px; }
  .lb-prev { left: 18px; }
  .lb-next { right: 18px; }
  @media (max-width: 767px) {
    .lb { padding: 64px 12px 90px; }
    .lb-prev, .lb-next { top: auto; bottom: 20px; margin-top: 0; }
    .lb-prev { left: calc(50% - 64px); }
    .lb-next { right: calc(50% - 64px); }
  }
</style>
HTML;

require __DIR__ . '/cms/partials/header.php';
?>
    <div class="page-wrapper">
<?php require __DIR__ . '/cms/partials/bottom-bar.php'; ?>
    </div>

    <div class="gallery-container" style="padding: 80px;">
      <div class="textheader" style="padding: 20px;"> <h1><?= e(s('ux_heading')) ?></h1></div>
      <div class="gallery">
<?php foreach (items('gallery') as $design): ?>
        <a class="gallery-item" href="<?= e(img($design['image'], 1920, 85)) ?>" data-lightbox data-caption="<?= e($design['name']) ?>">
          <img src="<?= e(img($design['image'], 800)) ?>" alt="<?= e($design['name']) ?>" loading="lazy">
          <div class="image-name"><?= e($design['name']) ?></div>
        </a>
<?php endforeach; ?>
      </div>
    </div>
<?php
// Full-size viewer: click a design; arrows, swipe or the keyboard move between designs.
// Without JavaScript the links simply open the image.
$beforeScripts = <<<'HTML'
<script>
(function () {
  var items = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
  if (!items.length) return;

  var box = document.createElement('div');
  box.className = 'lb';
  box.hidden = true;
  box.setAttribute('role', 'dialog');
  box.setAttribute('aria-modal', 'true');
  box.setAttribute('aria-label', 'Design viewer');
  box.innerHTML = '<button type="button" class="lb-close" aria-label="Close">&times;</button>'
    + '<button type="button" class="lb-prev" aria-label="Previous design">&#8249;</button>'
    + '<figure class="lb-figure"><img class="lb-img" alt=""><figcaption class="lb-caption"><span class="lb-name"></span><span class="lb-count"></span></figcaption></figure>'
    + '<button type="button" class="lb-next" aria-label="Next design">&#8250;</button>';
  document.body.appendChild(box);

  var img = box.querySelector('.lb-img');
  var name = box.querySelector('.lb-name');
  var count = box.querySelector('.lb-count');
  var buttons = Array.prototype.slice.call(box.querySelectorAll('button'));
  var closeButton = box.querySelector('.lb-close');
  var index = 0;
  var opener = null;

  function show(i) {
    index = (i + items.length) % items.length;
    var item = items[index];
    img.src = item.href;
    img.alt = item.getAttribute('data-caption');
    name.textContent = item.getAttribute('data-caption');
    count.textContent = (index + 1) + ' / ' + items.length;
    // Load the neighbours early so moving between designs is instant.
    [index - 1, index + 1].forEach(function (n) { new Image().src = items[(n + items.length) % items.length].href; });
  }
  function open(i) {
    opener = document.activeElement;
    show(i);
    box.hidden = false;
    document.body.classList.add('lb-open');
    closeButton.focus();
  }
  function close() {
    box.hidden = true;
    document.body.classList.remove('lb-open');
    img.removeAttribute('src');
    if (opener) opener.focus();
  }

  items.forEach(function (item, i) {
    item.addEventListener('click', function (event) { event.preventDefault(); open(i); });
  });
  closeButton.addEventListener('click', close);
  box.querySelector('.lb-prev').addEventListener('click', function () { show(index - 1); });
  box.querySelector('.lb-next').addEventListener('click', function () { show(index + 1); });
  box.addEventListener('click', function (event) {
    if (event.target === box || event.target.classList.contains('lb-figure')) close();
  });

  document.addEventListener('keydown', function (event) {
    if (box.hidden) return;
    if (event.key === 'Escape') close();
    else if (event.key === 'ArrowLeft') show(index - 1);
    else if (event.key === 'ArrowRight') show(index + 1);
    else if (event.key === 'Tab') {
      // Keep keyboard focus inside the viewer.
      var at = buttons.indexOf(document.activeElement);
      buttons[(at + (event.shiftKey ? -1 : 1) + buttons.length) % buttons.length].focus();
      event.preventDefault();
    }
  });

  var startX = null;
  box.addEventListener('touchstart', function (event) { startX = event.touches[0].clientX; }, { passive: true });
  box.addEventListener('touchend', function (event) {
    if (startX === null) return;
    var moved = event.changedTouches[0].clientX - startX;
    if (Math.abs(moved) > 50) show(index + (moved < 0 ? 1 : -1));
    startX = null;
  });
})();
</script>

HTML;
require __DIR__ . '/cms/partials/footer.php';
