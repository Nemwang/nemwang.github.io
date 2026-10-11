
    <!-- Modals success -->

  <div id="success" class="modal modal-message fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <span class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></span>
            <h2 class="modal-title"><?= e(s('contact_success_title')) ?></h2>
            <p class="modal-subtitle"><?= e(s('contact_success_text')) ?></p>
          </div>
        </div>
    </div>
  </div>

  <!-- Modals error -->

  <div id="error" class="modal modal-message fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <span class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></span>
            <h2 class="modal-title"><?= e(s('contact_error_title')) ?></h2>
            <p class="modal-subtitle"><?= e(s('contact_error_text')) ?></p>
          </div>
        </div>
    </div>
  </div>


<?php if (on('whatsapp_float') && whatsapp_url() !== ''): ?>
  <a class="wa-float" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" title="Chat on WhatsApp"><i class="icon ion-social-whatsapp" aria-hidden="true"></i></a>
<?php endif; ?>

<!-- jQuery -->

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/smoothscroll.js"></script>
<script src="js/jquery.validate.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/jquery.pagepiling.js"></script>


<!-- Scripts -->
<?= $beforeScripts ?? '' ?>
<script src="js/scripts.js"></script>
</body>
</html>
