# FoodMart structure (copy folders into your Laravel project root)

resources/views/layouts/app.blade.php        <- shell: <head>, ambient glows, cursor canvas, header/footer, @yield('content'), @stack('styles'/'scripts')
resources/views/partials/*.blade.php         <- parts repeated on many pages (announcement bar, header, footer, later: sidebar)
resources/views/components/*.blade.php       <- reusable pieces with data (product-card, later: order-row ...)
resources/views/pages/*.blade.php            <- one file per page (home, cart, profile ...)
public/css/app.css                           <- styles used on EVERY page
public/css/pages/<page>.css                  <- styles used on ONE page
public/js/*.js                               <- scripts used on EVERY page (cursor, ripple, cart)
public/js/pages/<page>.js                    <- scripts used on ONE page
routes/web.php                               <- Route::view('/', 'pages.home')

Rules for the next pages:
1. Header/footer/nav identical -> already in layout, ignore them.
2. Page body -> pages/<name>.blade.php inside @section('content').
3. Sidebar shared by several pages -> partials/sidebar.blade.php, @include it in those pages.
4. <style> only that page needs -> public/css/pages/<name>.css + @push('styles').
5. <style> every page needs -> append to public/css/app.css.
6. <script> only that page needs -> public/js/pages/<name>.js + @push('scripts').
7. Tailwind colors/fonts that are new -> add to public/js/tailwind-config.js.
8. Same card/row repeated 2+ times -> components/<name>.blade.php.

Added with page 2 (liked sidebar):
partials/wishlist-drawer.blade.php + components/wishlist-item.blade.php + public/js/wishlist-drawer.js (global, included in layout)

Added with page 3 (cart sidebar):
partials/cart-drawer.blade.php + components/cart-item.blade.php + public/js/cart-drawer.js (global, included in layout)
public/js/cart.js now sends product-card "Add" clicks into the drawer via window.FoodMartCart.add(...)
Cart item ids use the product ids (prod-1 ...) so adding the same product again raises its quantity.

Added with page 4 (checkout) - its own shell, so its own layout:
layouts/checkout.blade.php (own header/footer, NO drawers, NO app.css, own Tailwind config)
partials/checkout-header, checkout-footer, checkout-stepper, checkout-express, checkout-delivery, checkout-payment, checkout-summary
components/checkout-order-item, speed-option, checkout-field
public/css/checkout-shell.css, public/js/tailwind-config-checkout.js, public/js/pages/checkout.js
routes: /checkout (name: checkout). Cart drawer "Proceed to Checkout" now links there.

Added with page 5 (profile) - another "neon" shell (checkout + profile share the Tailwind config and base css):
(later renamed: layouts/profile -> layouts/store, partials/profile-header|footer -> store-header|footer, shared by profile + product pages)
renamed: public/js/tailwind-config-checkout.js -> tailwind-config-neon.js, public/css/checkout-shell.css -> neon-shell.css
layouts/profile.blade.php, partials/profile-header, profile-footer, profile-hero, profile-tabs, profile-dispatch, profile-orders, profile-presets, profile-sidebar
components/profile-card, card-header, profile-stat, payload-item, profile-order
public/js/pages/profile.js, route /profile (name: profile)

Added with page 6 (product detail) - uses layouts/store (same header/footer as profile):
pages/product.blade.php; partials/product-gallery, product-purchase, product-audit, product-reviews, product-related, cart-toast
components/star-rating, pack-option, ripeness-option, lab-metric, timeline-item, info-card, review-card, recommend-card
public/js/pages/product.js, route /product (name: product)

Added with v7 (new home page using the real images from public/foodmart/images):
pages/home.blade.php now only holds demo data + @include of: partials/home-hero, home-trust, home-categories, home-products (used twice), home-banners, home-blog, home-app
components/section-heading, category-tile, promo-banner, blog-card (new); product-card, cart-item, wishlist-item now take img (file name in public/foodmart/images) and fall back to emoji
cart.js + cart-drawer.js pass the product image into the cart drawer
Images are NOT in this zip: they stay in your public/foodmart/images/ and are used via asset('foodmart/images/<file>')
