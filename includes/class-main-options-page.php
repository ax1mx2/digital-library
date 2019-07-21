<?php

namespace DL;

defined( 'ABSPATH' ) || exit();

final class Main_Options_Page {

	/**
	 * Specifies the name of the options group.
	 */
	public const OPTIONS_GROUP = 'dl_main';

	public const DISABLE_PRODUCT_COMMENTS = 'disable_product_comments';

	/** @var array Specifies all option keys. */
	public const ALL_OPTIONS = array(
		self::DISABLE_PRODUCT_COMMENTS
	);

	/**
	 * @var Main_Options_Page The instance of the main options page.
	 */
	private static $instance;

	/**
	 * Main_Options_Page constructor.
	 */
	protected function __construct() {
		// Register settings.
		add_action( 'admin_init', array( $this, 'add_page_settings' ) );

		// Register options page.
		add_action( 'admin_menu', array( $this, 'add_settings_menus' ) );
	}

	/**
	 * @return Main_Options_Page The instance of the main options page.
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Method initializes
	 */
	public static function init() {
		self::instance();
	}

	/**
	 * Method handles registering settings for the main plugin page.
	 */
	public function add_page_settings() {
		register_setting( self::OPTIONS_GROUP,
			self::DISABLE_PRODUCT_COMMENTS, array(
				'type'    => 'boolean',
				'group'   => self::OPTIONS_GROUP,
				'default' => 'off'
			) );
	}

	/**
	 * Method handles registering the settings menu entries.
	 */
	public function add_settings_menus() {
		add_submenu_page( 'options-general.php', _x( 'Digital Library', 'settings', 'digital-library' ),
			_x( 'Digital Library', 'settings', 'digital-library' ), 'manage_options',
			'digital-library-main', array( $this, 'main_page' ) );
	}

	/**
	 * Method handles displaying main settings page.
	 */
	public function main_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized user' );
		}
		?>
        <div class="wrap">
            <h2><?php _e( 'Digital Library Settings', 'digital-library' ) ?></h2>
            <p><?php _e( 'This plugin enables the creation of digital libraries with the help of WooCommerce.', 'digital-library' ) ?></p>
            <form method="post" action="options.php">
				<?php settings_fields( self::OPTIONS_GROUP ) ?>
				<?php do_settings_sections( self::OPTIONS_GROUP ) ?>
                <table class="form-table">
                    <tbody>
					<?php $this->do_main_options() ?>
                    </tbody>
                </table>
				<?php submit_button() ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Method display main options.
	 */
	public function do_main_options() {
		// Sanitize options retrieved from the database.
		$disable_product_comments = filter_var( get_option( self::DISABLE_PRODUCT_COMMENTS ), FILTER_VALIDATE_BOOLEAN );
		ob_start();
		?>
        <tr valign="top">
            <th scope="row"><?php _e( 'Disable comments for all products.', 'digital-library' ) ?></th>
            <td><input type="checkbox"
                       name="<?php echo esc_attr( self::DISABLE_PRODUCT_COMMENTS ) ?>"<?php echo $disable_product_comments ? ' checked' : '' ?>>
            </td>
        </tr>
		<?php
	}

}