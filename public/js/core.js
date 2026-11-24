// Global helpers: window.FoodMart = { api(), toast(), escape(), money() }
(function () {
  const fm = (window.FoodMart = window.FoodMart || {});
  const meta = document.querySelector('meta[name="csrf-token"]');

  fm.escape = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  fm.money = (n) => '$' + Number(n || 0).toFixed(2);

  // JSON request to the server. Rejects with Error(message) on 4xx/5xx.
  fm.api = async function (method, url, body) {
    const res = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': meta ? meta.content : '',
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });

    let data = {};
    try { data = await res.json(); } catch (e) { /* empty body */ }

    if (!res.ok) {
      let message = data.message || 'Something went wrong. Please try again.';
      if (data.errors) {
        const first = Object.values(data.errors)[0];
        if (first && first[0]) message = first[0];
      }
      const err = new Error(message);
      err.status = res.status;
      throw err;
    }
    return data;
  };

  fm.toast = function (message, type) {
    let box = document.getElementById('fm-toast-box');
    if (!box) {
      box = document.createElement('div');
      box.id = 'fm-toast-box';
      box.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[70] flex flex-col items-center gap-2 pointer-events-none';
      document.body.appendChild(box);
    }
    const el = document.createElement('div');
    el.className = 'px-4 py-2.5 rounded-full text-xs font-mono font-bold shadow-xl border backdrop-blur ' +
      (type === 'error' ? 'bg-red-500/90 text-white border-red-300/40' : 'bg-primary text-black border-white/20');
    el.textContent = message;
    box.appendChild(el);
    setTimeout(() => {
      el.style.transition = 'opacity .3s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 300);
    }, 2600);
  };
})();
