// Product page only: pack (variant) selection, quantity, price, tabs, gallery, add to cart, wishlist, toast.
(function () {
  const root = document.getElementById('product-root');
  if (!root) return;

  const fm = window.FoodMart;
  const $ = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));
  const money = (n) => '$' + Number(n).toFixed(2);

  const state = { qty: 1, price: parseFloat(root.dataset.price), variantId: null };

  function renderPrice() {
    const total = money(state.price * state.qty);
    $('#display-price').textContent = total;
    $('#cta-text').textContent = '+ Add to Cart • ' + total;
  }

  const SEL = ['active', 'bg-surface-elevated', 'ring-1', 'ring-accent-hyper-emerald', 'shadow-sm'];
  function select(group, btn) {
    group.forEach((b) => { b.classList.remove(...SEL); b.classList.add('bg-surface-slate'); });
    btn.classList.add(...SEL);
    btn.classList.remove('bg-surface-slate');
  }

  // Pack = one product variant. data-pack holds the variant id.
  const packs = $$('#pack-options .pack-btn');
  function applyPack(btn) {
    select(packs, btn);
    state.price = parseFloat(btn.dataset.price);
    state.variantId = btn.dataset.pack;
    const oldEl = $('#old-price');
    if (oldEl) { oldEl.textContent = btn.dataset.old ? money(btn.dataset.old) : ''; oldEl.classList.toggle('hidden', !btn.dataset.old); }
    const badgeEl = $('#discount-badge');
    if (badgeEl) { badgeEl.textContent = btn.dataset.badge || ''; badgeEl.classList.toggle('hidden', !btn.dataset.badge); }
    $('#size-label').textContent = btn.dataset.label;
    renderPrice();
  }
  packs.forEach((btn) => btn.addEventListener('click', () => applyPack(btn)));
  if (packs[0]) state.variantId = packs[0].dataset.pack;

  const ripeness = $$('#ripeness-options .ripeness-btn');
  ripeness.forEach((btn) => btn.addEventListener('click', () => select(ripeness, btn)));

  $('#qty-minus').addEventListener('click', () => { state.qty = Math.max(1, state.qty - 1); $('#qty-val').textContent = state.qty; renderPrice(); });
  $('#qty-plus').addEventListener('click', () => { state.qty = Math.min(99, state.qty + 1); $('#qty-val').textContent = state.qty; renderPrice(); });

  // ---- gallery ----
  const mainImg = $('#main-product-img');
  const thumbs = $$('#thumb-strip .thumb-card');
  thumbs.forEach((card) => card.addEventListener('click', () => {
    thumbs.forEach((c) => {
      c.classList.remove('ring-2', 'ring-accent-hyper-emerald', 'shadow-md');
      const l = c.querySelector('.thumb-label'); if (l) { l.classList.remove('text-accent-hyper-emerald'); l.classList.add('text-text-muted'); }
    });
    card.classList.add('ring-2', 'ring-accent-hyper-emerald', 'shadow-md');
    const label = card.querySelector('.thumb-label');
    if (label) { label.classList.add('text-accent-hyper-emerald'); label.classList.remove('text-text-muted'); }
    if (mainImg && card.dataset.src) { mainImg.src = card.dataset.src; mainImg.alt = card.dataset.alt || ''; }
  }));

  const views = $$('#view-mode-buttons .view-btn');
  views.forEach((btn) => btn.addEventListener('click', () => {
    views.forEach((b) => { b.classList.remove('active', 'bg-surface-elevated', 'text-accent-hyper-emerald'); b.classList.add('bg-transparent', 'text-text-muted'); });
    btn.classList.add('active', 'bg-surface-elevated', 'text-accent-hyper-emerald'); btn.classList.remove('bg-transparent', 'text-text-muted');
    if (mainImg) { mainImg.classList.add('opacity-75'); setTimeout(() => mainImg.classList.remove('opacity-75'), 200); }
  }));

  const tabs = $$('#audit-tabs .telemetry-tab');
  tabs.forEach((tab) => tab.addEventListener('click', () => {
    tabs.forEach((t) => {
      const on = t === tab;
      t.classList.toggle('bg-surface-elevated', on); t.classList.toggle('text-accent-hyper-emerald', on);
      t.classList.toggle('text-text-muted', !on);
      const panel = document.getElementById('content-' + t.dataset.tab);
      if (panel) panel.classList.toggle('hidden', !on);
    });
  }));

  // ---- toast ----
  const toast = $('#cart-toast');
  let toastTimer;
  function showToast(title, subtitle, price) {
    if (!toast) return;
    $('#toast-title').textContent = title;
    $('#toast-subtitle').textContent = subtitle;
    $('#toast-price').textContent = price;
    toast.classList.remove('translate-y-24', 'opacity-0'); toast.classList.add('translate-y-0', 'opacity-100');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('translate-y-0', 'opacity-100'); toast.classList.add('translate-y-24', 'opacity-0');
    }, 3500);
  }

  // ---- add to cart (real, server side) ----
  $('#add-to-cart-btn').addEventListener('click', async () => {
    if (!window.FoodMartCart) return;
    const ok = await window.FoodMartCart.add(state.variantId, state.qty);
    if (ok) {
      const label = $('#size-label') ? $('#size-label').textContent : '';
      showToast('Added to cart', root.dataset.name + ' (' + label + ') x' + state.qty, money(state.price * state.qty));
    }
  });

  // Recommended cards: quick add (data-variant-id = variant)
  $$('.quick-add').forEach((btn) => btn.addEventListener('click', async () => {
    if (!window.FoodMartCart || !btn.dataset.variantId) return;
    const ok = await window.FoodMartCart.add(btn.dataset.variantId, 1);
    if (ok) showToast('Added to cart', btn.dataset.name, money(btn.dataset.price));
  }));

  // ---- wishlist ----
  const wishBtn = $('#wishlist-btn');
  function paintWish(ids) {
    const liked = ids.map(String).includes(String(root.dataset.productId));
    const icon = $('#fav-icon');
    icon.textContent = liked ? 'favorite' : 'favorite_border';
    icon.classList.toggle('text-accent-hyper-emerald', liked);
    icon.style.fontVariationSettings = liked ? "'FILL' 1" : "'FILL' 0";
    $('#fav-text').textContent = liked ? 'Saved to favorites' : 'Save to Liked';
  }
  wishBtn.addEventListener('click', () => { if (window.FoodMartWishlist) window.FoodMartWishlist.toggle(root.dataset.productId); });
  document.addEventListener('fm:wishlist', (e) => paintWish(e.detail.ids));
  if (fm && fm.wishlist) paintWish((fm.wishlist.items || []).map((i) => i.id));
})();
