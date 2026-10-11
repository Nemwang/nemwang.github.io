    <div class="copy-bottom white boxed"><?= e(with_year(s('footer_copyright'))) ?></div>
<?php if (on('show_language')): ?>
    <div class="lang-bottom white boxed">
      <div class="menu-lang">
        <a href="" class="active"><?= e(s('language_label')) ?></a>
      </div>
    </div>
<?php endif; ?>
