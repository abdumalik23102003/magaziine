// Checkout page: live totals when the delivery method changes (server recalculates on submit).
(function () {
  const form = document.getElementById('checkout-form');
  if (!form) return;

  const subtotal = parseFloat(form.dataset.subtotal);
  const tax = parseFloat(form.dataset.tax);
  const freeFrom = parseFloat(form.dataset.freeFrom);
  const money = (n) => '$' + n.toFixed(2);

  function render() {
    const picked = form.querySelector('input[name="delivery_method"]:checked');
    const fee = picked && subtotal < freeFrom ? parseFloat(picked.dataset.fee || '0') : 0;
    document.getElementById('sum-delivery').textContent = fee === 0 ? 'FREE' : money(fee);
    document.getElementById('sum-total').textContent = money(subtotal + tax + fee);
  }

  form.querySelectorAll('input[name="delivery_method"]').forEach((r) => r.addEventListener('change', render));

  form.addEventListener('submit', () => {
    const btn = document.getElementById('place-order-btn');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-60'); btn.querySelector('span').textContent = 'Placing order...'; }
  });
  render();
})();
