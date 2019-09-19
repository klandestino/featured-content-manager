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
	 * @param WP_REST_Request $request The request as an array.
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
		$posts      = $post_query->posts;

		// Loop through search result to trim unneccesary post fields.
		foreach ( $posts as $post ) {
			$post = self::prepare_post( $post );
			$post = self::populate_thumbnail( $post );
			$post = self::populate_post_human_time( $post );
		}

		return new \WP_REST_Response( $posts, 200 );
	}

	/**
	 * Remove unneccesary fields from posts.
	 * Makes for smaller rest responses and a more clean and fast theme_mods option.
	 *
	 * @param \WP_Post $post The post object to strip.
	 */
	private static function prepare_post( \WP_Post $post ): object {
		$fields_to_unset = [ 'post_content', 'comment_status', 'ping_status', 'post_password', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'guid', 'post_mime_type', 'comment_count' ];
		foreach ( $post as $key => $value ) {
			if ( in_array( $key, $fields_to_unset, true ) ) {
				unset( $post->$key );
			}
		}
		return $post;
	}

	/**
	 * Populate post with human time formated time.
	 *
	 * @param WP_Post $post New post object.
	 */
	private static function populate_post_human_time( $post ) {
		$post->post_human_time = human_time_diff( get_the_time( 'U', $post ), current_time( 'timestamp' ) );
		return $post;
	}

	/**
	 * Populate posts populate_thumbnail.
	 *
	 * @param array $args New thumbnail.
	 */
	private static function populate_thumbnail( $args ) {
		$result = array();
		if ( is_array( $args ) ) {
			foreach ( $args as $post ) {
				$thumbnail_id        = get_post_thumbnail_id( $post->ID );
				$thumbnail_src       = wp_get_attachment_image_src( $thumbnail_id, 'small' );
				$post->thumbnail     = $thumbnail_id;
				$post->thumbnail_src = $thumbnail_src[0];
				$result[]            = $post;
			}
		} else {
				$thumbnail_id        = get_post_thumbnail_id( $args->ID );
				$thumbnail_src       = wp_get_attachment_image_src( $thumbnail_id, 'small' );
				$args->thumbnail     = $thumbnail_id;
				$args->thumbnail_src = $thumbnail_src[0];
				$result              = $args;
		}
		return $result;
	}
}
