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
- Cart and Checkout pages are **blocks** in this store (My Account is a shortcode). Keep Woo's block layout and restyle the surface only. Block field selectors are very specific, so those few overrides use `!important`.
- Grids that contain a horizontally scrolling child need `grid-template-columns: minmax(0, 1fr)`, otherwise the child stretches the page on phones. When testing with mobile emulation, compare `scrollWidth` with the viewport width: `window.innerWidth` grows with the page and hides this bug.
- Local testing: the store is in WooCommerce "Coming soon" mode, so anonymous HTTP requests never reach the theme. Render pages through a CLI harness that adds `woocommerce_coming_soon_exclude` (do not change the store option).

- Blog (`inc/blog.php`, `home.php`, `archive.php`, `single.php`, `template-parts/blog/`): the posts page is the "Blog" page (`/blog/`, Settings > Reading). Listing filters are `?q=` and `?sort=` (noindexed); categories are real archive URLs. `assets/js/blog.js` swaps `#blog-head` + `#blog-results` over AJAX using the same `?bg_partial=1` contract as the shop (`begentle_blog_partial()`), and every link/form still works without JS. TOC is built from h2/h3; a "FAQ" h2 followed by h3 questions becomes FAQPage schema. The manual excerpt shows as the "In short" box. Meta/OG/JSON-LD switch off when an SEO plugin is active.
- PIN code auto-fill (`inc/pincode.php`, `assets/js/pincode.js`, data in `assets/pincodes/`, rebuilt with `tools/build-pincodes.mjs`): India PIN -> City + State on the block checkout (via the `wc/store/cart` data store, never the DOM) and My Account address forms. PIN is moved above City for India via the country locale priority. City the customer typed is never overwritten.

## Not decided / not verified yet

- Visual QA on real phones is done manually by the owner.
- Cart/Checkout: classic shortcode vs blocks depends on how the store pages were created; styling covers classic markup and basic block buttons only.
