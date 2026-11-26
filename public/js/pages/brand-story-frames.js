// Decodes the story video into still frames once, so scrubbing no longer waits for video seeks.
window.BrandStoryFrames = function (url, canvas, onReady) {
  if (!window.createImageBitmap) return;

  const small = window.matchMedia('(max-width: 767px)').matches || (navigator.deviceMemory || 8) <= 4;
  const limit = small
    ? { width: 720, bytes: 160e6, frames: 90 }
    : { width: 1280, bytes: 400e6, frames: 144 };
  const source = document.createElement('video');
  const frames = [];

  source.muted = true;
  source.playsInline = true;
  source.preload = 'auto';

  function once(name) {
    return new Promise((resolve, reject) => {
      const ok = () => done(resolve);
      const fail = () => done(reject, new Error('video error'));
      const timer = setTimeout(() => done(reject, new Error('timeout')), 8000);

      function done(settle, value) {
        clearTimeout(timer);
        source.removeEventListener(name, ok);
        source.removeEventListener('error', fail);
        settle(value);
      }

      source.addEventListener(name, ok);
      source.addEventListener('error', fail);
    });
  }

  async function build() {
    const metadata = once('loadedmetadata');
    source.src = url;
    source.load();
    await metadata;

    const duration = source.duration;
    if (!Number.isFinite(duration) || duration > 20 || !source.videoWidth) return;

    const count = Math.max(2, Math.min(limit.frames, Math.ceil(duration * 24)));
    const ratio = source.videoHeight / source.videoWidth;
    const width = Math.floor(Math.min(source.videoWidth, limit.width, Math.sqrt(limit.bytes / (count * 4 * ratio))));
    const height = Math.round(width * ratio);

    const work = document.createElement('canvas');
    work.width = width;
    work.height = height;
    const workContext = work.getContext('2d');
    const step = (duration - 0.05) / (count - 1);

    function grab() {
      workContext.clearRect(0, 0, width, height);
      workContext.drawImage(source, 0, 0, width, height);
      if (!frames.length && workContext.getImageData(width >> 1, height >> 1, 1, 1).data[3] === 0) {
        throw new Error('empty frame');
      }
      return createImageBitmap(work);
    }

    // Playing once decodes every frame in order, far quicker than one seek per frame.
    function capturePlaying() {
      return new Promise((resolve, reject) => {
        let timer;

        function finish(error) {
          clearTimeout(timer);
          source.pause();
          if (error) reject(error);
          else resolve();
        }

        function watch() {
          clearTimeout(timer);
          timer = setTimeout(() => finish(new Error('timeout')), 8000);
          source.requestVideoFrameCallback(onFrame);
        }

        function onFrame(now, frame) {
          try {
            let shot = null;
            while (frames.length < count && frame.mediaTime >= (frames.length - 0.5) * step) {
              shot = shot || grab();
              frames.push(shot);
            }
          } catch (error) {
            finish(error);
            return;
          }
          if (frames.length >= count) finish();
          else watch();
        }

        source.addEventListener('ended', () => {
          while (frames.length && frames.length < count) frames.push(frames[frames.length - 1]);
          finish(frames.length ? null : new Error('no frames'));
        }, { once: true });

        watch();
        source.play().catch(finish);
      });
    }

    async function captureSeeking() {
      for (let i = 0; i < count; i++) {
        const seeked = once('seeked');
        source.currentTime = Math.max(i * step, 0.001);
        await seeked;
        frames.push(await grab());
      }
    }

    if (source.requestVideoFrameCallback) await capturePlaying();
    else await captureSeeking();

    const bitmaps = await Promise.all(frames);

    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d', { alpha: false });
    let last = -1;

    onReady({
      draw(progress) {
        const index = Math.round(progress * (count - 1));
        if (index === last) return;
        last = index;
        context.drawImage(bitmaps[index], 0, 0);
      },
    });
  }

  build()
    .catch(() => frames.splice(0).forEach((frame) => Promise.resolve(frame).then((bitmap) => bitmap.close()).catch(() => {})))
    .finally(() => {
      source.removeAttribute('src');
      source.load();
    });
};
