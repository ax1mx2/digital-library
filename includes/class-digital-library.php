<?php

namespace DL;

defined( 'ABSPATH' ) || exit();

require_once __DIR__ . '/class-book-search-controller.php';

/**
 * Main of the Digital Library Plugin.
 */
final class Digital_Library {

	public const VERSION = '0.0.1';
	public const REST_API_NAMESPACE = 'dl/v1';
	// Field name constants
	public const BOOK_SUBTITLE = 'dl_book_subtitle';
	public const BOOK_AUTHORS = 'dl_book_authors';
	public const BOOK_ISBN = 'dl_book_isbn';
	public const BOOK_PREVIEW = 'dl_book_preview_media_id';

	/**
	 * @var Digital_Library The main instance of the plugin class.
	 */
	private static $instance;

	/**
	 * @var string Specifies the plugin's main directory.
	 */
	private static $dir;

	/**
	 * @var string Specifies the URL to the main plugin's directory.
	 */
	private static $url;

	/**
	 * Digital_Library constructor.
	 */
	protected function __construct() {
		// Initializes class.
		self::$dir = plugin_dir_path( DL_PLUGIN_FILE );
		self::$url = plugin_dir_url( DL_PLUGIN_FILE );

		// Initialize hooks.
		register_activation_hook( DL_PLUGIN_FILE, array( $this, 'activate' ) );

		// Add custom scripts when editing a book.
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );
			add_filter( 'post_mime_types', array( $this, 'add_pdf_mime_type' ) );
		}
		// Add WooCommerce fields.
		add_action(
			'woocommerce_product_options_general_product_data',
			array( $this, 'add_book_fields' )
		);
		// Add WooCommerce save hooks.
		add_action(
			'woocommerce_process_product_meta',
			array( $this, 'save_book_fields' )
		);
		add_action( 'rest_api_init', array( $this, 'register_rest_controllers' ) );
	}

	/**
	 * @param string|null $sub The sub-path to be appended.
	 *
	 * @return string The main directory of the plugin.
	 */
	public static function dir( ?string $sub = null ): string {
		if ( empty( $sub ) ) {
			return self::$dir;
		}
		$sub = '/' . ltrim( $sub, '/' );

		return self::$dir . $sub;
	}

	/**
	 * @param string|null $sub The sub-path to be appended.
	 *
	 * @return string The URL for the main plugin directory.
	 */
	public static function url( ?string $sub = null ): string {
		if ( empty( $sub ) ) {
			return self::$url;
		}
		$sub = '/' . ltrim( $sub, '/' );

		return self::$url . $sub;
	}

	/**
	 * @return Digital_Library The main instance of the plugin.
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Method performs additional checks when activating the plugin.
	 */
	public function activate() {
		$plugin = plugin_basename( DL_PLUGIN_FILE );
		// Deactivate plugin if dependent plugins have not been activated beforehand.
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( $plugin );
		}
	}

	/**
	 * Method handles adding the admin scripts into the admin panel.
	 *
	 * @param string|null $hook The hook(page) name.
	 */
	public function add_admin_scripts( ?string $hook ) {
		if ( 'post.php' === $hook && 'product' === get_post_type() ) {
			wp_enqueue_media();
			wp_register_script( 'digital-library-admin', self::url( 'assets/admin/js/admin.js' ),
				array( 'jquery' ), self::VERSION );
			wp_localize_script( 'digital-library-admin', 'dl_translations',
				array(
					'set_to_post_id'        => '',
					'media_frame_title'     => __( 'Please select a book preview', 'digital-library' ),
					'select_preview_button' => __( 'Use this book preview', 'digital-library' ),
				)
			);
			wp_enqueue_script( 'digital-library-admin' );
		}
	}

	/**
	 * Method handles adding a custom mime type category to the media library for PDF files.
	 *
	 * @param array $post_mime_types The mime type array before the transformation.
	 *
	 * @return array The mime type after the transformation.
	 */
	public function add_pdf_mime_type( array $post_mime_types ): array {
		$post_mime_types['application/pdf'] = array(
			__( 'PDFs', 'digital-library' ),
			__( 'Manage PDFs', 'digital-library' ),
			_n_noop(
				'PDF <span class="count">(%s)</span>',
				'PDFs <span class="count">(%s)</span>'
			)
		);

		return $post_mime_types;
	}

	/**
	 * Method adds WooCommerce product fields.
	 */
	public function add_book_fields() {
		echo '<div class="options_group dl-woocommerce-book-field-group">';
		echo '<div><h2>' . esc_attr( __( 'Book Details', 'digital-library' ) ) . '</h2></div>';

		// Display authors.
		woocommerce_wp_textarea_input(
			array(
				'id'          => self::BOOK_AUTHORS,
				'label'       => __( 'Book Authors', 'digital-library' ),
				'placeholder' => __( "Peter Petrov\nDragan Draganov\n...", 'digital-library' ),
				'desc_tip'    => true,
				'description' => esc_html__( 'Enter each author name on a new line.', 'digital-library' ),
				'rows'        => 5
			)
		);

		// Display subtitle.
		woocommerce_wp_text_input(
			array(
				'id'    => self::BOOK_SUBTITLE,
				'type'  => 'text',
				'label' => __( 'Book Subtitle', 'digital-library' ),
			)
		);

		// Display ISBN.
		woocommerce_wp_text_input(
			array(
				'id'    => self::BOOK_ISBN,
				'type'  => 'text',
				'label' => __( 'Book ISBN', 'digital-library' ),
			)
		);

		// Display PDF Preview.
		?>
        <p class="form-field <?php echo esc_attr( self::BOOK_PREVIEW ) ?>">
            <label for="book_preview_id"><?php esc_attr_e( 'Book preview', 'digital-library' ) ?></label>
            <span class="book-preview-indicator"></span>
            <input id="book_preview_select_button" type="button" class="button"
                   value="<?php esc_attr_e( 'Upload a book preview', 'digital-library' ) ?>">
            <input type="hidden" id="book_preview_id" name="<?php echo esc_attr( self::BOOK_PREVIEW ) ?>">
        </p>
		<?php

		echo '</div>';
	}

	/**
	 * Method contains custom logic for saving a additional book fields.
	 *
	 * @param $post_id int The ID of book.
	 */
	public function save_book_fields( int $post_id ) {
		$authors  = &$_POST[ self::BOOK_AUTHORS ];
		$subtitle = &$_POST[ self::BOOK_SUBTITLE ];
		$isbn     = &$_POST[ self::BOOK_ISBN ];

		// Update book authors.
		if ( ! empty( $authors ) ) {
			update_post_meta( $post_id, self::BOOK_AUTHORS, wp_kses( $authors, array() ) );
		}

		if ( ! empty( $subtitle ) ) {
			update_post_meta( $post_id, self::BOOK_SUBTITLE, wp_kses( $subtitle, array() ) );
		}

		// Update book isbn.
		if ( ! empty( $isbn ) ) {
			update_post_meta( $post_id, self::BOOK_ISBN, wp_kses( $isbn, array() ) );
		}

	}

	/**
	 * Method registers REST Controllers.
	 */
	public function register_rest_controllers() {
		$book_search_controller = new Book_Search_Controller( self::REST_API_NAMESPACE );
		$book_search_controller->register_routes();
	}

}