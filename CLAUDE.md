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

## Not decided / not verified yet

- Visual QA on real phones is done manually by the owner.
- Cart/Checkout: classic shortcode vs blocks depends on how the store pages were created; styling covers classic markup and basic block buttons only.
