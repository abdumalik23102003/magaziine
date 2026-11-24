// Server-backed wishlist drawer.
// window.FoodMartWishlist = { toggle(productId), has(productId), open(), close(), setState(w), state() }
(function () {
  const fm = window.FoodMart;
  const drawer = document.getElementById('wishlist-drawer');
  if (!fm || !drawer) return;

  const $ = (id) => document.getElementById(id);
  const backdrop = $('wishlist-backdrop');
  const list = $('wishlist-items-list');
  const emptyState = $('wishlist-empty-state');
  const footer = $('wishlist-footer');
  const clearBtn = $('clear-wishlist-btn');
  const badge = $('nav-wishlist-count');

  let state = fm.wishlist || { items: [], count: 0, total: '0.00' };
  let busy = false;

  function rowHtml(i) {
    const e = fm.escape;
    const thumb = i.img
      ? `<img class="w-full h-full object-contain p-1.5" src="${e(i.img)}" alt="${e(i.name)}" loading="lazy">`
      : '<span class="text-3xl">🛒</span>';
    const old = i.old_price ? `<span class="text-[11px] text-zinc-500 line-through">$${e(i.old_price)}</span>` : '';
    const action = i.variant_id
      ? `<button type="button" class="wl-add px-3 py-1 rounded-full bg-primary text-black font-display font-bold text-xs uppercase tracking-wider hover:bg-emerald-400 transition-all flex items-center gap-1"><i class="bi bi-cart-plus-fill"></i> Add</button>`
      : '<span class="text-[11px] font-mono text-zinc-500">Out of stock</span>';
    return `
      <div class="wishlist-item group relative p-4 rounded-2xl bg-surface-obsidian/60 border border-white/5 hover:border-primary/40 transition-all" data-product-id="${i.id}">
        <div class="flex items-start gap-4">
          <a href="${e(i.url)}" class="w-16 h-16 rounded-xl bg-surface-carbon border border-white/10 flex items-center justify-center flex-shrink-0 overflow-hidden">${thumb}</a>
          <div class="flex-1 min-w-0 pr-6">
            <span class="text-[10px] text-zinc-400 font-mono">${e(i.category || '')}</span>
            <a href="${e(i.url)}" class="block font-display font-bold text-sm text-text-crisp truncate hover:text-primary transition-colors">${e(i.name)}</a>
            <p class="text-xs text-text-muted mt-0.5">${e(i.variant || '')}</p>
            <div class="flex items-center justify-between mt-3">
              <div class="flex items-baseline gap-1.5 font-display"><span class="text-base font-black text-text-crisp">$${e(i.price)}</span>${old}</div>
              ${action}
            </div>
          </div>
        </div>
        <button type="button" class="wl-remove absolute top-3 right-3 w-6 h-6 rounded-md text-zinc-500 hover:text-red-400 hover:bg-red-500/10 flex items-center justify-center" aria-label="Remove"><i class="bi bi-x-lg text-xs"></i></button>
      </div>`;
  }

  function render() {
    const items = state.items || [];
    const empty = items.length === 0;
    list.innerHTML = items.map(rowHtml).join('');
    emptyState.classList.toggle('hidden', !empty);
    emptyState.classList.toggle('flex', empty);
    footer.classList.toggle('opacity-40', empty);
    footer.classList.toggle('pointer-events-none', empty);
    clearBtn.classList.toggle('opacity-40', empty);
    clearBtn.classList.toggle('pointer-events-none', empty);
    $('drawer-counter-badge').textContent = `${state.count} ${state.count === 1 ? 'item' : 'items'}`;
    $('wishlist-total-value').textContent = '$' + (state.total || '0.00');
    if (badge) {
      badge.textContent = state.count;
      badge.style.display = state.count > 0 ? 'flex' : 'none';
    }
    document.dispatchEvent(new CustomEvent('fm:wishlist', { detail: { ids: items.map((i) => i.id) } }));
  }

  async function call(method, url, body) {
    if (busy) return null;
    busy = true;
    try {
      const data = await fm.api(method, url, body);
      state = data.wishlist;
      render();
      return data;
    } catch (err) {
      fm.toast(err.message, 'error');
      return null;
    } finally {
      busy = false;
    }
  }

  const base = fm.urls.wishlist;

  const api = {
    has: (id) => (state.items || []).some((i) => String(i.id) === String(id)),
    async toggle(id) {
      const data = await call('POST', `${base}/${id}`);
      if (data) fm.toast(data.added ? 'Saved to favorites' : 'Removed from favorites');
      return data;
    },
    setState: (w) => { state = w; render(); },
    state: () => state,
  };

  function open() {
    if (window.FoodMartCart) window.FoodMartCart.close();
    drawer.classList.replace('translate-x-full', 'translate-x-0');
    backdrop.classList.remove('opacity-0', 'pointer-events-none');
    backdrop.classList.add('opacity-100', 'pointer-events-auto');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    drawer.classList.replace('translate-x-0', 'translate-x-full');
    backdrop.classList.remove('opacity-100', 'pointer-events-auto');
    backdrop.classList.add('opacity-0', 'pointer-events-none');
    document.body.style.overflow = '';
  }

  list.addEventListener('click', async (e) => {
    const row = e.target.closest('.wishlist-item');
    if (!row) return;
    const id = row.dataset.productId;

    if (e.target.closest('.wl-remove')) { call('DELETE', `${base}/${id}`); return; }

    if (e.target.closest('.wl-add')) {
      const item = (state.items || []).find((i) => String(i.id) === id);
      if (item && item.variant_id && window.FoodMartCart) window.FoodMartCart.add(item.variant_id, 1);
    }
  });

  clearBtn.addEventListener('click', () => call('DELETE', base));

  $('move-all-cart-btn').addEventListener('click', async () => {
    const data = await call('POST', `${base}/move-to-cart`);
    if (data && data.cart && window.FoodMartCart) {
      window.FoodMartCart.setState(data.cart);
      fm.toast(data.message || 'Moved to cart');
    }
  });

  [ $('close-wishlist-btn'), $('continue-shopping-btn'), $('explore-items-btn'), backdrop ].forEach((el) => el && el.addEventListener('click', close));

  const toggleBtn = $('wishlist-toggle-btn');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      drawer.classList.contains('translate-x-0') ? close() : open();
    });
  }
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('translate-x-0')) close();
  });

  window.FoodMartWishlist = Object.assign(api, { open, close });
  render();
})();
