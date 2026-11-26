/* Header tugmalari: joylashuv yangilash va support chat ochish. core.js dan keyin yuklanadi. */
(function () {
  const STORAGE_KEY = 'fm_location';

  function read() {
    try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
  }
  function write(value) {
    try { localStorage.setItem(STORAGE_KEY, value); } catch (e) { /* storage blocked */ }
  }
  function toast(message, type) {
    if (window.FoodMart && window.FoodMart.toast) window.FoodMart.toast(message, type);
  }

  /* ---- Joylashuv ---- */
  const btn = document.getElementById('location-btn');
  if (btn) {
    const label = document.getElementById('location-label');
    const spinner = document.getElementById('location-refresh');

    const saved = read();
    if (saved && label) label.textContent = saved;

    function busy(on) {
      btn.disabled = on;
      btn.classList.toggle('opacity-70', on);
      if (spinner) spinner.classList.toggle('animate-spin', on);
    }

    btn.addEventListener('click', () => {
      if (!navigator.geolocation) {
        toast('Your browser does not support geolocation.', 'error');
        return;
      }

      busy(true);
      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          try {
            const data = await window.FoodMart.api('POST', btn.dataset.url, {
              lat: pos.coords.latitude,
              lng: pos.coords.longitude,
            });
            if (label) label.textContent = data.name;
            write(data.name);
            toast('Location updated: ' + data.name);
          } catch (e) {
            toast(e.message, 'error');
          } finally {
            busy(false);
          }
        },
          (err) => {
              busy(false);

              console.error('Geolocation error:', {
                  code: err.code,
                  message: err.message,
              });

              const messages = {
                  1: 'Location permission denied. Allow it in your browser settings.',
                  2: 'Position unavailable: ' + err.message,
                  3: 'Location request timed out. Try again.',
              };

              toast(messages[err.code] || `Location error ${err.code}: ${err.message}`, 'error');
          },
        { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 }
      );
    });
  }

  /* ---- 24/7 Concierge Hotline -> support chat ----
     Chat vidjeti (6-faza) 'support-chat:toggle' ni tinglab, event.detail.handled = true qo'yadi. */
  const hotline = document.getElementById('hotline-btn');
  if (hotline) {
    hotline.addEventListener('click', () => {
      const event = new CustomEvent('support-chat:toggle', { detail: { handled: false } });
      window.dispatchEvent(event);
      if (!event.detail.handled) {
        toast('Live chat opens soon. Call +998-90-188-6641 meanwhile.');
      }
    });
  }
})();
