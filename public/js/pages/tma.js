// Mini App ning umumiy skripti: Telegram tugmalari, savatga qo'shish, formalar.
(function () {
  const tg = window.Telegram && window.Telegram.WebApp;
  const body = document.body;
  const meta = (name) => {
    const el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.content : '';
  };

  if (tg) {
    tg.ready();
    tg.expand();
  }

  // Telegramda hisob almashgan bo'lsa (cookie eski hisobniki), qayta kiramiz. Server baribir imzoni tekshiradi.
  const mine = meta('tg-user-id');
  const current = tg && tg.initDataUnsafe && tg.initDataUnsafe.user;
  if (mine && current && String(current.id) !== mine && !('bare' in body.dataset)) {
    window.location.replace(body.dataset.login);
    return;
  }

  if (tg && tg.initData && tg.BackButton) {
    if (body.dataset.back) {
      tg.BackButton.show();
      tg.BackButton.onClick(() => window.location.assign(body.dataset.back));
    } else {
      tg.BackButton.hide();
    }
  }

  function haptic(type) {
    if (tg && tg.initData && tg.HapticFeedback) tg.HapticFeedback.notificationOccurred(type);
  }

  const toast = document.getElementById('tma-toast');
  let timer;
  function say(message, isError) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.toggle('toast-error', Boolean(isError));
    toast.hidden = false;
    clearTimeout(timer);
    timer = setTimeout(() => { toast.hidden = true; }, 2500);
  }

  function badge(count) {
    const el = document.getElementById('tma-cart-count');
    if (!el) return;
    el.textContent = count;
    el.hidden = !count;
  }

  document.querySelectorAll('form[data-ajax]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = form.querySelector('[type="submit"]');
      if (button.disabled) return;
      button.disabled = true;

      try {
        const res = await fetch(form.getAttribute('action'), {
          method: 'POST',
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': meta('csrf-token') },
          body: new FormData(form),
        });
        if (res.status === 419) {
          window.location.reload();
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.cart) throw new Error(data.message || 'Something went wrong.');

        badge(data.cart.count);
        say(data.message || 'Added to cart');
        haptic('success');
      } catch (e) {
        say(e.message, true);
        haptic('error');
      } finally {
        button.disabled = false;
      }
    });
  });

  // Ikki marta bosilsa ikkinchi buyurtma ketmasin.
  document.querySelectorAll('form[data-once]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('[type="submit"]');
      setTimeout(() => { button.disabled = true; }, 0);
    });
  });
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
  });

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const question = form.dataset.confirm;
      if (tg && tg.initData && tg.showConfirm && tg.isVersionAtLeast('6.2')) {
        tg.showConfirm(question, (yes) => { if (yes) form.submit(); });
      } else if (window.confirm(question)) {
        form.submit();
      }
    });
  });

  // Checkout: tanlangan yetkazish narxiga qarab jami summa (server qayta hisoblaydi).
  const total = document.getElementById('tma-total');
  if (total) {
    const render = () => {
      const picked = document.querySelector('input[name="delivery_method"]:checked');
      const fee = picked ? parseFloat(picked.dataset.fee || '0') : 0;
      total.textContent = '$' + (parseFloat(total.dataset.base) + fee).toFixed(2);
    };
    document.querySelectorAll('input[name="delivery_method"]').forEach((r) => r.addEventListener('change', render));
    render();
  }

  // Checkout: ixtiyoriy joylashuv (kuryer uchun koordinata).
  const locate = document.getElementById('tma-locate');
  if (locate) {
    const info = document.getElementById('tma-locate-info');
    locate.addEventListener('click', () => {
      if (!navigator.geolocation) {
        info.textContent = 'Location is not available on this device.';
        return;
      }
      locate.disabled = true;
      info.textContent = 'Locating...';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          document.getElementById('delivery_lat').value = pos.coords.latitude.toFixed(7);
          document.getElementById('delivery_lng').value = pos.coords.longitude.toFixed(7);
          info.textContent = 'Location added.';
          locate.disabled = false;
        },
        () => {
          info.textContent = 'Could not get your location. Allow access and try again.';
          locate.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }
})();
