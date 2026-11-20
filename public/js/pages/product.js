// Product page only: pack/ripeness selection, quantity, price, tabs, gallery, wishlist, toast.
(function () {
  const root = document.getElementById('product-root');
  if (!root) return;

  const $ = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$ = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));
  const money = n => '$' + Number(n).toFixed(2);

  const state = { qty: 1, price: parseFloat(root.dataset.price), wished: false };

  // ---- price / CTA ----
  function renderPrice() {
    const total = money(state.price * state.qty);
    $('#display-price').textContent = total;
    $('#cta-text').textContent = '+ Add to Cold-Chain Dispatch • ' + total;
  }

  // ---- selected-state classes ----
  const SEL = ['active', 'bg-surface-elevated', 'ring-1', 'ring-accent-hyper-emerald', 'shadow-sm'];
  function select(group, btn) {
    group.forEach(b => { b.classList.remove(...SEL); b.classList.add('bg-surface-slate'); });
    btn.classList.add(...SEL); btn.classList.remove('bg-surface-slate');
  }

  // Pack size
  const packs = $$('#pack-options .pack-btn');
  packs.forEach(btn => btn.addEventListener('click', () => {
    select(packs, btn);
    state.price = parseFloat(btn.dataset.price);
    $('#unit-price').textContent = money(btn.dataset.per) + ' / unit';
    $('#size-label').textContent = btn.dataset.label;
    renderPrice();
  }));

  // Ripeness
  const ripeness = $$('#ripeness-options .ripeness-btn');
  ripeness.forEach(btn => btn.addEventListener('click', () => select(ripeness, btn)));

  // Quantity
  $('#qty-minus').addEventListener('click', () => { state.qty = Math.max(1, state.qty - 1); $('#qty-val').textContent = state.qty; renderPrice(); });
  $('#qty-plus').addEventListener('click', () => { state.qty += 1; $('#qty-val').textContent = state.qty; renderPrice(); });

  // ---- gallery ----
  const mainImg = $('#main-product-img');
  const thumbs = $$('#thumb-strip .thumb-card');
  thumbs.forEach(card => card.addEventListener('click', () => {
    thumbs.forEach(c => {
      c.classList.remove('ring-2', 'ring-accent-hyper-emerald', 'shadow-md');
      const l = c.querySelector('.thumb-label'); if (l) { l.classList.remove('text-accent-hyper-emerald'); l.classList.add('text-text-muted'); }
    });
    card.classList.add('ring-2', 'ring-accent-hyper-emerald', 'shadow-md');
    const label = card.querySelector('.thumb-label');
    if (label) { label.classList.add('text-accent-hyper-emerald'); label.classList.remove('text-text-muted'); }
    if (mainImg && card.dataset.src) { mainImg.src = card.dataset.src; mainImg.alt = card.dataset.alt || ''; }
  }));

  // View-mode buttons (only restyle + brief dim, as in the original design)
  const views = $$('#view-mode-buttons .view-btn');
  views.forEach(btn => btn.addEventListener('click', () => {
    views.forEach(b => { b.classList.remove('active', 'bg-surface-elevated', 'text-accent-hyper-emerald'); b.classList.add('bg-transparent', 'text-text-muted'); });
    btn.classList.add('active', 'bg-surface-elevated', 'text-accent-hyper-emerald'); btn.classList.remove('bg-transparent', 'text-text-muted');
    if (mainImg) { mainImg.classList.add('opacity-75'); setTimeout(() => mainImg.classList.remove('opacity-75'), 200); }
  }));

  // ---- audit tabs ----
  const tabs = $$('#audit-tabs .telemetry-tab');
  tabs.forEach(tab => tab.addEventListener('click', () => {
    tabs.forEach(t => {
      const on = t === tab;
      t.classList.toggle('bg-surface-elevated', on); t.classList.toggle('text-accent-hyper-emerald', on);
      t.classList.toggle('text-text-muted', !on);
      const panel = document.getElementById('content-' + t.dataset.tab);
      if (panel) panel.classList.toggle('hidden', !on);
    });
  }));

  // ---- wishlist ----
  $('#wishlist-btn').addEventListener('click', () => {
    state.wished = !state.wished;
    const icon = $('#fav-icon');
    icon.textContent = state.wished ? 'favorite' : 'favorite_border';
    icon.classList.toggle('text-accent-hyper-emerald', state.wished);
    icon.style.fontVariationSettings = state.wished ? "'FILL' 1" : "'FILL' 0";
    $('#fav-text').textContent = state.wished ? 'Saved in Vault' : 'Save to Liked';
  });

  // ---- toast ----
  const toast = $('#cart-toast');
  let toastTimer;
  function showToast(title, subtitle, price) {
    $('#toast-title').textContent = title;
    $('#toast-subtitle').textContent = subtitle;
    $('#toast-price').textContent = price;
    toast.classList.remove('translate-y-24', 'opacity-0'); toast.classList.add('translate-y-0', 'opacity-100');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('translate-y-0', 'opacity-100'); toast.classList.add('translate-y-24', 'opacity-0');
    }, 3500);
  }

  $('#add-to-cart-btn').addEventListener('click', () => {
    showToast('Staged for Cold Dispatch', root.dataset.name + ' (' + state.qty + ' pack) • 3.4°C Vault', money(state.price * state.qty));
  });
  $$('.quick-add').forEach(btn => btn.addEventListener('click', () => {
    showToast('Added to Allocation', btn.dataset.name + ' • Rapid Cold Intake', money(btn.dataset.price));
  }));
})();
