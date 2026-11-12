<?php
/**
 * Microsoft Clarity — Heatmaps & Session Recordings
 * Project ID: zcgqsuso2s
 */
defined( 'ABSPATH' ) || exit;

define( 'BEGENTLE_CLARITY_ID', 'zcgqsuso2s' );

add_action( 'wp_head', 'begentle_clarity_script', 5 );
function begentle_clarity_script(): void { ?>
<!-- Microsoft Clarity -->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "<?php echo esc_js( BEGENTLE_CLARITY_ID ); ?>");
</script>
<!-- End Microsoft Clarity -->
<?php }
