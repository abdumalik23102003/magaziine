// Header dropdowns (<details>): JS yo'q bo'lsa ham ishlaydi. JS: aria, Esc, tashqariga bosish, strelkalar, bittasi ochilsa ikkinchisi yopiladi.
(function () {
  var items = Array.prototype.slice.call(document.querySelectorAll('[data-dropdown]'));
  if (!items.length) return;

  function trigger(d) { return d.querySelector('summary'); }
  function focusables(d) {
    return Array.prototype.slice.call(d.querySelectorAll('[data-dropdown-panel] a[href], [data-dropdown-panel] button'));
  }
  function sync(d) { trigger(d).setAttribute('aria-expanded', d.open ? 'true' : 'false'); }

  items.forEach(function (d, i) {
    var s = trigger(d);
    var panel = d.querySelector('[data-dropdown-panel]');
    if (panel) {
      if (!panel.id) panel.id = 'nav-dd-panel-' + i;
      s.setAttribute('aria-controls', panel.id);
    }
    s.setAttribute('aria-haspopup', 'true');
    sync(d);

    d.addEventListener('toggle', function () {
      sync(d);
      if (d.open) items.forEach(function (o) { if (o !== d && o.open) o.open = false; });
    });

    s.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowDown') return;
      e.preventDefault();
      d.open = true;
      var first = focusables(d)[0];
      if (first) first.focus();
    });

    d.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && d.open) {
        d.open = false;
        s.focus();
        return;
      }
      if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && e.target !== s) {
        var list = focusables(d);
        var idx = list.indexOf(document.activeElement);
        if (idx < 0) return;
        e.preventDefault();
        var step = e.key === 'ArrowDown' ? 1 : -1;
        list[(idx + step + list.length) % list.length].focus();
      }
    });

    d.addEventListener('focusout', function (e) {
      if (d.open && e.relatedTarget && !d.contains(e.relatedTarget)) d.open = false;
    });
  });

  document.addEventListener('click', function (e) {
    items.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
  });

  document.querySelectorAll('[data-dispatch]').forEach(function (b) {
    b.addEventListener('click', function () {
      window.dispatchEvent(new CustomEvent(b.getAttribute('data-dispatch')));
      items.forEach(function (d) { d.open = false; });
    });
  });
})();

// Header category pills: only those that fit on the single row stay visible and focusable.
(function () {
  var nav = document.querySelector('[data-fit-pills]');
  if (!nav) return;

  function fit() {
    var pills = Array.prototype.slice.call(nav.children);
    if (!pills.length) return;
    var top = Math.min.apply(null, pills.map(function (p) { return p.offsetTop; }));
    pills.forEach(function (p) {
      var fits = p.offsetTop === top && p.offsetWidth <= nav.clientWidth - 8;
      p.style.visibility = fits ? '' : 'hidden';
    });
  }

  window.addEventListener('resize', fit, { passive: true });
  window.addEventListener('load', fit);
  if (document.fonts) document.fonts.ready.then(fit);
  fit();
})();
