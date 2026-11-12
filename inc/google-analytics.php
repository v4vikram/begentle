<?php
/**
 * Google Tag Manager + Google Analytics 4
 * GTM Container  : GTM-MV9XFFMB
 * GA4 Measurement: G-JTXJM01YNJ
 */
defined( 'ABSPATH' ) || exit;

define( 'BEGENTLE_GTM_ID',  'GTM-MV9XFFMB' );
define( 'BEGENTLE_GA4_ID',  'G-JTXJM01YNJ' );

// 1. GTM + GA4 SCRIPT — in <head>
add_action( 'wp_head', 'begentle_gtm_head', 1 );
function begentle_gtm_head(): void { ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( BEGENTLE_GTM_ID ); ?>');</script>
<!-- End Google Tag Manager -->
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( BEGENTLE_GA4_ID ); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo esc_js( BEGENTLE_GA4_ID ); ?>');
</script>
<!-- End Google Analytics 4 -->
<?php }

// 2. GTM NOSCRIPT — right after <body>
add_action( 'wp_body_open', 'begentle_gtm_body', 1 );
function begentle_gtm_body(): void {
    echo '<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( BEGENTLE_GTM_ID ) . '"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->';
}
