// Brand carousel: CSS scroll-snap + prev/next buttons, arrow keys, touch (native) and mouse drag. No libraries.
(function () {
  document.querySelectorAll('[data-brand-carousel]').forEach(function (root) {
    var track = root.querySelector('[data-carousel-track]');
    var prev = root.querySelector('[data-carousel-prev]');
    var next = root.querySelector('[data-carousel-next]');
    if (!track || !prev || !next) return;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var queued = false;

    function stepSize() {
      var card = track.querySelector('[data-carousel-item]');
      if (!card) return track.clientWidth;
      var gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
      return card.getBoundingClientRect().width + gap;
    }

    function scrollByDir(dir) {
      track.scrollBy({ left: dir * stepSize(), behavior: reduceMotion ? 'auto' : 'smooth' });
    }

    function setDisabled(btn, off) {
      btn.disabled = off;
      btn.setAttribute('aria-disabled', off ? 'true' : 'false');
    }

    function update() {
      queued = false;
      var max = track.scrollWidth - track.clientWidth;
      var overflow = max > 2;
      prev.classList.toggle('hidden', !overflow);
      next.classList.toggle('hidden', !overflow);
      setDisabled(prev, track.scrollLeft <= 1);
      setDisabled(next, track.scrollLeft >= max - 1);
    }

    function queueUpdate() {
      if (queued) return;
      queued = true;
      window.requestAnimationFrame(update);
    }

    prev.addEventListener('click', function () { scrollByDir(-1); });
    next.addEventListener('click', function () { scrollByDir(1); });

    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { e.preventDefault(); scrollByDir(-1); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); scrollByDir(1); }
      else if (e.key === 'Home') { e.preventDefault(); track.scrollTo({ left: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); }
      else if (e.key === 'End') { e.preventDefault(); track.scrollTo({ left: track.scrollWidth, behavior: reduceMotion ? 'auto' : 'smooth' }); }
    });

    // Touch swipe works natively (overflow scroll + snap). Mouse drag is added by hand.
    var drag = null;
    var moved = false;

    track.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      drag = { x: e.clientX, left: track.scrollLeft };
      moved = false;
    });

    window.addEventListener('pointermove', function (e) {
      if (!drag) return;
      var dx = e.clientX - drag.x;
      if (!moved && Math.abs(dx) > 5) {
        moved = true;
        track.style.scrollSnapType = 'none';
        track.style.scrollBehavior = 'auto';
        track.style.cursor = 'grabbing';
      }
      if (moved) track.scrollLeft = drag.left - dx;
    });

    function endDrag() {
      if (!drag) return;
      drag = null;
      track.style.scrollSnapType = '';
      track.style.scrollBehavior = '';
      track.style.cursor = '';
    }
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);

    track.addEventListener('click', function (e) {
      if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
    }, true);
    track.addEventListener('dragstart', function (e) { e.preventDefault(); });

    track.addEventListener('scroll', queueUpdate, { passive: true });
    window.addEventListener('resize', queueUpdate);
    if (window.ResizeObserver) new ResizeObserver(queueUpdate).observe(track);
    track.querySelectorAll('img').forEach(function (img) { img.addEventListener('load', queueUpdate); });
    update();
  });
})();
