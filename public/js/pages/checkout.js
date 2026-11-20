// Checkout page only: hold timer, delivery speed, round-up, promo, live totals.
(function () {
  const root = document.getElementById('checkout-root');
  if (!root) return;

  const money = n => '$' + n.toFixed(2);
  const subtotal = parseFloat(root.dataset.subtotal);
  const voucherAmount = parseFloat(root.dataset.voucher);
  const tax = parseFloat(root.dataset.tax);
  const roundupAmount = parseFloat(root.dataset.roundup);

  const state = { voucher: true, roundup: false, shipping: 0 };

  const sidebarTotal = document.getElementById('sidebar-total');
  const ctaTotal = document.getElementById('cta-total-display');
  const roundupRow = document.getElementById('roundup-row');
  const voucherRow = document.getElementById('voucher-row');
  const promoPill = document.getElementById('promo-pill');

  function render() {
    const total = subtotal - (state.voucher ? voucherAmount : 0) + tax + state.shipping + (state.roundup ? roundupAmount : 0);
    if (sidebarTotal) sidebarTotal.textContent = money(total);
    if (ctaTotal) ctaTotal.textContent = money(total);
    if (roundupRow) { roundupRow.classList.toggle('hidden', !state.roundup); roundupRow.classList.toggle('flex', state.roundup); }
    if (voucherRow) { voucherRow.classList.toggle('hidden', !state.voucher); voucherRow.classList.toggle('flex', state.voucher); }
    if (promoPill) { promoPill.classList.toggle('hidden', !state.voucher); promoPill.classList.toggle('flex', state.voucher); }
  }

  // Cart-hold countdown
  let seconds = 14 * 60 + 59;
  const timerEl = document.getElementById('cart-timer');
  setInterval(() => {
    if (seconds <= 0) return;
    seconds--;
    if (timerEl) timerEl.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
  }, 1000);

  // Delivery speed selection (its price feeds the total)
  const options = document.querySelectorAll('.speed-option');
  const ON = ['bg-surface-slate', 'shadow-[0_0_25px_-5px_rgba(0,245,155,0.2)]'];
  const OFF = ['bg-surface-carbon', 'opacity-85'];
  function setIcon(opt, checked) {
    const icon = opt.querySelector('.material-symbols-outlined');
    if (!icon) return;
    icon.textContent = checked ? 'radio_button_checked' : 'radio_button_unchecked';
    icon.classList.toggle('text-primary-container', checked);
    icon.classList.toggle('text-text-muted', !checked);
  }
  options.forEach(opt => opt.addEventListener('click', () => {
    options.forEach(o => { o.classList.remove(...ON); o.classList.add(...OFF); setIcon(o, false); });
    opt.classList.remove(...OFF); opt.classList.add(...ON); setIcon(opt, true);
    state.shipping = parseFloat(opt.dataset.price || '0');
    render();
  }));

  // Round-up toggle
  const soil = document.getElementById('soil-roundup');
  if (soil) soil.addEventListener('change', e => { state.roundup = e.target.checked; render(); });

  // Promo: remove works; Apply re-applies the demo code only
  const promoRemove = document.getElementById('promo-remove');
  if (promoRemove) promoRemove.addEventListener('click', () => { state.voucher = false; render(); });
  const promoApply = document.getElementById('promo-apply');
  const promoInput = document.getElementById('promo-input');
  if (promoApply && promoInput) promoApply.addEventListener('click', () => {
    if (promoInput.value.trim().toUpperCase() === 'FRESHORGANIC') { state.voucher = true; promoInput.value = ''; render(); }
  });
})();
