<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woocommerce.com/document/template-structure/
 * @package    WooCommerce/Templates
 * @version    3.4.0
 */

use DL\Digital_Library;

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

$category_id = get_queried_object()->term_id;
$dl          = Digital_Library::instance();

$categories = $dl->get_product_categories( $category_id );

$category_view_app_data = array(
	'category'   => $category_id,
	'categories' => $categories,
	'upcoming'   => $dl->get_upcoming_books( $category_id )
);

$category_thumb_id = get_term_meta( $category_id, 'thumbnail_id', true );
?>
    <header class="woocommerce-products-header">
        <div class="category-descriptor">
	        <?php
	        if ( ! empty( $category_thumb_id ) ) :
		        echo wp_get_attachment_image(
			        $category_thumb_id, array( 100, 100 ),
			        false
		        );
	        endif;

	        /**
	         * Hook: woocommerce_archive_description.
	         *
	         * @hooked woocommerce_taxonomy_archive_description - 10
	         * @hooked woocommerce_product_archive_description - 10
	         */
	        do_action( 'woocommerce_archive_description' );
	        ?>
        </div>
    </header>

    <div class="book-category-view-app" style="padding: 10px;">
        <script type="text/props"><?php echo wp_json_encode( $category_view_app_data ) ?></script>
    </div>

<?php

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );


/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
