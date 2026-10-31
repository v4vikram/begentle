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
inc/                 helpers, setup, enqueue, customizer, woo (all Woo hooks)
template-parts/      drawers (menu/search/cart), sticky add-to-cart bar
front-page.php       home: hero, categories, new drops
woocommerce.php      one wrapper for shop / category / product pages
page.php             pages incl. cart, checkout, my account
src/css/main.css     Tailwind v4 source + all Woo styling
assets/js/main.js    jQuery: drawers, size swatches, AJAX add to cart, sticky bar
```

## Customising

Appearance, Customize, "Begentle: banner & home": announcement bar, hero title/text/button/image.
Menus: assign "Primary" and "Footer" under Appearance, Menus (until then, fallbacks list Shop + categories).
