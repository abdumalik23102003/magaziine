(function initButtonWaterRipples() {
  const rippleTargets = document.querySelectorAll('.water-ripple-target');
  
  rippleTargets.forEach(btn => {
    btn.addEventListener('click', function(e) {
      let pond = this.querySelector('.pond-ripple-container');
      if (!pond) {
        pond = document.createElement('div');
        pond.className = 'pond-ripple-container';
        this.appendChild(pond);
      }

      const rect = this.getBoundingClientRect();
      const x = (e.clientX || (rect.left + rect.width / 2)) - rect.left;
      const y = (e.clientY || (rect.top + rect.height / 2)) - rect.top;

      const maxDistX = Math.max(x, rect.width - x);
      const maxDistY = Math.max(y, rect.height - y);
      const radius = Math.hypot(maxDistX, maxDistY);
      const diameter = radius * 2.4;

      const waves = ['wave-sheen', 'wave-1', 'wave-2', 'wave-3'];
      
      waves.forEach(waveClass => {
        const ring = document.createElement('span');
        ring.className = `pond-wave ${waveClass}`;
        ring.style.width = `${diameter}px`;
        ring.style.height = `${diameter}px`;
        ring.style.left = `${x}px`;
        ring.style.top = `${y}px`;
        pond.appendChild(ring);

        setTimeout(() => {
          if (ring && ring.parentNode) {
            ring.remove();
          }
        }, 1100);
      });
    });
  });
})();
