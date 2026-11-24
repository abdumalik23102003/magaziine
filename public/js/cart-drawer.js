// Server-backed shopping-cart drawer.
// window.FoodMartCart = { add(variantId, qty), set(variantId, qty), remove(variantId), clear(), open(), close(), setState(cart), state() }
(function () {
  const fm = window.FoodMart;
  const drawer = document.getElementById('cart-drawer');
  if (!fm || !drawer) return;

  const $ = (id) => document.getElementById(id);
  const backdrop = $('cart-backdrop');
  const list = $('cart-items-list');
  const emptyState = $('cart-empty-state');
  const footer = $('cart-footer');
  const clearBtn = $('cart-clear-all-btn');
  const badge = $('nav-cart-count');

  let state = fm.cart || { items: [], count: 0 };
  let busy = false;

  function rowHtml(i) {
    const e = fm.escape;
    const thumb = i.img
      ? `<img class="w-full h-full object-contain p-1.5" src="${e(i.img)}" alt="${e(i.name)}" loading="lazy">`
      : '<span class="text-3xl">🛒</span>';
    const tag = i.discount > 0
      ? `<span class="inline-flex text-[9px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-accent-lime/15 text-accent-lime border border-accent-lime/30">-${i.discount}%</span>`
      : '<span class="inline-flex text-[9px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">In stock</span>';
    const old = i.old_price ? `<span class="text-[11px] text-zinc-500 line-through ml-1">$${e(i.old_price)}</span>` : '';
    return `
      <div class="cart-item group relative p-4 rounded-2xl bg-surface-obsidian/75 border border-white/10 hover:border-primary/40 transition-all" data-variant-id="${i.id}">
        <div class="flex items-start gap-4">
          <a href="${e(i.url)}" class="w-16 h-16 rounded-xl bg-surface-carbon border border-white/10 flex items-center justify-center flex-shrink-0 overflow-hidden">${thumb}</a>
          <div class="flex-1 min-w-0 pr-6">
            <div class="flex items-center gap-2 mb-1">${tag}<span class="text-[10px] text-zinc-400 font-mono truncate">${e(i.variant)}</span></div>
            <a href="${e(i.url)}" class="block font-display font-bold text-sm text-text-crisp truncate hover:text-primary transition-colors">${e(i.name)}</a>
            <p class="text-xs text-text-muted mt-0.5">$${e(i.price)}${old}</p>
            <div class="flex items-center justify-between mt-3">
              <div class="inline-flex items-center rounded-lg bg-surface-carbon border border-border-glass p-0.5">
                <button type="button" class="cart-step w-6 h-6 rounded flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/5 text-xs" data-delta="-1" aria-label="Decrease"><i class="bi bi-dash"></i></button>
                <span class="w-7 text-center font-mono font-bold text-xs text-text-crisp">${i.qty}</span>
                <button type="button" class="cart-step w-6 h-6 rounded flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/5 text-xs" data-delta="1" aria-label="Increase"><i class="bi bi-plus"></i></button>
              </div>
              <div class="font-display font-black text-sm text-text-crisp">$${e(i.line_total)}</div>
            </div>
          </div>
        </div>
        <button type="button" class="cart-remove absolute top-3 right-3 w-6 h-6 rounded-md text-zinc-500 hover:text-red-400 hover:bg-red-500/10 flex items-center justify-center" aria-label="Remove"><i class="bi bi-x-lg text-xs"></i></button>
      </div>`;
  }

  function render(pulse) {
    const items = state.items || [];
    const empty = items.length === 0;

    list.innerHTML = items.map(rowHtml).join('');
    emptyState.classList.toggle('hidden', !empty);
    emptyState.classList.toggle('flex', empty);
    footer.classList.toggle('opacity-40', empty);
    footer.classList.toggle('pointer-events-none', empty);
    clearBtn.classList.toggle('opacity-40', empty);
    clearBtn.classList.toggle('pointer-events-none', empty);

    $('cart-drawer-count').textContent = `${state.count} ${state.count === 1 ? 'item' : 'items'}`;
    $('cart-subtotal').textContent = '$' + (state.subtotal || '0.00');
    $('cart-tax').textContent = '$' + (state.tax || '0.00');
    $('cart-tax-row').classList.toggle('hidden', !state.tax_rate);
    $('cart-total-value').textContent = '$' + (state.total || '0.00');
    $('cart-checkout-label').textContent = `Proceed to Checkout ($${state.total || '0.00'})`;
    $('cart-progress-bar').style.width = (state.progress || 0) + '%';
    $('cart-delivery-text').textContent = state.qualifies || empty
      ? (empty ? `Free delivery from $${state.free_from}` : 'Free delivery unlocked!')
      : `Add $${state.remaining} more for free delivery`;
    $('cart-delivery-value').textContent = state.qualifies ? 'FREE' : 'at checkout';

    if (badge) {
      badge.textContent = state.count;
      badge.style.display = state.count > 0 ? 'flex' : 'none';
      if (pulse) {
        badge.classList.add('scale-125');
        setTimeout(() => badge.classList.remove('scale-125'), 180);
      }
    }
    document.dispatchEvent(new CustomEvent('fm:cart', { detail: state }));
  }

  async function call(method, url, body, successMessage) {
    if (busy) return false;
    busy = true;
    try {
      const data = await fm.api(method, url, body);
      state = data.cart;
      render(true);
      if (successMessage) fm.toast(successMessage);
      return true;
    } catch (err) {
      fm.toast(err.message, 'error');
      return false;
    } finally {
      busy = false;
    }
  }

  const base = fm.urls.cart;

  const api = {
    add: (variantId, qty) => call('POST', base + '/items', { variant_id: Number(variantId), quantity: Number(qty) || 1 }, 'Added to cart'),
    set: (variantId, qty) => call('PATCH', `${base}/items/${variantId}`, { quantity: qty }),
    remove: (variantId) => call('DELETE', `${base}/items/${variantId}`),
    clear: () => call('DELETE', base),
    setState: (cart) => { state = cart; render(false); },
    state: () => state,
  };

  function open() {
    if (window.FoodMartWishlist) window.FoodMartWishlist.close();
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

  list.addEventListener('click', (e) => {
    const row = e.target.closest('.cart-item');
    if (!row) return;
    const id = row.dataset.variantId;
    const item = (state.items || []).find((i) => String(i.id) === id);
    if (!item) return;

    if (e.target.closest('.cart-remove')) { api.remove(id); return; }

    const step = e.target.closest('.cart-step');
    if (step) {
      const next = item.qty + Number(step.dataset.delta);
      next < 1 ? api.remove(id) : api.set(id, next);
    }
  });

  clearBtn.addEventListener('click', () => api.clear());
  $('cart-checkout-btn').addEventListener('click', () => { window.location.href = fm.urls.checkout; });
  [ $('cart-close-btn'), $('cart-continue-btn'), $('cart-shop-now-btn'), backdrop ].forEach((el) => el && el.addEventListener('click', close));

  const toggle = $('cart-toggle-btn');
  if (toggle) {
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      drawer.classList.contains('translate-x-0') ? close() : open();
    });
  }
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('translate-x-0')) close();
  });

  window.FoodMartCart = Object.assign(api, { open, close });
  render(false);
})();
