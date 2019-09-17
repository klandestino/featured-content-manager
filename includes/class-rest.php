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

		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array(
						'Featured_Content_Manager\Rest',
						'create_featured_item',
					),
					'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array(
						'Featured_Content_Manager\Rest',
						'update_featured_item',
					),
					'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array(
						'Featured_Content_Manager\Rest',
						'delete_featured_item',
					),
					'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array(
						'Featured_Content_Manager\Rest',
						'save_settings',
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
		$fields_to_unset = [ 'post_content', 'comment_status', 'ping_status', 'post_password', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'guid', 'post_mime_type', 'comment_count', 'filter' ];
		foreach ( $post as $key => $value ) {
			if ( in_array( $key, $fields_to_unset, true ) ) {
				unset( $post->$key );
			}
		}
		return $post;
	}

	/**
	 * Create the featured content post.
	 *
	 * @param WP_Post $post_data The original post object.
	 */
	private static function create_featured_content( $post_data ) {
		$post = $post_data;

		// If featured content already exist make sure its a draft and return it
		// Else make a copy of the original post and return the copy.
		if ( 'featured-content' === get_post_type( $post ) ) {
			return self::update_featured_content( $post );
		} else {
			return self::create_featured_content_from_post( $post, $post->ID, 'draft' );
		}
	}

	/**
	 * Update the featured content post.
	 *
	 * @param WP_Post $post The original post object.
	 */
	private static function update_featured_content( $post ) {
		$result = self::update_post(
			array(
				'ID'          => intval( $post->ID ),
				'post_status' => 'draft',
			)
		);
		if ( ! is_wp_error( $result ) ) {
			$result = get_post( $result );
			$result = self::populate_original_post_id( $result );
			$result = self::populate_taxonomies( $result );
			return self::populate_thumbnail( $result );
		}
	}

	/**
	 * Copy the original posts terms.
	 *
	 * @param WP_Post $post The original post object.
	 */
	private static function populate_taxonomies( $post ) {
		$fields = Featured_Content::get_fields();
		foreach ( $fields as $field ) {
			if ( 'taxonomy' === $field['type'] ) {
				$terms = get_the_terms( $post->ID, $field['name'] );
				if ( ! empty( $terms ) ) {
					$key        = 'taxonomy_' . $field['name'];
					$post->$key = $terms[0]->name;
				}
			}
		}
		return $post;
	}

	/**
	 * Copy the original posts to a featured content.
	 *
	 * @param WP_Post $post The original post object.
	 * @param WP_Post $org_post_id The original post object id.
	 * @param string  $post_status The new post object status.
	 * @param int     $post_parent The new post object parent id.
	 */
	private static function create_featured_content_from_post( $post, $org_post_id, $post_status, $post_parent = 0 ) {
		$author      = wp_get_current_user();
		$org_post_id = $post->ID;
		$menu_order  = $post->menu_order;

		$accepted_values = [
			'post_title'     => '',
			'post_excerpt'   => '',
			'post_content'   => '',
			'post_date'      => '',
			'post_date_gmt'  => '',
			'featured_media' => '',
		];

		$org_post = get_post( $org_post_id, ARRAY_A );

		// Trim and copy post excerpt to the new post content.
		$org_post['post_content'] = wp_strip_all_tags( wp_trim_words( get_the_excerpt( $post->ID ) ) );

		$new_post = array_intersect_key( $org_post, $accepted_values );
		$new_data = array(
			'post_status' => $post_status,
			'post_author' => $author->ID,
			'post_type'   => 'featured-content',
			'menu_order'  => $menu_order,
			'post_parent' => $post_parent,
		);

		$new_post = array_merge( $new_post, $new_data );
		$result   = wp_insert_post( $new_post );

		add_post_meta( $result, 'original_post_id', $org_post_id );
		wp_set_post_terms( $result, $post->featured_area, 'featured-area', false );

		// If orgininal post has thumbnail, set same thumbnail for featured item.
		$org_post_thumbnail = get_post_thumbnail_id( $post->ID );
		if ( $org_post_thumbnail ) {
			set_post_thumbnail( $result, $org_post_thumbnail );
		}
		$result = get_post( $result );
		$result = self::populate_original_post_id( $result );
		$result = self::populate_taxonomies( $result );
		return self::populate_thumbnail( $result );
	}

	/**
	 * Create a featured item from saved settings.
	 *
	 * @param \WP_REST_Request $request The post request.
	 */
	public static function create_featured_item( \WP_REST_Request $request ) {
		// Get request body as JSON Object.
		$data = json_decode( $request->get_body() );

		// Populate result with featured content.
		$result = array();

		// If request contains more than one post loop through ELSE there is a new item created.
		if ( isset( $data->settings ) && is_array( $data->settings ) && ! empty( $data->settings ) ) {

			// Delete all drafted featured content in this area that is not included in settings.
			$query = new \WP_Query(
				array(
					'post_type'      => 'featured-content',
					'post_status'    => 'draft',
					'post__not_in'   => wp_list_pluck( $data->settings, 'ID' ),
					'tax_query'      => array(
						array(
							'taxonomy' => 'featured-area',
							'field'    => 'slug',
							'terms'    => $data->settings[0]->featured_area,
						),
					),
					'fields'         => 'ids',
					'posts_per_page' => 100,
				)
			);
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					wp_delete_post( get_the_ID(), true );
				}
				wp_reset_postdata();
			}

			foreach ( $data->settings as $post_data ) {
				$result[] = self::prepare_post( self::create_featured_content( $post_data ) );
			}
			return new \WP_REST_Response( $result, 200 );
		} elseif ( isset( $data->obj ) ) {
			$result[] = self::prepare_post( self::create_featured_content( $data->obj ) );
			return new \WP_REST_Response( $result, 200 );
		}

		// If something goes wrong return response error.
		return new \WP_REST_Response( array(), 200 );
	}

	/**
	 * Update a featured item from saved settings.
	 *
	 * @param WP_REST_Request $request The post request.
	 */
	private function update_featured_item( \WP_REST_Request $request ) {
		$fields      = Featured_Content::get_fields();
		$post_id     = intval( $request['id'] );
		$post_parent = intval( $request['post_parent'] );
		$menu_order  = intval( $request['menu_order'] );
		$thumbnail   = intval( $request['thumbnail'] );
		$post        = array(
			'ID'          => $post_id,
			'post_parent' => $post_parent,
			'menu_order'  => $menu_order,
			'post_status' => 'draft',
		);

		foreach ( $fields as $field ) {
			$post[ $field['name'] ] = $request[ $field['name'] ];
		}

		$result = self::update_post( $post );

		if ( null !== $thumbnail ) {
			set_post_thumbnail( $result, $thumbnail );
		}

		$result = get_post( $result );
		$result = self::populate_original_post_id( $result );
		$result = self::populate_taxonomies( $result );
		$result = self::populate_thumbnail( $result );
		$result = self::prepare_post( $result );

		if ( $result ) {
			return new \WP_REST_Response( $result, 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}

	/**
	 * Delete a featured item from saved settings.
	 *
	 * @param WP_REST_Request $request The post request.
	 */
	public static function delete_featured_item( \WP_REST_Request $request ) {
		$post_id = intval( $request['id'] );
		$result  = wp_delete_post( $post_id, true );

		if ( $result ) {
			$result = get_post( $result );
			$result = self::prepare_post( $result );
			return new \WP_REST_Response( $result, 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}

	/**
	 * Save settings from customizer.
	 *
	 * @param WP_REST_Request $request The post request.
	 */
	public static function save_settings( \WP_REST_Request $request ) {
		$featured_items = json_decode( $request->get_body() );

		if ( empty( $featured_items ) ) {
			return new \WP_REST_Response( 'OK', 200 );
		}

		$saved_items = [];
		foreach ( $featured_items as $featured_item ) {
			$result = self::update_post( $featured_item );
			if ( ! is_wp_error( $result ) ) {
				$saved_items[] = $result;
			}
		}

		return new \WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Update featured content post.
	 *
	 * @param WP_Post $featured_item New post object.
	 */
	private static function update_post( $featured_item ) {
		$result = wp_update_post( $featured_item, true );

		foreach ( $featured_item as $key => $value ) {
			if ( 0 === strrpos( $key, 'taxonomy_' ) ) {
				wp_set_post_terms( $result, $value, substr( $key, 9 ), false );
			}
		}

		if ( isset( $featured_item->thumbnail ) ) {
			( '' === $featured_item->thumbnail ) ? delete_post_thumbnail( $result ) : set_post_thumbnail( $result, $featured_item->thumbnail );
		}

		return $result;
	}

	/**
	 * Populate posts original id.
	 *
	 * @param WP_Post $post New post object.
	 */
	private static function populate_original_post_id( $post ) {
		$original_post_id       = get_post_meta( $post->ID, 'original_post_id', true );
		$post->original_post_id = $original_post_id;
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
