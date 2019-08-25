<?php

namespace DL;

defined( 'ABSPATH' ) || exit();

require_once __DIR__ . '/class-book-search-controller.php';
require_once __DIR__ . '/class-main-options-page.php';
require_once __DIR__ . '/class-book-preview-page.php';

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

		// Add plugin translations.
		add_action( 'plugins_loaded', array( $this, 'add_translations' ) );

		if ( is_admin() ) {
			// Add custom scripts when editing a book.
			add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ) );

			// Add custom options page.
			Main_Options_Page::init();
		} else {
			Book_Preview_Page::init();

			// Disable comments for all products, if necessary.
			if ( filter_var( get_option( Main_Options_Page::DISABLE_PRODUCT_COMMENTS ), FILTER_VALIDATE_BOOLEAN ) ) {
				add_filter( 'comments_open', array( $this, 'disable_comments_for_products' ), 1, 2 );
			}
		}

		// Customize WooCommerce.
		add_filter( 'woocommerce_locate_template',
			array( $this, 'change_product_category_template' ), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ), 10, 1 );

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
	 * @return Digital_Library The main instance of the plugin.
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
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
	 * Method performs additional checks when activating the plugin.
	 */
	public function activate() {
		$plugin = plugin_basename( DL_PLUGIN_FILE );
		// Deactivate plugin if dependent plugins have not been activated beforehand.
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( $plugin );
		}
		Book_Preview_Page::instance()->activate();
	}

	/**
	 * Method carries out addititonal logic when the plugin is deactivated.
	 */
	public function deactivate(): void {
		Book_Preview_Page::instance()->activate();
	}

	/**
	 * Method handles adding the plugin's translations.
	 */
	public function add_translations() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'digital-library' );

		load_textdomain( 'digital-library', WP_LANG_DIR . '/digital-library-' . $locale . '.mo' );
		load_plugin_textdomain( 'digital-library', false,
			basename( dirname( DL_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Method handles adding the admin scripts and styles into the admin panel.
	 *
	 * @param string|null $hook The hook (page) name.
	 */
	public function add_admin_scripts( ?string $hook ) {
		if ( 'post.php' === $hook && 'product' === get_post_type() ) {
			wp_enqueue_media();
			wp_register_script( 'digital-library-admin', self::url( 'assets/admin/js/admin.js' ),
				array( 'jquery' ), self::VERSION );
			wp_localize_script( 'digital-library-admin', 'dl_translations',
				array(
					'missing_book_preview'  => __( '(No preview file selected.)', 'digital-library' ),
					'media_frame_title'     => __( 'Please select a book preview', 'digital-library' ),
					'select_preview_button' => __( 'Use this book preview', 'digital-library' ),
				)
			);
			wp_enqueue_script( 'digital-library-admin' );

			wp_enqueue_style( 'digital-library-admin', self::url( 'assets/admin/css/styles.css' ),
				array(), self::VERSION );
		}
	}

	/**
	 * Method handles adding the scripts and styles to the front panel.
	 *
	 */
	public function add_scripts() {
		if ( is_product_category() ) {
			wp_enqueue_script( 'digital-library-widgets',
				self::url( 'front-panel/build/bundle.js' ), array(),
				self::VERSION, true );
		}
	}

	/**
	 * Method handles the disabling of comments for products, if necessary.
	 *
	 * @param bool $open Specifies whether the comments are enabled for this post.
	 * @param int $post_id Specifies the post ID.
	 *
	 * @return bool The new value.
	 */
	public function disable_comments_for_products( bool $open, int $post_id ): bool {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return $open;
		}

		return false;
	}

	/**
	 * Method handles changing the product category template of WooCommerce,
	 * or the respective theme.
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 *
	 * @return string The name of the new template file.
	 */
	public function change_product_category_template(
		string $template,
		string $template_name = '',
		string $template_path = ''
	) {
		$basename = basename( $template );
		if ( 'archive-product.php' === $basename ) {
			return $this->dir( 'templates/archive-product.php' );
		}

		return $template;
	}

	/**
	 * Method adds WooCommerce product fields.
	 */
	public function add_book_fields() {
		global $thepostid;

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
		$preview_id       = intval( get_post_meta( $thepostid, self::BOOK_PREVIEW, true ) );
		$preview_filename = $preview_id !== 0 ? get_the_title( $preview_id ) : '';
		?>
        <p class="form-field dl <?php echo esc_attr( self::BOOK_PREVIEW ) ?>">
            <label for="book_preview_id"><?php esc_attr_e( 'Book preview', 'digital-library' ) ?></label>
            <input id="book_preview_select_button" type="button" class="button button-primary" style="margin-left: 0"
                   value="<?php esc_attr_e( 'Upload a book preview', 'digital-library' ) ?>">
            <input type="hidden" id="book_preview_id" name="<?php echo esc_attr( self::BOOK_PREVIEW ) ?>"
                   value="<?php echo $preview_id ?>">
            <i class="book-preview-indicator" style="margin-inline-start: 10px;"
               id="book_preview_indicator">
				<?php echo empty( $preview_filename ) ?
					esc_attr__( '(No preview file selected.)', 'digital-library' )
					: esc_html( $preview_filename ) ?>
            </i>
            <a class="delete" title="<?php esc_attr_e( 'Remove book preview', 'digital-library' ) ?>"
               id="remote_book_preview_button">
				<?php esc_html_e( 'Remove book preview', 'digital-library' ) ?>
            </a>
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
		$authors    = &$_POST[ self::BOOK_AUTHORS ];
		$subtitle   = &$_POST[ self::BOOK_SUBTITLE ];
		$isbn       = &$_POST[ self::BOOK_ISBN ];
		$preview_id = &$_POST[ self::BOOK_PREVIEW ];

		// Update book authors.
		if ( ! empty( $authors ) ) {
			update_post_meta( $post_id, self::BOOK_AUTHORS, wp_kses( $authors, array() ) );
		}

		// Update book subtitle.
		if ( ! empty( $subtitle ) ) {
			update_post_meta( $post_id, self::BOOK_SUBTITLE, wp_kses( $subtitle, array() ) );
		}

		// Update book isbn.
		if ( ! empty( $isbn ) ) {
			update_post_meta( $post_id, self::BOOK_ISBN, wp_kses( $isbn, array() ) );
		}

		// Update book preview.
		if ( ! empty( $preview_id )
		     && 0 != ( $preview_id = intval( $preview_id ) )
		     && 'attachment' === get_post_type( $preview_id ) ) {
			update_post_meta( $post_id, self::BOOK_PREVIEW, $preview_id );
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