(function() {
  const drawer = document.getElementById('wishlist-drawer');
  const backdrop = document.getElementById('wishlist-backdrop');
  const toggleBtn = document.getElementById('wishlist-toggle-btn');
  const closeBtn = document.getElementById('close-wishlist-btn');
  const continueBtn = document.getElementById('continue-shopping-btn');
  const exploreBtn = document.getElementById('explore-items-btn');
  const clearBtn = document.getElementById('clear-wishlist-btn');
  const badgeNav = document.getElementById('nav-wishlist-count');
  const badgeDrawer = document.getElementById('drawer-counter-badge');
  const emptyState = document.getElementById('wishlist-empty-state');
  const footer = document.getElementById('wishlist-footer');
  const totalValueEl = document.getElementById('wishlist-total-value');
  const moveAllBtn = document.getElementById('move-all-cart-btn');

  function updateCounts() {
    const items = document.querySelectorAll('.wishlist-item');
    const count = items.length;
    
    // Update badge numbers
    if (badgeNav) {
      badgeNav.textContent = count;
      // the badge has an inline display style, so toggle that (the 'hidden' class would lose)
      badgeNav.style.display = count === 0 ? 'none' : 'flex';
    }

    if (badgeDrawer) {
      badgeDrawer.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
    }

    // Recalculate total value
    let total = 0;
    items.forEach(item => {
      total += parseFloat(item.dataset.price) || 0;
    });
    if (totalValueEl) {
      totalValueEl.textContent = `$${total.toFixed(2)}`;
    }

    if (count === 0) {
      if (emptyState) emptyState.classList.remove('hidden');
      if (emptyState) emptyState.classList.add('flex');
      if (clearBtn) clearBtn.classList.add('opacity-40', 'pointer-events-none');
      if (footer) footer.classList.add('opacity-40', 'pointer-events-none');
    } else {
      if (emptyState) emptyState.classList.add('hidden');
      if (emptyState) emptyState.classList.remove('flex');
      if (clearBtn) clearBtn.classList.remove('opacity-40', 'pointer-events-none');
      if (footer) footer.classList.remove('opacity-40', 'pointer-events-none');
    }
  }

  function openWishlist() {
    if (window.FoodMartCart) window.FoodMartCart.close(); // only one drawer at a time
    drawer.classList.remove('translate-x-full');
    drawer.classList.add('translate-x-0');
    backdrop.classList.remove('opacity-0', 'pointer-events-none');
    backdrop.classList.add('opacity-100', 'pointer-events-auto');
    document.body.style.overflow = 'hidden';
  }

  function closeWishlist() {
    drawer.classList.remove('translate-x-0');
    drawer.classList.add('translate-x-full');
    backdrop.classList.remove('opacity-100', 'pointer-events-auto');
    backdrop.classList.add('opacity-0', 'pointer-events-none');
    document.body.style.overflow = '';
  }

  // Toggle button in header
  if (toggleBtn) {
    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (drawer.classList.contains('translate-x-0')) {
        closeWishlist();
      } else {
        openWishlist();
      }
    });
  }

  // Close handlers
  if (closeBtn) closeBtn.addEventListener('click', closeWishlist);
  if (continueBtn) continueBtn.addEventListener('click', closeWishlist);
  if (exploreBtn) exploreBtn.addEventListener('click', closeWishlist);
  if (backdrop) backdrop.addEventListener('click', closeWishlist);

  // Esc key to close
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('translate-x-0')) {
      closeWishlist();
    }
  });

  // Individual item remove
  document.querySelectorAll('.remove-wishlist-item').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const row = this.closest('.wishlist-item');
      if (row) {
        row.style.transition = 'all 0.25s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(20px)';
        setTimeout(() => {
          row.remove();
          updateCounts();
        }, 220);
      }
    });
  });

  // Clear all button
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      const items = document.querySelectorAll('.wishlist-item');
      items.forEach(row => {
        row.style.transition = 'all 0.2s ease';
        row.style.opacity = '0';
        row.style.transform = 'scale(0.95)';
      });
      setTimeout(() => {
        items.forEach(row => row.remove());
        updateCounts();
      }, 200);
    });
  }

  // Move all to cart button feedback
  if (moveAllBtn) {
    moveAllBtn.addEventListener('click', function() {
      const origText = this.innerHTML;
      const n = document.querySelectorAll('.wishlist-item').length;
      this.innerHTML = `<i class="bi bi-check2 text-base"></i> Added All ${n} to Cart!`;
      this.classList.add('bg-white', 'text-black');
      setTimeout(() => {
        this.innerHTML = origText;
        this.classList.remove('bg-white', 'text-black');
      }, 1800);
    });
  }

  // Add individual to cart feedback
  document.querySelectorAll('.add-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const orig = this.innerHTML;
      this.innerHTML = '<i class="bi bi-check2"></i> Added';
      this.classList.add('bg-white');
      setTimeout(() => {
        this.innerHTML = orig;
        this.classList.remove('bg-white');
      }, 1400);
    });
  });

  window.FoodMartWishlist = { open: openWishlist, close: closeWishlist };

  // Initialize count state
  updateCounts();
})();
