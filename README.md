# Begentle

Minimal, mobile-first WooCommerce theme for Begentle (men's fashion, Gen Z). Starting with men's jeans.

## Setup

1. Clone/copy into `wp-content/themes/begentle`, activate it in WP admin (WooCommerce must be active).
2. `npm install`
3. `npm run dev` (watch) or `npm run build` (minified)

`assets/css/main.css` is a build output but **is committed**, so the server needs no Node. Rebuild and commit it whenever you change `src/css/main.css` or add new Tailwind classes in PHP/JS.

## Branches

- `main`: production
- `dev`: day-to-day work, merge into `main` when ready

## Structure

```
functions.php        thin bootstrap
inc/                 helpers, setup, enqueue, customizer, woo (card/product hooks),
                     filters (shop filters), account (My Account extras)
template-parts/      drawers (menu/search/cart), filter-drawer, catalog-header, sticky add-to-cart bar
front-page.php       home: hero, highlights, categories, new drops, promo, sale, best sellers
woocommerce.php      one wrapper for shop / category / product pages
page.php             pages incl. cart, checkout, my account
src/css/main.css     Tailwind v4 source + all Woo styling
assets/js/main.js    jQuery: drawers, size swatches, AJAX add to cart, sticky bar
```

## Customising

Appearance, Customize, Begentle: announcement bar, hero, highlights strip (3 short lines) and promo banner.
Optional sections (highlights, promo, sale, best sellers) stay hidden until they have content.
Category tiles/banners use the category image (Products, Categories, thumbnail).
Menus: assign "Primary" and "Footer" under Appearance, Menus (until then, fallbacks list Shop + categories).
