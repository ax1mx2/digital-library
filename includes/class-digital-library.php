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
	public const BOOK_UPCOMING = 'dl_book_upcoming';
	public const BOOK_SUBTITLE = 'dl_book_subtitle';
	public const BOOK_AUTHORS = 'dl_book_authors';
	public const BOOK_ISBN = 'dl_book_isbn';
	public const BOOK_EXCERPT = 'dl_book_excerpt_media_id';
	public const BOOK_PREVIEW = 'dl_book_preview_media_id';
	public const BOOK_DATE_PUBLIC = 'dl_book_date_public';
	public const BOOK_ADD_TO_CATEGORY = 'dl_book_add_to_category';
	public const BOOK_MAIN_CATEGORY = 'dl_book_main_category';
	public const BOOK_COPYRIGHT = 'dl_book_copyright';
	public const BOOK_LOCATION = 'dl_book_location';
	public const BOOK_YEAR = 'dl_book_year';
	public const BOOK_PAGES = 'dl_book_pages';
	public const BOOK_EDITION = 'dl_book_edition';

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
			// Add routing for book previews.
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

		// Customize Book View
		add_filter( 'woocommerce_short_description',
			array( $this, 'add_bibliographical_info_box' ), 50, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_front_panel_styles' ) );

		// Add cron hooks.
		add_action( 'add_product_to_product_cat',
			array( $this, 'add_product_to_product_cat' ), 10, 1 );
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
	 * Method carries out additional logic when the plugin is deactivated.
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
			wp_register_script(
				'digital-library-admin',
				self::url( 'assets/admin/js/admin.js' ),
				array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ),
				self::VERSION );
			wp_localize_script( 'digital-library-admin', 'dl_translations',
				array(
					'missingBookExcerpt'      => __( '(No excerpt file selected.)', 'digital-library' ),
					'pleaseSelectBookExcerpt' => __( 'Please select a book excerpt', 'digital-library' ),
					'selectExcerptButton'     => __( 'Use this book excerpt', 'digital-library' ),
					'missingBookPreview'      => __( '(No preview file selected.)', 'digital-library' ),
					'pleaseSelectBookPreview' => __( 'Please select a book preview', 'digital-library' ),
					'selectPreviewButton'     => __( 'Use this book preview', 'digital-library' ),
				)
			);
			wp_enqueue_script( 'digital-library-admin' );

			wp_enqueue_style(
				'digital-library-admin',
				self::url( 'assets/admin/css/styles.css' ),
				array(), self::VERSION );
		}
	}

	/**
	 * Method handles adding the scripts and styles to the front panel.
	 *
	 */
	public function add_scripts() {
		if ( is_product_category() ) {
			wp_register_script( 'digital-library-widgets',
				self::url( 'front-panel/build/bundle.js' ), array(),
				self::VERSION, true );

			wp_localize_script(
				'digital-library-widgets',
				'dl_data',
				array(
					'fetchBooksUrl'    => get_rest_url(
						null,
						self::REST_API_NAMESPACE . Book_Search_Controller::REST_BASE
					),
					// Translations
					'loading'          => __( 'Loading...', 'digital-library' ),
					'noBooksAvailable' => __( 'No books available.', 'digital-library' ),
					'search'           => __( 'Search', 'digital-library' ),
					'titleSearch'      => __( 'Title...', 'digital-library' ),
					'authorsSearch'    => __( 'Authors...', 'digital-library' ),
					'applySearch'      => __( 'Apply', 'digital-library' ),
					'categories'       => __( 'Categories', 'digital-library' ),
					'books'            => __( 'Books', 'digital-library' ),
					'loadingMoreBooks' => __( 'Loading more books...', 'digital-library' )
				)
			);

			wp_enqueue_script( 'digital-library-widgets' );
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

		$product_category_terms = get_terms( 'product_cat' );
		$product_categories     = array( '' => '' );
		/** @var \WP_Term $term */
		foreach ( $product_category_terms as $term ) {
			$product_categories[ $term->term_id ] = $term->name;
		}

		echo '<div class="options_group dl-woocommerce-book-field-group">';
		echo '<div><h2>' . esc_attr( __( 'Book Details', 'digital-library' ) ) . '</h2></div>';

		// Display upcoming.
		$upcoming = get_post_meta( $thepostid, self::BOOK_UPCOMING, true );
		filter_var( $upcoming, FILTER_VALIDATE_BOOLEAN );
		woocommerce_wp_checkbox( array(
			'id'          => self::BOOK_UPCOMING,
			'label'       => __( 'Appear In Upcoming', 'digital-library' ),
			'desc_tip'    => true,
			'description' => esc_html__(
				'Check if book should appear in the upcoming section.',
				'digital-library'
			)
		) );

		// Display authors.
		woocommerce_wp_textarea_input( array(
			'id'          => self::BOOK_AUTHORS,
			'label'       => __( 'Book Authors', 'digital-library' ),
			'placeholder' => __( "Peter Petrov\nDragan Draganov\n...", 'digital-library' ),
			'desc_tip'    => true,
			'description' => esc_html__( 'Enter each author name on a new line.', 'digital-library' ),
			'rows'        => 5
		) );

		// Display subtitle.
		woocommerce_wp_text_input( array(
			'id'    => self::BOOK_SUBTITLE,
			'type'  => 'text',
			'label' => __( 'Book Subtitle', 'digital-library' ),
		) );

		// Display ISBN.
		woocommerce_wp_text_input( array(
			'id'    => self::BOOK_ISBN,
			'type'  => 'text',
			'label' => __( 'Book ISBN', 'digital-library' ),
		) );

		// Display PDF excerpt.
		$excerpt_id       = intval( get_post_meta( $thepostid, self::BOOK_EXCERPT, true ) );
		$excerpt_filename = $excerpt_id !== 0 ? get_the_title( $excerpt_id ) : '';
		?>
        <p class="form-field dl <?php echo esc_attr( self::BOOK_EXCERPT ) ?>">
            <label for="book_excerpt_id"><?php esc_attr_e( 'Book excerpt', 'digital-library' ) ?></label>
            <input id="book_excerpt_select_button" type="button" class="button button-primary" style="margin-left: 0"
                   value="<?php esc_attr_e( 'Upload a book excerpt', 'digital-library' ) ?>">
            <input type="hidden" id="book_excerpt_id" name="<?php echo esc_attr( self::BOOK_EXCERPT ) ?>"
                   value="<?php echo $excerpt_id ?>">
            <i class="book-excerpt-indicator" style="margin-inline-start: 10px;"
               id="book_excerpt_indicator">
				<?php echo empty( $excerpt_filename ) ?
					esc_attr__( '(No excerpt file selected.)', 'digital-library' )
					: esc_html( $excerpt_filename ) ?>
            </i>
            <a class="delete" id="remote_book_excerpt_button"
               title="<?php esc_attr_e( 'Remove book excerpt', 'digital-library' ) ?>">
				<?php esc_html_e( 'Remove book excerpt', 'digital-library' ) ?>
            </a>
        </p>
		<?php

		// Display PDF preview.
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
            <a class="delete" id="remove_book_preview_button"
               title="<?php esc_attr_e( 'Remove book preview', 'digital-library' ) ?>">
				<?php esc_html_e( 'Remove book preview', 'digital-library' ) ?>
            </a>
        </p>
		<?php

		// Display book date public.
		woocommerce_wp_text_input( array(
			'id'                => 'book_date_public_datepicker',
			'name'              => self::BOOK_DATE_PUBLIC,
			'type'              => 'text',
			'label'             => __( 'Book Date Public', 'digital-library' ),
			'custom_attributes' => array( 'autocomplete' => 'off' ),
			'value'             => get_post_meta( $thepostid, self::BOOK_DATE_PUBLIC, true ),
			'desc_tip'          => true,
			'description'       => esc_html__( 'The date from which the book preview becomes available.', 'digital-library' ),
		) );

		// Display book add to category.
		woocommerce_wp_select( array(
			'id'          => self::BOOK_ADD_TO_CATEGORY,
			'label'       => __( 'Add Book to Category', 'digital-library' ),
			'options'     => $product_categories,
			'desc_tip'    => true,
			'description' => esc_html__( 'Add to category after the public date.', 'digital-library' ),
			'value'       => get_post_meta( $thepostid, self::BOOK_ADD_TO_CATEGORY, true )
		) );

		// Display book main category.
		woocommerce_wp_select( array(
			'id'      => self::BOOK_MAIN_CATEGORY,
			'label'   => __( 'Book Main Category', 'digital-library' ),
			'options' => $product_categories,
			'value'   => get_post_meta( $thepostid, self::BOOK_MAIN_CATEGORY, true )
		) );

		// Display copyright.
		woocommerce_wp_textarea_input( array(
			'id'          => self::BOOK_COPYRIGHT,
			'type'        => 'text',
			'placeholder' => __( "Publisher 1\nPublisher 2\n...", 'digital-library' ),
			'label'       => __( 'Book Copyright', 'digital-library' ),
			'desc_tip'    => true,
			'description' => esc_html__( 'Enter each publisher on a new line.', 'digital-library' ),
			'rows'        => 5,
		) );

		// Display book location.
		woocommerce_wp_text_input( array(
			'id'    => self::BOOK_LOCATION,
			'type'  => 'text',
			'label' => __( 'Book Location', 'digital-library' ),
		) );

		// Display book year.
		woocommerce_wp_text_input( array(
			'id'    => self::BOOK_YEAR,
			'type'  => 'number',
			'label' => __( 'Book Year', 'digital-library' ),
		) );

		// Display book pages.
		woocommerce_wp_text_input( array(
			'id'    => self::BOOK_PAGES,
			'type'  => 'number',
			'label' => __( 'Book Pages', 'digital-library' ),
		) );

		// Display book pages.
		woocommerce_wp_text_input( array(
			'id'    => self::BOOK_EDITION,
			'type'  => 'text',
			'label' => __( 'Book Edition', 'digital-library' ),
		) );

		echo '</div>';
	}

	/**
	 * Method contains custom logic for saving a additional book fields.
	 *
	 * @param $post_id int The ID of book.
	 */
	public function save_book_fields( int $post_id ) {
		$upcoming    = &$_POST[ self::BOOK_UPCOMING ];
		$authors     = &$_POST[ self::BOOK_AUTHORS ];
		$subtitle    = &$_POST[ self::BOOK_SUBTITLE ];
		$isbn        = &$_POST[ self::BOOK_ISBN ];
		$excerpt_id  = &$_POST[ self::BOOK_EXCERPT ];
		$preview_id  = &$_POST[ self::BOOK_PREVIEW ];
		$date_public = &$_POST[ self::BOOK_DATE_PUBLIC ];
		$add_to_cat  = &$_POST[ self::BOOK_ADD_TO_CATEGORY ];
		$main_cat    = &$_POST[ self::BOOK_MAIN_CATEGORY ];
		$copyright   = &$_POST[ self::BOOK_COPYRIGHT ];
		$location    = &$_POST[ self::BOOK_LOCATION ];
		$year        = &$_POST[ self::BOOK_YEAR ];
		$pages       = &$_POST[ self::BOOK_PAGES ];
		$edition     = &$_POST[ self::BOOK_EDITION ];

		$upcoming = filter_var( $upcoming, FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, self::BOOK_UPCOMING, $upcoming ? 'yes' : '' );

		// Update book subtitle.
		if ( ! is_null( $subtitle ) ) {
			update_post_meta( $post_id, self::BOOK_SUBTITLE, wp_kses( $subtitle, array() ) );
		}

		// Update book authors.
		if ( ! is_null( $authors ) ) {
			update_post_meta( $post_id, self::BOOK_AUTHORS, wp_kses( $authors, array() ) );
		}

		// Update book ISBN.
		if ( ! is_null( $isbn ) ) {
			update_post_meta( $post_id, self::BOOK_ISBN, wp_kses( $isbn, array() ) );
		}

		// Update book excerpt.
		if ( ! is_null( $excerpt_id ) ) {
			if ( 0 == ( $excerpt_id = intval( $excerpt_id ) )
			     || 'attachment' !== get_post_type( $excerpt_id )
			) {
				$excerpt_id = '';
			}
			update_post_meta( $post_id, self::BOOK_EXCERPT, $excerpt_id );
		}

		// Update book preview.
		if ( ! is_null( $preview_id ) ) {
			if ( 0 == ( $preview_id = intval( $preview_id ) )
			     || 'attachment' !== get_post_type( $preview_id )
			) {
				$preview_id = '';
			}
			update_post_meta( $post_id, self::BOOK_PREVIEW, $preview_id );
		}

		// Update date public.
		if ( ! is_null( $date_public ) ) {
			$date_public = date( 'Y-m-d', strtotime( $date_public ) );
			update_post_meta( $post_id, self::BOOK_DATE_PUBLIC, $date_public );
		}

		// Update add to category.
		if ( ! is_null( $add_to_cat ) ) {
			$add_to_cat = intval( $add_to_cat );
			$term       = get_term( $add_to_cat, 'product_cat' );
			if ( empty( $term ) || is_wp_error( $term ) ) {
				$add_to_cat = '';
			}
			update_post_meta( $post_id, self::BOOK_ADD_TO_CATEGORY, $add_to_cat );
			if ( ! empty( $add_to_cat ) && ! has_term( $term, 'product_cat', $post_id ) ) {
				// Check if event is scheduled an remove it.
				$add_to_cat_timestamp = wp_next_scheduled(
					'add_product_to_product_cat', array( $post_id ) );
				if ( false !== $add_to_cat_timestamp ) {
					wp_unschedule_event(
						$add_to_cat_timestamp,
						'add_product_to_product_cat',
						array( $post_id )
					);
				}

				// Schedule the new event.
				if ( isset( $date_public )
				     && ! empty( $execution_date = strtotime( $date_public ) )
				) {
					wp_schedule_single_event(
						$execution_date,
						'add_product_to_product_cat',
						array( $post_id )
					);
				}
			}
		}

		// Update main category.
		if ( ! is_null( $main_cat ) ) {
			$main_cat = intval( $main_cat );
			$term     = get_term( $main_cat, 'product_cat' );
			if ( empty( $term ) || is_wp_error( $term ) ) {
				$main_cat = '';
			}
			update_post_meta( $post_id, self::BOOK_MAIN_CATEGORY, $main_cat );
			if ( ! empty( $main_cat ) && ! has_term( $term, 'product_cat', $post_id ) ) {
				wp_set_post_categories( $post_id, array( $main_cat ), true );
			}
		}

		// Update copyright.
		if ( ! is_null( $copyright ) ) {
			update_post_meta( $post_id, self::BOOK_COPYRIGHT, $copyright );
		}

		// Update year.
		if ( ! is_null( $year ) ) {
			if ( ! empty( $year ) ) {
				$year = intval( $year );
			}
			update_post_meta( $post_id, self::BOOK_YEAR, $year );
		}

		// Update location.
		if ( ! is_null( $location ) ) {
			update_post_meta( $post_id, self::BOOK_LOCATION, $location );
		}

		// Update pages.
		if ( ! is_null( $pages ) ) {
			if ( ! empty( $page ) ) {
				$pages = intval( $page );
			}
			update_post_meta( $post_id, self::BOOK_PAGES, $pages );
		}

		// Update edition.
		if ( ! is_null( $edition ) ) {
			update_post_meta( $post_id, self::BOOK_EDITION, $edition );
		}

	}

	/**
	 * Method registers REST Controllers.
	 */
	public function register_rest_controllers() {
		$book_search_controller = new Book_Search_Controller( self::REST_API_NAMESPACE );
		$book_search_controller->register_routes();
	}

	/**
	 * Method handles adding the product to a new product category.
	 * (To be used in events)
	 *
	 * @param int $product_id Specifies the ID of the product.
	 */
	public function add_product_to_product_cat( int $product_id ) {
		if ( 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		$new_category_id = get_post_meta(
			$product_id, self::BOOK_ADD_TO_CATEGORY, true );

		$new_category = get_term( $new_category_id, 'product_cat' );
		if ( empty( $new_category ) || is_wp_error( $new_category ) ) {
			return;
		}

		wp_set_object_terms(
			$product_id,
			array( $new_category->term_id ),
			'product_cat',
			true
		);
	}

	/**
	 * Method handles adding bibliographical info box on the product page.
	 *
	 * @param string $excerpt
	 *
	 * @return string
	 */
	public function add_bibliographical_info_box( string $excerpt ) {
		if ( ! is_product() ) {
			return $excerpt;
		}

		global $product;
		$id = $product->get_id();

		$main_category_id = get_post_meta( $id, self::BOOK_MAIN_CATEGORY, true );
		if ( ! empty( $main_category_id ) ) {
			$main_category = get_term( $main_category_id, 'product_cat' );
			if ( is_wp_error( $main_category ) ) {
				$main_category = null;
			}
		}
		$isbn      = get_post_meta( $id, self::BOOK_ISBN, true );
		$copyright = get_post_meta( $id, self::BOOK_COPYRIGHT, true );
		$copyright = preg_split( '/\r\n|\n|\r/', $copyright );
		$location  = get_post_meta( $id, self::BOOK_LOCATION, true );
		$year      = get_post_meta( $id, self::BOOK_YEAR, true );
		$pages     = get_post_meta( $id, self::BOOK_PAGES, true );
		$edition   = get_post_meta( $id, self::BOOK_EDITION, true );

		ob_start();
		?>
        <div class="bib-info">
			<?php if ( ! empty( $main_category ) ): ?>
                <p>
                    <span class="bib-label">
                        <?php esc_html_e( 'Main category:', 'digtial-library' ) ?>
                    </span>
                    <span class="bib-value">
                        <a href="<?php echo esc_url( get_term_link( $main_category ) ) ?>">
                        <?php echo esc_html( $main_category->name ) ?>
                        </a>
                    </span>
                </p>
			<?php endif; ?>
			<?php if ( ! empty( $isbn ) ): ?>
                <p>
                    <span class="bib-label">
                        <?php esc_html_e( 'Print ISBN:', 'digital-library' ) ?>
                    </span>
                    <span class="bib-value">
                        <?php echo esc_html( $isbn ) ?>
                    </span>
                </p>
			<?php endif; ?>
			<?php if ( ! empty( $copyright ) ): ?>
                <p>
                    <span class="bib-label">
                        <?php esc_html_e( 'Copyright Information', 'digital-library' ) ?>
                    </span>
                    <span class="bib-cpright">
					<?php foreach ( $copyright as $copyright_line ): ?>
                        <br>
                        <span class="bib-value">&copy;<?php echo esc_html( $copyright_line ) ?></span>
					<?php endforeach; ?>
                    </span>
                </p>
			<?php endif; ?>
			<?php if ( ! empty( $location ) || ! empty( $year ) || ! empty( $pages ) ): ?>
                <p>
                    <span class="bib-value">
					<?php if ( ! empty( $location ) || ! empty( $year ) ): ?>
                        <strong>
							<?php echo esc_html(
								implode( ', ', array_filter( array( $location, $year ) ) )
								. ( empty( $pages ) ? '' : ',' )
							) ?>
                        </strong>
					<?php endif; ?>
	                    <?php echo empty( $pages ) ? ''
		                    : sprintf(
			                    __( '%s p.', 'digital-library' ),
			                    $pages
		                    ) ?>
                    </span>
                </p>
			<?php endif; ?>
	        <?php if ( ! empty( $edition ) ): ?>
                <p>
                    <em>
	                    <?php echo esc_html( $edition ) ?>
                    </em>
                </p>
	        <?php endif; ?>
        </div>
		<?php
		return $excerpt . ob_get_clean();
	}

	/**
	 * Method handles adding the front panel styles.
	 */
	public function add_front_panel_styles() {
		if ( is_product() ) {
			wp_enqueue_style(
				'dl_fp_prod',
				plugin_dir_url( DL_PLUGIN_FILE ) . 'front-panel/css/product.css',
				array(),
				'0.0.1'
			);
		}
		if ( is_product_category() ) {
			wp_enqueue_style(
				'dl_fp_prod_cat',
				plugin_dir_url( DL_PLUGIN_FILE ) . 'front-panel/css/category.css',
				array(),
				'0.0.1'
			);
		}
	}
}