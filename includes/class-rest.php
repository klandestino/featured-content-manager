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
					'methods'             => \WP_REST_Server::READABLE,
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
	 * Search posts function.
	 *
	 * @param \WP_REST_Request $request The request as an array.
	 */
	public static function search_posts( \WP_REST_Request $request ) {
		$search_term = ( isset( $request['s'] ) ? $request['s'] : '' );
		$args        = array(
			'post_type'      => 'post',
			'posts_per_page' => 10,
			'post_status'    => apply_filters( 'fcm_post_status', [ 'publish', 'future' ] ),
			's'              => $search_term,
		);

		$post_query = new \WP_Query( $args );
		$posts      = [];

		// Loop through search result to trim unneccesary post fields.
		foreach ( $post_query->posts as $post ) {
			$posts[] = self::prepare_post( $post );
		}
		wp_reset_postdata();

		return new \WP_REST_Response( $posts, 200 );
	}

	/**
	 * Remove unneccesary fields from posts.
	 * Makes for smaller rest responses and a more clean and fast theme_mods option.
	 *
	 * @param \WP_Post $post The post object to strip.
	 */
	private static function prepare_post( \WP_Post $post ): array {
		$prepared_post                    = [];
		$prepared_post['id']              = $post->ID;
		$prepared_post['post_title']      = $post->post_title;
		$prepared_post['post_status']     = $post->post_status;
		$prepared_post['human_time_diff'] = human_time_diff( get_the_time( 'U', $post ), current_time( 'timestamp' ) );
		$fields_to_keep                   = array_keys( Featured_Content::get_fields() );
		if ( in_array( 'post_excerpt', $fields_to_keep, true ) ) {
			$prepared_post['post_excerpt'] = get_the_excerpt( $post );
		}
		if ( in_array( 'thumbnail', $fields_to_keep, true ) ) {
			$prepared_post['thumbnail']     = get_post_thumbnail_id( $post->ID );
			$prepared_post['thumbnail_src'] = get_the_post_thumbnail_url( $post->ID, 'medium' );
		}
		return $prepared_post;
	}
}
