<?php

namespace DL;

use WP_Query;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit();

/**
 * Class Book_Search_Controller contains logic for searching books.
 * @package DL
 */
class Book_Search_Controller extends WP_REST_Controller {

	private const POST_TYPE = 'product';

	/**
	 * Book_Search_Controller constructor.
	 *
	 * @param string $namespace
	 */
	public function __construct( string $namespace ) {
		$this->namespace = $namespace;
		$this->rest_base = '/book-search';
	}

	/**
	 * Method retrieves books (items) by specific fields.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		global $wpdb;
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => $request['per_page'] ?? 20,
			'page'           => $request['page'] ?? 1,
			'meta_query'     => array(),
			'tax_query'      => array()
		);
		if ( isset( $request['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => $request['category'],
				'operator'         => 'IN',
				'include_children' => true
			);
		}
		if ( isset( $request['author'] ) ) {
			$args['meta_query'][] = array(
				'key'     => Digital_Library::BOOK_AUTHORS,
				'value'   => $wpdb->esc_like( $request['author'] ),
				'type'    => 'CHAR',
				'compare' => 'LIKE'
			);
		}
		if ( isset( $request['title'] ) ) {
			$args['meta_query'][] = array(
				'key'     => Digital_Library::BOOK_TITLE,
				'value'   => $wpdb->esc_like( $request['title'] ),
				'type'    => 'CHAR',
				'compare' => 'LIKE'
			);
		}
		$query = new WP_Query( $args );

		$res = $query->get_posts();

		return new WP_REST_Response( $res, 200 );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_items' ),
			'args'     => $this->get_collection_params()
		) );
	}
}