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
	public const REST_BASE = '/book-search';

	/**
	 * Book_Search_Controller constructor.
	 *
	 * @param string $namespace
	 */
	public function __construct( string $namespace ) {
		$this->namespace = $namespace;
		$this->rest_base = self::REST_BASE;
	}

	private static function trim( string $str ): string {
		return preg_replace( '/(^\s+)|(\s+$)/us', '', $str );
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
			'post_status'    => 'publish',
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => $request['per_page'],
			'paged'          => $request['page'],
			'meta_query'     => array(
				array(
					'relation' => 'OR',
					array(
						'key'   => Digital_Library::BOOK_UPCOMING,
						'value' => false,
						'type'  => 'BOOLEAN'
					),
					array(
						'key'     => Digital_Library::BOOK_UPCOMING,
						'compare' => 'NOT EXISTS'
					)
				)
			),
			'tax_query'      => array()
		);

		$category_id = $request['category'];
		if ( isset( $category_id ) and is_int( $category_id ) ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => $request['category'],
				'operator'         => 'IN',
				'include_children' => true
			);
		}

		$title_search = self::trim( $request['title'] ?? '' );
		if ( ! empty ( $title_search ) ) {
			add_filter( 'posts_join', array( $this, 'title_filter_join' ), 10, 2 );
			add_filter( 'posts_where', array( $this, 'title_filter_where' ), 10, 2 );
			add_filter( 'posts_groupby', array( $this, 'title_filter_groupby' ), 10, 2 );
			// Search by post title.
			$args['search_book_title'] = $title_search;
		}

		$authors_search = self::trim( $request['authors'] ?? '' );
		if ( ! empty( $authors_search ) ) {
			$args['meta_query'][] = array(
				'key'     => Digital_Library::BOOK_AUTHORS,
				'value'   => $authors_search,
				'type'    => 'CHAR',
				'compare' => 'LIKE'
			);
		}

		$query = new WP_Query( $args );

		$books = array_map(
			array( Digital_Library::instance(), 'map_book' ),
			$query->get_posts()
		);

		$response = array(
			'pages' => $query->max_num_pages,
			'books' => $books
		);

		return new WP_REST_Response( $response, 200 );
	}


	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, array(
			'methods'  => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
			'callback' => array( $this, 'get_items' ),
			'args'     => $this->get_collection_params()
		) );
	}

	/**
	 * Method handles the custom query variable 'search_book_title'.
	 *
	 * @param string $join
	 * @param WP_Query $wp_query
	 *
	 * @return string
	 */
	public function title_filter_join( string $join, WP_Query $wp_query ): string {
		global $wpdb;
		// Don't process, if not a book (product).
		if ( 'product' !== $wp_query->get( 'post_type' ) ) {
			return $join;
		}

		if ( ! empty( $title_search = $wp_query->get( 'search_book_title' ) ) ) {
			$join .= " INNER JOIN `{$wpdb->postmeta}` AS `book_subtitles` ON `{$wpdb->posts}`.`ID` = `book_subtitles`.`post_id` ";
		}

		return $join;
	}

	/**
	 * Method handles the custom query variable 'search_book_title'.
	 *
	 * @param string $where
	 * @param WP_Query $wp_query
	 *
	 * @return string
	 */
	public function title_filter_where( string $where, WP_Query $wp_query ): string {
		global $wpdb;
		// Don't process, if not a book (product).
		if ( 'product' !== $wp_query->get( 'post_type' ) ) {
			return $where;
		}

		if ( ! empty( $title_search = $wp_query->get( 'search_book_title' ) ) ) {
			$where .= $wpdb->prepare(
				" AND (`{$wpdb->posts}`.`post_title` LIKE %s OR (`book_subtitles`.`meta_key` = %s AND `book_subtitles`.`meta_value` LIKE %s))",
				array(
					'%' . $wpdb->esc_like( $title_search ) . '%',
					Digital_Library::BOOK_SUBTITLE,
					'%' . $wpdb->esc_like( $title_search ) . '%'
				)
			);
		}

		return $where;
	}

	/**
	 * Method handles the custom query variable 'search_book_title'.
	 *
	 * @param string $groupby
	 * @param WP_Query $wp_query
	 *
	 * @return string
	 */
	public function title_filter_groupby( string $groupby, WP_Query $wp_query ): string {
		global $wpdb;
		// Don't process, if not a book (product).
		if ( 'product' !== $wp_query->get( 'post_type' ) ) {
			return $groupby;
		}

		if ( ! empty( $wp_query->get( 'search_book_title' ) ) ) {
			$id_col     = "{$wpdb->posts}.ID";
			$group_cols = explode( ',', $groupby );
			foreach ( $group_cols as $group_col ) {
				$group_col = self::trim( $group_col );
				if ( false !== stripos( $groupby, $id_col ) ) {
					return $groupby;
				}

				$groupby .= "{$wpdb->posts}.ID";
			}
		}

		return $groupby;
	}
}