// Brand page: scroll position drives the story video (GSAP ScrollTrigger scrub).
(function () {
  const section = document.querySelector('[data-brand-story]');
  if (!section) return;

  const video = section.querySelector('video');
  const copy = section.querySelector('[data-story-copy]');
  const hint = section.querySelector('[data-story-hint]');
  const bar = section.querySelector('[data-story-bar]');
  const canvas = section.querySelector('[data-story-canvas]');
  const header = document.querySelector('header');
  const src = video.dataset.src;

  const headerHeight = () => (header ? header.offsetHeight : 0);
  const syncTop = () => section.style.setProperty('--story-top', headerHeight() + 'px');
  syncTop();

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const saveData = Boolean(navigator.connection && navigator.connection.saveData);

  function makeStatic() {
    section.classList.add('is-static', 'is-ready');
    video.controls = true;
    video.preload = 'metadata';
    if (!video.getAttribute('src')) video.src = src;
  }

  video.addEventListener('error', () => {
    if (video.getAttribute('src')) section.style.display = 'none';
    if (window.ScrollTrigger) window.ScrollTrigger.refresh();
  });

  if (reduced || saveData || !window.gsap || !window.ScrollTrigger) {
    makeStatic();
    return;
  }

  let progress = 0;
  let queued = false;
  let frames = null;

  function seek() {
    queued = false;
    if (frames) return;
    if (video.readyState < 1 || !Number.isFinite(video.duration)) return;
    if (video.seeking) return;
    const target = progress * Math.max(video.duration - 0.05, 0);
    if (Math.abs(video.currentTime - target) > 0.02) video.currentTime = target;
  }

  function requestSeek() {
    if (queued) return;
    queued = true;
    window.requestAnimationFrame(seek);
  }

  function render(value) {
    progress = Math.min(Math.max(value, 0), 1);
    bar.style.transform = 'scaleX(' + progress + ')';
    hint.style.opacity = progress > 0.04 ? '0' : '1';
    if (copy) {
      const fade = Math.min(Math.max((progress - 0.6) / 0.25, 0), 1);
      copy.style.opacity = String(1 - fade);
      copy.style.transform = 'translateY(' + (-40 * fade) + 'px)';
    }
    if (frames) frames.draw(progress);
    else requestSeek();
  }

  video.addEventListener('seeked', requestSeek);
  video.addEventListener('loadeddata', () => {
    section.classList.add('is-ready');
    requestSeek();
  });

  // iOS paints seeked frames only after the video has been played once.
  function unlock() {
    const attempt = video.play();
    if (attempt) attempt.then(() => { video.pause(); requestSeek(); }).catch(() => {});
  }
  video.addEventListener('loadedmetadata', () => { unlock(); requestSeek(); }, { once: true });
  window.addEventListener('touchstart', unlock, { once: true, passive: true });

  window.gsap.registerPlugin(window.ScrollTrigger);

  const state = { p: 0 };
  window.gsap.to(state, {
    p: 1,
    ease: 'none',
    onUpdate: () => render(state.p),
    scrollTrigger: {
      trigger: section,
      start: () => 'top top+=' + headerHeight(),
      end: 'bottom bottom',
      scrub: 1,
      invalidateOnRefresh: true,
    },
  });

  window.addEventListener('resize', syncTop);
  window.addEventListener('load', () => window.ScrollTrigger.refresh());

  function load(url) {
    video.preload = 'auto';
    video.src = url;
    video.load();
  }

  // A blob URL is fully seekable even when the server ignores HTTP Range requests (php artisan serve).
  fetch(src)
    .then((response) => {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.blob();
    })
    .then((blob) => {
      const url = URL.createObjectURL(blob);
      load(url);
      if (window.BrandStoryFrames && canvas) {
        window.BrandStoryFrames(url, canvas, (ready) => {
          frames = ready;
          frames.draw(progress);
          section.classList.add('has-frames');
        });
      }
    })
    .catch(() => load(src));
})();
