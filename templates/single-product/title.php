<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woocommerce.com/document/template-structure/
 * @package    WooCommerce/Templates
 * @version    1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

the_title( '<h1 class="product_title entry-title">', '</h1>' );
$book_authors = get_post_meta( get_the_ID(), \DL\Digital_Library::BOOK_AUTHORS, true );
if ( ! empty( trim( $book_authors ) ) ) {
	$book_authors = preg_split( '/\r\n|\r|\n/', esc_html( $book_authors ) );
	$book_authors = join( ', ', $book_authors );
	echo '<div class="book-authors">' . $book_authors . '</div>';
}
