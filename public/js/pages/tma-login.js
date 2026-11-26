// Mini App kirish sahifasi: Telegram initData serverga yuboriladi, tekshiruv serverda.
(function () {
  const box = document.getElementById('tma-login');
  if (!box) return;

  const tg = window.Telegram && window.Telegram.WebApp;
  const status = document.getElementById('tma-status');
  const retry = document.getElementById('tma-retry');
  const KEY = 'tma_signins';

  function show(message, canRetry) {
    status.textContent = message;
    retry.hidden = !canRetry;
  }

  // Cookie bloklansa "kirish -> sahifa -> yana kirish" aylanishi bo'ladi: 30 soniyada 3 martadan keyin to'xtaymiz.
  function recent() {
    try {
      return JSON.parse(sessionStorage.getItem(KEY) || '[]').filter((t) => Date.now() - t < 30000);
    } catch (e) {
      return [];
    }
  }
  function remember(list) {
    try { sessionStorage.setItem(KEY, JSON.stringify(list)); } catch (e) { /* storage blocked */ }
  }

  async function signIn() {
    if (!tg || !tg.initData) {
      show('Open this shop from the Telegram bot.', false);
      return;
    }
    if (recent().length >= 3) {
      show('Could not keep you signed in. Cookies may be blocked: try the Telegram mobile app.', true);
      return;
    }

    show('Signing you in...', false);

    try {
      const res = await fetch(box.dataset.url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ init_data: tg.initData }),
      });
      const data = await res.json().catch(() => ({}));

      if (res.status === 419) throw new Error('Session could not be created. Close the shop and open it again.');
      if (!res.ok || !data.redirect) throw new Error(data.message || 'Sign in failed.');

      remember(recent().concat(Date.now()));
      window.location.replace(data.redirect);
    } catch (e) {
      show(e.message, true);
    }
  }

  retry.addEventListener('click', () => {
    remember([]);
    signIn();
  });

  signIn();
})();
