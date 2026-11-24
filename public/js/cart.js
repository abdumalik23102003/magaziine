// Product cards: quantity stepper, "Add" button (-> cart drawer) and heart (-> wishlist).
(function () {
  const fm = window.FoodMart;

  document.querySelectorAll('.product-qty-controller').forEach((ctrl) => {
    const value = ctrl.querySelector('.qty-value');
    ctrl.querySelector('.qty-btn-minus').addEventListener('click', (e) => {
      e.stopPropagation();
      const n = parseInt(value.textContent, 10) || 1;
      if (n > 1) value.textContent = n - 1;
    });
    ctrl.querySelector('.qty-btn-plus').addEventListener('click', (e) => {
      e.stopPropagation();
      const n = parseInt(value.textContent, 10) || 1;
      if (n < 99) value.textContent = n + 1;
    });
  });

  document.querySelectorAll('.add-to-cart-action').forEach((btn) => {
    btn.addEventListener('click', async function (e) {
      e.stopPropagation();
      const card = this.closest('[data-product-id]');
      if (!card || !window.FoodMartCart) return;
      const qtyEl = card.querySelector('.qty-value');
      const qty = qtyEl ? parseInt(qtyEl.textContent, 10) || 1 : 1;

      const original = this.innerHTML;
      const ok = await window.FoodMartCart.add(card.dataset.productId, qty);
      if (!ok) return;
      this.classList.add('bg-white', 'text-black');
      this.innerHTML = '<i class="bi bi-check-lg font-black text-xs"></i> <span>Added</span>';
      setTimeout(() => { this.classList.remove('bg-white', 'text-black'); this.innerHTML = original; }, 750);
    });
  });

  // Hearts
  document.querySelectorAll('.wishlist-toggle').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (window.FoodMartWishlist) window.FoodMartWishlist.toggle(btn.dataset.wishlistId);
    });
  });

  function paintHearts(ids) {
    const set = new Set(ids.map(String));
    document.querySelectorAll('.wishlist-toggle').forEach((btn) => {
      const liked = set.has(String(btn.dataset.wishlistId));
      const icon = btn.querySelector('i');
      if (icon) icon.className = 'text-xs bi ' + (liked ? 'bi-heart-fill text-red-400' : 'bi-heart');
      btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
    });
  }
  document.addEventListener('fm:wishlist', (e) => paintHearts(e.detail.ids));
  if (fm && fm.wishlist) paintHearts((fm.wishlist.items || []).map((i) => i.id));
})();
