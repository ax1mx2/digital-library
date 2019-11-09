<?php

defined( 'ABSPATH' ) || exit();

/** \WC_Product */
global $preview_url;
?>
<style>
    html, body, #Wrapper {
        height: 100%;
    }

    #Subheader {
        display: none;
    }

    #Footer {
        display: none;
    }

    .pdfemb-viewer {
        overflow: auto;
    }
</style>
<div style="width: 100%; margin: 0 auto;">
	<?php echo do_shortcode( sprintf( '[pdf-embedder width="max" height="400" zoom="50" url="%s"]', esc_attr( $preview_url ) ) ); ?>
</div>
<script>
  (function($) {
    'use strict';

    function fillRemainingSpace() {
      setTimeout(function() {
        var pdfViewer = $('.pdfemb-viewer, .pdfemb-pagescontainer');
        var height = $(window).height() - $('#Header').height() - ($('#wpadminbar').height() || 0);
        pdfViewer.css({height: height + 'px'});
      }, 2000);
    }

    $(fillRemainingSpace);
    $(function() {
      $(window).resize(fillRemainingSpace);
    });
  })(window.jQuery);
</script>