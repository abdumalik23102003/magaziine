// Product-card controls: quantity stepper + "Add" button. Adding goes into the cart drawer (cart-drawer.js).
(function initQuantityControls() {
  document.querySelectorAll('.product-qty-controller').forEach(ctrl => {
    const minusBtn = ctrl.querySelector('.qty-btn-minus');
    const plusBtn = ctrl.querySelector('.qty-btn-plus');
    const valDisplay = ctrl.querySelector('.qty-value');

    minusBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      let currentVal = parseInt(valDisplay.textContent, 10) || 0;
      if (currentVal > 1) valDisplay.textContent = currentVal - 1;
    });

    plusBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      valDisplay.textContent = (parseInt(valDisplay.textContent, 10) || 0) + 1;
    });
  });

  document.querySelectorAll('.add-to-cart-action').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const parentCard = this.closest('[data-product-id]');
      const valDisplay = parentCard ? parentCard.querySelector('.qty-value') : null;
      const addedQty = valDisplay ? (parseInt(valDisplay.textContent, 10) || 1) : 1;

      if (parentCard && window.FoodMartCart) {
        window.FoodMartCart.add({
          id: parentCard.dataset.productId,
          name: parentCard.dataset.name,
          price: parentCard.dataset.price,
          emoji: parentCard.dataset.emoji,
          img: parentCard.dataset.img,
          glow: parentCard.dataset.glow,
          note: parentCard.dataset.category
        }, addedQty);
      }

      // Button feedback
      const originalHTML = this.innerHTML;
      this.classList.add('bg-white', 'text-black');
      this.innerHTML = '<i class="bi bi-check-lg font-black text-xs"></i> <span>Added</span>';
      setTimeout(() => {
        this.classList.remove('bg-white', 'text-black');
        this.innerHTML = originalHTML;
      }, 750);
    });
  });
})();
