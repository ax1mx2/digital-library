<?php

namespace DL;

/*
Plugin Name: Digital Library
Description: Auxiliary plugin that enables the creation of digital libraries with WooCommerce.
Version: 0.0.3
Author: A. Milanov
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

if ( ! defined( 'DL_PLUGIN_FILE' ) ) {
	define( 'DL_PLUGIN_FILE', __FILE__ );
}

// Include the main plugin class.
if ( ! class_exists( 'Digital_Library' ) ) {
	include_once __DIR__ . '/includes/class-digital-library.php';
}

/**
 * Initializes the Digital Library plugin.
 */
function dl_init_digital_library() {
	Digital_Library::instance();
}

dl_init_digital_library();