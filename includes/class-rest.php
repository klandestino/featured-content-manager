<?php
/**
 * Class Featured Content Manager Rest API.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

/**
 * This class handles all the REST API functions for Featured Content Manager.
 */
class Rest {
	/**
	 * Register the REST API routes.
	 */
	public static function register_routes() {
		$version   = '1';
		$namespace = 'featured-content-manager/v' . $version;
		$base      = 'items';
		$posts     = 'posts';

		register_rest_route(
			$namespace,
			'/' . $posts,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array(
						'Featured_Content_Manager\Rest',
						'search_posts',
					),
					'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
				),
			)
		);
	}

	/**
	 * Check the user premisson.
	 */
	public static function check_user_permission() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Search posts.
	 *
	 * @param mixed[] $args An array of search argumetns.
	 */
	public static function fcm_post_search( $args = array( 'search_term' => '' ) ) {
		$query  = array(
			'post_type'      => $args['object_subtype'],
			'posts_per_page' => 10,
			'post_status'    => apply_filters( 'fcm_post_status', array( 'publish', 'future' ) ),
			's'              => $args['search_term'],
		);
		$result = get_posts( $query );
		return $result;
	}

	/**
	 * Search terms.
	 *
	 * @param mixed[] $args An array of search argumetns.
	 */
	public static function fcm_term_search( $args = array( 'search_term' => '' ) ) {
		$query  = array(
			'taxonomy' => $args['object_subtype'],
			'number'   => 10,
			'search'   => $args['search_term'],
		);
		$result = get_terms( $query );
		return $result;
	}

	/**
	 * Search posts function.
	 *
	 * @param \WP_REST_Request $request The request as an array.
	 */
	public static function search_posts( \WP_REST_Request $request ) {
		$search_term     = ( ! empty( $request['s'] ) ? $request['s'] : '' );
		$object_type     = ( ! empty( $request['type'] ) ? $request['type'] : [ 'post' ] );
		$object_subtype  = ( ! empty( $request['subtype'] ) ? $request['subtype'] : [ 'post' ] );
		$result          = apply_filters(
			"fcm_{$object_type}_search",
			array(
				'search_term'    => $search_term,
				'object_subtype' => $object_subtype,
			)
		);
		$filtered_result = apply_filters( "fcm_{$object_type}_filter_result", $result );

		return new \WP_REST_Response( $filtered_result, 200 );
	}

	/**
	 * Filtering post search result.
	 *
	 * @param mixed[] $result An array of search results.
	 */
	public static function fcm_post_filter_result( $result = array() ) {
		$filtered_result = array();
		foreach ( $result as $post ) {
			$filtered_result[] = self::prepare_post( $post );
		}
		return $filtered_result;
	}

	/**
	 * Filtering term search result.
	 *
	 * @param mixed[] $result An array of search results.
	 */
	public static function fcm_term_filter_result( $result = array() ) {
		$filtered_result = array();
		foreach ( $result as $term ) {
			$filtered_result[] = self::prepare_term( get_term( $term ) );
		}
		return $filtered_result;
	}

	/**
	 * Remove unneccesary fields from terms.
	 * Makes for smaller rest responses and a more clean and fast theme_mods option.
	 *
	 * @param \WP_Term $term The term object to strip.
	 */
	private static function prepare_term( \WP_Term $term ): array {
		$prepared_term                  = array();
		$prepared_term['id']            = $term->term_id;
		$prepared_term['title']         = html_entity_decode( $term->name );
		$prepared_term['type']          = 'term';
		$prepared_term['subtype']       = $term->taxonomy;
		$prepared_term['subtype_label'] = get_taxonomy( $term->taxonomy )->labels->singular_name;
		$prepared_term['meta']          = array();
		return $prepared_term;
	}

	/**
	 * Remove unneccesary fields from posts.
	 * Makes for smaller rest responses and a more clean and fast theme_mods option.
	 *
	 * @param \WP_Post $post The post object to strip.
	 */
	private static function prepare_post( \WP_Post $post ): array {
		$prepared_post                            = array();
		$prepared_post['id']                      = $post->ID;
		$prepared_post['title']                   = html_entity_decode( $post->post_title );
		$prepared_post['type']                    = 'post';
		$prepared_post['subtype']                 = $post->post_type;
		$prepared_post['subtype_label']           = get_post_type_object( $post->post_type )->labels->singular_name;
		$prepared_post['meta']                    = array();
		$prepared_post['meta']['human_time_diff'] = human_time_diff( get_the_time( 'U', $post ), strtotime( wp_date( 'Y-m-d H:i:s' ) ) );
		$prepared_post['meta']['post_status']     = $post->post_status;
		return $prepared_post;
	}
}
