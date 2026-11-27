/*
 * Ko'rinish sozlamalari (mavzu, shrift, o'lcham, animatsiya) va mehmon avatari.
 * <head> da sinxron yuklanadi: sahifa chizilishidan oldin atributlar qo'yilsin (miltillamasin).
 * Hammasi shu brauzerning localStorage ida saqlanadi (mehmon ham, kirgan foydalanuvchi ham).
 */
(function () {
  'use strict';

  var KEY = 'fm_prefs';
  var AVATAR_KEY = 'fm_guest_avatar';
  var ALLOWED = {
    theme: ['dark', 'mint', 'lavender'],
    font: ['jakarta', 'hanken', 'inter', 'atkinson', 'system', 'serif', 'mono'],
    heading: ['display', 'body', 'serif'],
    size: ['sm', 'md', 'lg', 'xl'],
    motion: ['full', 'reduced'],
    ripple: ['on', 'off'],
    ambient: ['on', 'off'],
  };
  var TONE = { dark: 'dark', mint: 'light', lavender: 'light' };
  var root = document.documentElement;

  function defaults() {
    var reduced = false;
    try { reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) { /* eski brauzer */ }
    return { theme: 'dark', font: 'jakarta', heading: 'display', size: 'md', motion: reduced ? 'reduced' : 'full', ripple: 'on', ambient: 'on' };
  }

  function load() {
    var prefs = defaults();
    var raw = null;
    try { raw = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (e) { raw = null; }
    if (raw && typeof raw === 'object') {
      Object.keys(ALLOWED).forEach(function (k) {
        if (ALLOWED[k].indexOf(raw[k]) !== -1) prefs[k] = raw[k];
      });
    }
    return prefs;
  }

  function save(prefs) {
    try { localStorage.setItem(KEY, JSON.stringify(prefs)); return true; } catch (e) { return false; }
  }

  function apply(prefs) {
    Object.keys(ALLOWED).forEach(function (k) { root.setAttribute('data-' + k, prefs[k]); });
    root.setAttribute('data-tone', TONE[prefs.theme] || 'dark');
    root.classList.toggle('dark', (TONE[prefs.theme] || 'dark') === 'dark');
    try { window.dispatchEvent(new CustomEvent('fm:prefs', { detail: prefs })); } catch (e) { /* eski brauzer */ }
  }

  var current = load();
  apply(current);

  var api = {
    allowed: ALLOWED,
    get: function () { return Object.assign({}, current); },
    set: function (key, value) {
      if (!ALLOWED[key] || ALLOWED[key].indexOf(value) === -1) return false;
      current[key] = value;
      var ok = save(current);
      apply(current);
      return ok;
    },
    reset: function () {
      try { localStorage.removeItem(KEY); } catch (e) { /* bloklangan */ }
      current = defaults();
      apply(current);
    },
  };

  /* ---------- Mehmon avatari (faqat shu brauzerda; serverga yuborilmaydi) ---------- */
  var SIZE = 256;

  function getGuestAvatar() {
    try {
      var v = localStorage.getItem(AVATAR_KEY);
      return v && /^data:image\/(jpeg|png|webp);base64,/.test(v) ? v : null;
    } catch (e) { return null; }
  }

  function paintGuestAvatar() {
    var url = getGuestAvatar();
    document.querySelectorAll('[data-guest-avatar]').forEach(function (box) {
      var img = box.querySelector('[data-avatar-img]');
      var fallback = box.querySelector('[data-avatar-fallback]');
      if (img) {
        if (url) img.setAttribute('src', url); else img.removeAttribute('src');
        img.classList.toggle('hidden', !url);
      }
      if (fallback) fallback.classList.toggle('hidden', !!url);
    });
    document.querySelectorAll('[data-guest-avatar-remove]').forEach(function (b) { b.classList.toggle('hidden', !url); });
  }

  /* Rasm markazdan kvadrat qilib kesiladi va 256px ga kichraytiriladi (localStorage hajmi uchun). */
  function setGuestAvatar(file) {
    return new Promise(function (resolve, reject) {
      if (!file || !/^image\/(jpeg|png|webp|gif)$/.test(file.type)) return reject(new Error('type'));
      if (file.size > 8 * 1024 * 1024) return reject(new Error('size'));
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        try {
          var side = Math.min(img.width, img.height);
          var canvas = document.createElement('canvas');
          canvas.width = SIZE;
          canvas.height = SIZE;
          var ctx = canvas.getContext('2d');
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, SIZE, SIZE);
          ctx.drawImage(img, (img.width - side) / 2, (img.height - side) / 2, side, side, 0, 0, SIZE, SIZE);
          var data = canvas.toDataURL('image/jpeg', 0.85);
          localStorage.setItem(AVATAR_KEY, data);
          paintGuestAvatar();
          resolve(data);
        } catch (e) { reject(e); } finally { URL.revokeObjectURL(url); }
      };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
      img.src = url;
    });
  }

  function clearGuestAvatar() {
    try { localStorage.removeItem(AVATAR_KEY); } catch (e) { /* bloklangan */ }
    paintGuestAvatar();
  }

  api.guestAvatar = { get: getGuestAvatar, set: setGuestAvatar, clear: clearGuestAvatar, paint: paintGuestAvatar };
  window.FoodMartPrefs = api;

  document.addEventListener('DOMContentLoaded', paintGuestAvatar);

  /* Boshqa tabda o'zgarsa, shu tab ham yangilansin. */
  window.addEventListener('storage', function (e) {
    if (e.key === KEY) { current = load(); apply(current); }
    if (e.key === AVATAR_KEY) paintGuestAvatar();
  });
})();
