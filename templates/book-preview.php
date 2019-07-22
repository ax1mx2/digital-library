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

    .preview-box {
        width: 60%;
        margin: 0 auto;
        max-height: 80vh;
    }

    .pdfemb-viewer {
        max-height: 70vh;
    }
</style>
<div class="preview-box">
	<?php echo do_shortcode( sprintf( '[pdf-embedder width="max" url="%s"]', esc_attr( $preview_url ) ) ); ?>
</div>
