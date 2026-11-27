/* Sozlamalar sahifasi: tugmalar public/js/prefs.js (FoodMartPrefs) orqali saqlanadi va darhol qo'llanadi. */
(function () {
  'use strict';
  var prefs = window.FoodMartPrefs;
  if (!prefs) return;

  function cursorOn() {
    return window.FoodMartCursor ? window.FoodMartCursor.isEnabled() : false;
  }

  function refresh() {
    var cur = prefs.get();
    document.querySelectorAll('[data-pref]').forEach(function (b) {
      b.setAttribute('aria-pressed', cur[b.dataset.pref] === b.dataset.value ? 'true' : 'false');
    });
    var on = cursorOn();
    document.querySelectorAll('[data-cursor]').forEach(function (b) {
      b.setAttribute('aria-pressed', (b.dataset.cursor === 'on') === on ? 'true' : 'false');
    });
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pref]');
    if (b) {
      prefs.set(b.dataset.pref, b.dataset.value);
      /* Kamaytirilgan animatsiyada kursor ham o'chadi. */
      if (b.dataset.pref === 'motion' && b.dataset.value === 'reduced' && window.FoodMartCursor) window.FoodMartCursor.disable();
      refresh();
      return;
    }
    var c = e.target.closest('[data-cursor]');
    if (c && window.FoodMartCursor) {
      if (c.dataset.cursor === 'on') window.FoodMartCursor.enable(); else window.FoodMartCursor.disable();
      refresh();
    }
  });

  window.addEventListener('fm:cursor', refresh);
  window.addEventListener('fm:prefs', refresh);

  var reset = document.getElementById('settings-reset');
  if (reset) reset.addEventListener('click', function () {
    prefs.reset();
    try { localStorage.removeItem('fm_cursor'); } catch (e) { /* bloklangan */ }
    refresh();
  });

  /* Kirgan foydalanuvchi: tanlangan rasmni yuklashdan oldin ko'rsatish. */
  var input = document.getElementById('avatar-input');
  var slot = document.getElementById('settings-avatar');
  if (input && slot) input.addEventListener('change', function () {
    var f = input.files && input.files[0];
    if (!f) return;
    var img = slot.querySelector('img');
    if (!img) { img = document.createElement('img'); img.className = 'h-full w-full object-cover'; img.alt = ''; slot.textContent = ''; slot.appendChild(img); }
    img.src = URL.createObjectURL(f);
  });

  /* Mehmon: rasm faqat brauzerda saqlanadi. */
  var guest = document.getElementById('guest-avatar-input');
  var err = document.getElementById('guest-avatar-error');
  if (guest) guest.addEventListener('change', function () {
    var f = guest.files && guest.files[0];
    if (!f) return;
    if (err) err.classList.add('hidden');
    prefs.guestAvatar.set(f).catch(function (ex) {
      if (!err) return;
      err.textContent = ex && ex.message === 'size' ? 'The image is too large (max 8 MB).' : 'Please choose a JPG, PNG, WebP or GIF image.';
      err.classList.remove('hidden');
    });
  });
  document.querySelectorAll('[data-guest-avatar-remove]').forEach(function (b) {
    b.addEventListener('click', function () { prefs.guestAvatar.clear(); if (guest) guest.value = ''; });
  });
  prefs.guestAvatar.paint();

  refresh();
})();
