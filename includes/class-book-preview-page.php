<?php


namespace DL;

defined( 'ABSPATH' ) || exit();

/**
 * Class Book_Preview_Page contains logic for virtual pages for each book preview.
 * @package DL
 */
class Book_Preview_Page {

	public const EXCERPT_ENDPOINT = 'book-excerpt';
	public const PREVIEW_ENDPOINT = 'book-preview';

	/**
	 * @var Book_Preview_Page The instance of the book preview page.
	 */
	private static $instance;

	/**
	 * Book_Preview_Page constructor.
	 */
	protected function __construct() {
		// Set up routing.
		add_action( 'init', array( $this, 'add_endpoint' ) );
		if ( is_admin() ) {
			flush_rewrite_rules();
		}
		add_action( 'template_redirect', array( $this, 'handle_redirect' ) );


		// Add buttons on product page.
		add_filter( 'woocommerce_short_description', array( $this, 'add_product_buttons' ) );
	}

	/**
	 * @return Book_Preview_Page The instance of the book preview page.
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Method initializes the default class instance.
	 */
	public static function init(): void {
		self::instance();
	}

	/**
	 * Method carries out additional logic when the plugin is activated.
	 */
	public function activate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Method carries out additional logic when the plugin is deactivated.
	 */
	public function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Method handles adding the rewrite endpoint.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( self::EXCERPT_ENDPOINT, EP_PERMALINK );
		add_rewrite_endpoint( self::PREVIEW_ENDPOINT, EP_PERMALINK );
		flush_rewrite_rules();
	}

	/**
	 * Method handles redirect.
	 */
	public function handle_redirect() {
		global $wp_query, $post;
		$is_excerpt = isset( $wp_query->query_vars[ self::EXCERPT_ENDPOINT ] );
		$is_preview = isset( $wp_query->query_vars[ self::PREVIEW_ENDPOINT ] );
		if ( ! is_singular( 'product' ) ||
		     ( ! $is_excerpt && ! $is_preview )
		) {
			return;
		}

		if ( $is_excerpt ) {
			$excerpt_id = intval( get_post_meta( $post->ID, Digital_Library::BOOK_EXCERPT, true ) );
			if ( 'attachment' !== get_post_type( $excerpt_id )
			     || 'application/pdf' !== strtolower( get_post_mime_type( $excerpt_id ) ) ) {
				wp_redirect( get_permalink( $post->ID ), 302, 'Digital Library' );
			}
			$file_id = $excerpt_id;
		} elseif ( $is_preview ) {
			$preview_id            = intval( get_post_meta( $post->ID, Digital_Library::BOOK_PREVIEW, true ) );
			$date_public           = get_post_meta( $post->ID, Digital_Library::BOOK_DATE_PUBLIC, true );
			$date_public_timestamp = strtotime( $date_public );
			if ( ( ! empty( $date_public ) && time() < $date_public_timestamp )
			     || 'attachment' !== get_post_type( $preview_id )
			     || 'application/pdf' !== strtolower( get_post_mime_type( $preview_id ) )
			) {
				wp_redirect( get_permalink( $post->ID ), 302, 'Digital Library' );
			}
			$file_id = $preview_id;
		}

		/** @noinspection PhpIncludeInspection */
		$GLOBALS['preview_url'] = wp_get_attachment_url( $file_id );
		ob_start();
		get_header();
		include Digital_Library::dir( 'templates/book-preview.php' );
		get_footer();
		die();
	}

	/**
	 * Method handles adding the book buttons,
	 * if the excerpt or preview is available.
	 *
	 * @param string $excerpt
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function add_product_buttons( string $excerpt ) {
		if ( ! is_product() ) {
			return $excerpt;
		}
		global $product;

		ob_start();
		$excerpt_id = intval( get_post_meta( $product->get_id(), Digital_Library::BOOK_EXCERPT, true ) );
		if ( 'attachment' === get_post_type( $excerpt_id )
		     && 'application/pdf' === strtolower( get_post_mime_type( $excerpt_id ) )
		) {
			$excerpt_button = __( 'Open the book excerpt', 'digital-library' );
			$url            = rtrim( get_permalink( $product->get_id() ), '/' ) . '/' . self::EXCERPT_ENDPOINT;
			if ( shortcode_exists( 'fancy_link' ) ) {
				echo do_shortcode(
					sprintf( '[fancy_link link="%1$s" title="%2$s" style="5"]',
						esc_attr( $url ), esc_html( $excerpt_button ) ) );
			} else {
				printf( '<a role="button" class="button" href="%1$s">%2$s</a>',
					esc_attr( $url ), esc_html( $excerpt_button ) );
			}
		}

		$date_public = get_post_meta( $product->get_id(), Digital_Library::BOOK_DATE_PUBLIC, true );
		$date_public = new \DateTime( $date_public );
		$now         = new \DateTime();
		if ( $date_public <= $now ) {
			$preview_id = intval( get_post_meta( $product->get_id(), Digital_Library::BOOK_PREVIEW, true ) );
			if ( 'attachment' === get_post_type( $preview_id )
			     && 'application/pdf' === strtolower( get_post_mime_type( $preview_id ) )
			) {
				$preview_button = __( 'Open the book preview', 'digital-library' );
				$url            = rtrim( get_permalink( $product->get_id() ), '/' ) . '/' . self::PREVIEW_ENDPOINT;
				if ( shortcode_exists( 'fancy_link' ) ) {
					echo do_shortcode(
						sprintf( '[fancy_link link="%1$s" title="%2$s" style="5"]',
							esc_attr( $url ), esc_html( $preview_button ) ) );
				} else {
					printf( '<a role="button" class="button" href="%1$s">%2$s</a>',
						esc_attr( $url ), esc_html( $preview_button ) );
				}
			}
		}

		return $excerpt . ob_get_clean();
	}

}