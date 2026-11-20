// Shopping-cart drawer. Exposes window.FoodMartCart = { add(product, qty), open(), close() }
(function () {
  const cartDrawer = document.getElementById('cart-drawer');
  if (!cartDrawer) return;

  const cartBackdrop = document.getElementById('cart-backdrop');
  const cartToggleBtn = document.getElementById('cart-toggle-btn');
  const cartCloseBtn = document.getElementById('cart-close-btn');
  const cartContinueBtn = document.getElementById('cart-continue-btn');
  const cartClearAllBtn = document.getElementById('cart-clear-all-btn');
  const cartShopNowBtn = document.getElementById('cart-shop-now-btn');
  const navCartBadge = document.getElementById('nav-cart-count');
  const cartDrawerCount = document.getElementById('cart-drawer-count');
  const itemsContainer = document.getElementById('cart-items-container');
  const emptyState = document.getElementById('cart-empty-state');
  const cartFooter = document.getElementById('cart-footer');
  const subtotalEl = document.getElementById('cart-subtotal');
  const taxEl = document.getElementById('cart-tax');
  const totalValueEl = document.getElementById('cart-total-value');
  const checkoutBtn = document.getElementById('cart-checkout-btn');
  const checkoutLabel = document.getElementById('cart-checkout-label');
  const progressBar = document.getElementById('cart-progress-bar');
  const deliveryText = document.getElementById('cart-delivery-text');
  const template = document.getElementById('cart-item-template');

  const FREE_DELIVERY_FROM = 35;

  function calculateCart(pulseBadge) {
    const items = document.querySelectorAll('.cart-item');
    let subtotal = 0;
    let totalItemsCount = 0;

    items.forEach(item => {
      const price = parseFloat(item.getAttribute('data-price') || '0');
      const qtyEl = item.querySelector('.cart-item-qty');
      const totalEl = item.querySelector('.cart-item-total');
      const qty = parseInt(qtyEl ? qtyEl.textContent : '1', 10);
      const itemSubtotal = price * qty;

      if (totalEl) totalEl.textContent = `$${itemSubtotal.toFixed(2)}`;
      subtotal += itemSubtotal;
      totalItemsCount += qty;
    });

    const tax = subtotal > 0 ? 1.25 : 0.00;
    const finalTotal = subtotal + tax;

    // Header badge (it has an inline display style, so toggle that instead of the 'hidden' class)
    if (navCartBadge) {
      navCartBadge.textContent = totalItemsCount;
      navCartBadge.style.display = totalItemsCount === 0 ? 'none' : 'flex';
      if (pulseBadge) {
        navCartBadge.classList.add('scale-125');
        setTimeout(() => navCartBadge.classList.remove('scale-125'), 180);
      }
    }

    if (cartDrawerCount) {
      cartDrawerCount.textContent = `${totalItemsCount} ${totalItemsCount === 1 ? 'item' : 'items'}`;
    }

    if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    if (taxEl) taxEl.textContent = `$${tax.toFixed(2)}`;
    if (totalValueEl) totalValueEl.textContent = `$${finalTotal.toFixed(2)}`;
    if (checkoutLabel) checkoutLabel.textContent = `Proceed to Checkout ($${finalTotal.toFixed(2)})`;

    // Free-delivery progress ($35)
    if (progressBar) {
      progressBar.style.width = `${Math.min(100, Math.round((subtotal / FREE_DELIVERY_FROM) * 100))}%`;
    }
    if (deliveryText) {
      deliveryText.textContent = subtotal >= FREE_DELIVERY_FROM
        ? 'Free delivery unlocked!'
        : `Add $${(FREE_DELIVERY_FROM - subtotal).toFixed(2)} more for free delivery`;
    }

    // Empty state
    const isEmpty = items.length === 0;
    if (emptyState) {
      emptyState.classList.toggle('hidden', !isEmpty);
      emptyState.classList.toggle('flex', isEmpty);
    }
    if (cartFooter) cartFooter.classList.toggle('opacity-40', isEmpty), cartFooter.classList.toggle('pointer-events-none', isEmpty);
    if (cartClearAllBtn) cartClearAllBtn.classList.toggle('opacity-40', isEmpty), cartClearAllBtn.classList.toggle('pointer-events-none', isEmpty);
  }

  function openCart() {
    if (window.FoodMartWishlist) window.FoodMartWishlist.close(); // only one drawer at a time
    cartDrawer.classList.remove('translate-x-full');
    cartDrawer.classList.add('translate-x-0');
    cartBackdrop.classList.remove('opacity-0', 'pointer-events-none');
    cartBackdrop.classList.add('opacity-100', 'pointer-events-auto');
    document.body.style.overflow = 'hidden';
  }

  function closeCart() {
    cartDrawer.classList.remove('translate-x-0');
    cartDrawer.classList.add('translate-x-full');
    cartBackdrop.classList.remove('opacity-100', 'pointer-events-auto');
    cartBackdrop.classList.add('opacity-0', 'pointer-events-none');
    document.body.style.overflow = '';
  }

  const escapeHtml = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // Add a product (from a product card). Same id => increase quantity, otherwise append a new row.
  function addToCart(product, qty) {
    qty = Math.max(1, parseInt(qty, 10) || 1);
    const existing = Array.from(itemsContainer.querySelectorAll('.cart-item'))
      .find(row => row.getAttribute('data-product-id') === String(product.id));

    if (existing) {
      const qtySpan = existing.querySelector('.cart-item-qty');
      qtySpan.textContent = (parseInt(qtySpan.textContent, 10) || 0) + qty;
    } else if (template) {
      const values = {
        '__ID__': product.id, '__EMOJI__': product.emoji || '🛒', '__IMG__': product.img || '', '__GLOW__': product.glow || '0,245,155',
        '__NAME__': product.name || 'Product', '__PRICE__': (parseFloat(product.price) || 0).toFixed(2), '__NOTE__': product.note || ''
      };
      let html = template.innerHTML;
      Object.keys(values).forEach(k => { html = html.split(k).join(escapeHtml(values[k])); });
      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      const row = wrapper.firstElementChild;
      if (!product.img) {                       // no photo: drop the <img>, show the emoji instead
        const thumb = row.querySelector('.item-thumb'); if (thumb) thumb.remove();
        const emo = row.querySelector('.item-emoji'); if (emo) emo.classList.remove('hidden');
      }
      row.querySelector('.cart-item-qty').textContent = qty;
      itemsContainer.insertBefore(row, emptyState);
    }
    calculateCart(true);
  }

  function removeRow(row, duration, transform) {
    row.style.transition = `all ${duration}s ease`;
    row.style.opacity = '0';
    row.style.transform = transform;
    setTimeout(() => { row.remove(); calculateCart(); }, duration * 1000 - 30);
  }

  // Toggle / close triggers
  if (cartToggleBtn) {
    cartToggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      cartDrawer.classList.contains('translate-x-0') ? closeCart() : openCart();
    });
  }
  [cartCloseBtn, cartContinueBtn, cartBackdrop, cartShopNowBtn].forEach(el => el && el.addEventListener('click', closeCart));

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && cartDrawer.classList.contains('translate-x-0')) closeCart();
  });

  // Stepper + remove (event delegation, so rows added later work too)
  itemsContainer.addEventListener('click', (e) => {
    const stepBtn = e.target.closest('.cart-step-btn');
    const removeBtn = e.target.closest('.cart-remove-item');
    const row = e.target.closest('.cart-item');
    if (!row) return;

    if (stepBtn) {
      e.stopPropagation();
      const qtySpan = row.querySelector('.cart-item-qty');
      let qty = parseInt(qtySpan.textContent, 10);
      if (stepBtn.getAttribute('data-action') === 'increase') {
        qtySpan.textContent = qty + 1;
        calculateCart();
      } else if (qty > 1) {
        qtySpan.textContent = qty - 1;
        calculateCart();
      } else {
        removeRow(row, 0.25, 'translateX(20px)');
      }
    } else if (removeBtn) {
      e.stopPropagation();
      removeRow(row, 0.25, 'translateX(20px)');
    }
  });

  if (cartClearAllBtn) {
    cartClearAllBtn.addEventListener('click', () => {
      const items = document.querySelectorAll('.cart-item');
      items.forEach(row => {
        row.style.transition = 'all 0.2s ease';
        row.style.opacity = '0';
        row.style.transform = 'scale(0.95)';
      });
      setTimeout(() => { items.forEach(row => row.remove()); calculateCart(); }, 200);
    });
  }

  // Checkout button -> checkout page (url comes from the button's data-checkout-url)
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function () {
      const url = this.getAttribute('data-checkout-url');
      if (url) window.location.href = url;
    });
  }

  window.FoodMartCart = { add: addToCart, open: openCart, close: closeCart };
  calculateCart();
})();
