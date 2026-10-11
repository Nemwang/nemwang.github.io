// Small enhancements for the admin forms. Everything still works without JavaScript.
(function () {
  'use strict';

  // Ask before deleting.
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-confirm]');
    if (button && !window.confirm(button.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });

  // Success messages fade away on their own.
  document.querySelectorAll('.flash.ok').forEach(function (flash) {
    setTimeout(function () {
      flash.classList.add('leaving');
      setTimeout(function () { flash.remove(); }, 450);
    }, 3500);
  });

  // Close the mobile menu after picking a page.
  var navToggle = document.getElementById('nav-toggle');
  document.querySelectorAll('.nav a').forEach(function (link) {
    link.addEventListener('click', function () { if (navToggle) navToggle.checked = false; });
  });

  // Section switches in card headers: label text and dimmed body follow the switch.
  document.querySelectorAll('[data-section-toggle]').forEach(function (input) {
    var card = input.closest('.section-card');
    var text = card && card.querySelector('[data-switch-text]');
    input.addEventListener('change', function () {
      if (card) card.classList.toggle('is-off', !input.checked);
      if (text) text.textContent = text.getAttribute(input.checked ? 'data-on' : 'data-off');
    });
  });

  // Image and file fields: preview the chosen file, and clear the current one.
  document.querySelectorAll('[data-media]').forEach(function (media) {
    var isImage = media.getAttribute('data-media') === 'image';
    var file = media.querySelector('[data-file]');
    var path = media.querySelector('[data-path]');
    var thumb = media.querySelector('[data-thumb]');
    var clear = media.querySelector('[data-clear]');
    var note = media.querySelector('[data-note]');
    var original = thumb.innerHTML;
    var emptyIcon = '<svg class="icon" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
      + (isImage
        ? '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>'
        : '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h6"/>')
      + '</svg>';

    file.addEventListener('change', function () {
      var chosen = file.files && file.files[0];
      if (!chosen) {
        thumb.innerHTML = original;
        note.textContent = '';
        return;
      }
      if (isImage) {
        var img = document.createElement('img');
        img.src = URL.createObjectURL(chosen);
        thumb.replaceChildren(img);
      } else {
        thumb.innerHTML = emptyIcon;
      }
      thumb.classList.remove('is-empty');
      note.textContent = chosen.name + ' — uploads when you save';
      clear.hidden = false;
    });

    clear.addEventListener('click', function () {
      file.value = '';
      path.value = '';
      thumb.innerHTML = emptyIcon;
      thumb.classList.add('is-empty');
      note.textContent = '';
      clear.hidden = true;
      path.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  // Unsaved changes: the save bar says so, and leaving the page asks first.
  document.querySelectorAll('[data-form]').forEach(function (form) {
    var bar = form.querySelector('.savebar');
    var status = form.querySelector('[data-status]');
    var dirty = false;
    var submitting = false;

    function markDirty() {
      if (dirty || !bar) return;
      dirty = true;
      bar.classList.add('dirty');
      status.textContent = 'You have unsaved changes';
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () {
      submitting = true;
      var button = bar && bar.querySelector('button');
      if (button) {
        button.disabled = true;
        button.lastElementChild.textContent = 'Saving…';
      }
    });
    window.addEventListener('beforeunload', function (event) {
      if (dirty && !submitting) {
        event.preventDefault();
        event.returnValue = '';
      }
    });
  });

  // Highlight the section jump link of the card in view.
  var links = document.querySelectorAll('.section-nav a');
  if (links.length && 'IntersectionObserver' in window) {
    var byId = {};
    var order = [];
    var inView = {};
    links.forEach(function (link) {
      var id = link.getAttribute('href').slice(1);
      byId[id] = link;
      order.push(id);
    });
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) { inView[entry.target.id] = entry.isIntersecting; });
      var top = order.filter(function (id) { return inView[id]; })[0];
      if (!top || byId[top].classList.contains('current')) return;
      links.forEach(function (link) { link.classList.remove('current'); });
      byId[top].classList.add('current');
      byId[top].parentNode.scrollTo({ left: byId[top].offsetLeft - 32, behavior: 'smooth' });
    }, { rootMargin: '-130px 0px -55% 0px' });
    Object.keys(byId).forEach(function (id) {
      var section = document.getElementById(id);
      if (section) observer.observe(section);
    });
  }
})();
