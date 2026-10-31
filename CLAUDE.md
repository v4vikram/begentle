# Begentle theme: project notes

Custom WooCommerce theme, men's fashion (jeans first, more categories later), Gen Z audience, phones are the main traffic.

## Conventions

- Branches: `main` (prod) and `dev` (work). Only these two.
- Stack: PHP classic theme, Tailwind v4 (CLI), jQuery (WP bundled). No other dependencies without a reason.
- `assets/css/main.css` is built from `src/css/main.css` with `npm run build` and is committed.
- **No `woocommerce/` template overrides.** Customise through hooks in `inc/woo.php`. Add an override only when markup must change, and note why here.
- WooCommerce's own CSS is disabled (`woocommerce_enqueue_styles` returns `[]`), so every Woo element must be styled in `src/css/main.css`. Cart/Checkout *blocks* keep their block CSS.
- Mobile-first. Inputs stay at 16px (prevents iOS zoom). Tap targets at least 44px.
- Mini-cart drawer content is replaced by Woo cart fragments: style `.widget_shopping_cart_content` by selector, never by utility classes on that element.

- Shop filters (`inc/filters.php`) use WooCommerce's own query vars (`min_price`, `max_price`, `filter_<attr>=a,b`) plus `on_sale` / `in_stock`. No filter plugin or widget. Size/colour chips are joined into the comma format by `assets/js/main.js`.
- CSS cascade gotcha: our rules live in Tailwind's `@layer components`, and **unlayered CSS always beats layered CSS**. Anything that must override third-party CSS (select2, Woo Cart/Checkout blocks) goes in the unlayered section at the end of `src/css/main.css`.
- My Account (`inc/account.php`): dashboard tiles, recent orders, order progress steps, all via hooks.
- Cart and Checkout pages are **blocks** in this store (My Account is a shortcode). Block styling is intentionally light (buttons, radius, fonts).
- Local testing: the store is in WooCommerce "Coming soon" mode, so anonymous HTTP requests never reach the theme. Render pages through a CLI harness that adds `woocommerce_coming_soon_exclude` (do not change the store option).

## Not decided / not verified yet

- Visual QA on real phones is done manually by the owner.
- Cart/Checkout: classic shortcode vs blocks depends on how the store pages were created; styling covers classic markup and basic block buttons only.
