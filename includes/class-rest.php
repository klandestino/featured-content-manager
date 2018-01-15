<?php
/**
 * Class Featured Content Manager Rest API.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

class Rest {
	public static function register_routes() {
		$version = '1';
		$namespace = 'featured-content-manager/v' . $version;
		$base = 'items';
		$posts = 'posts';

		register_rest_route( $namespace, '/' . $posts, array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array(
					'Featured_Content_Manager\Rest',
					'search_posts',
				),
			),
		) );

		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => array(
					'Featured_Content_Manager\Rest',
					'create_featured_item',
				),
				'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
			),
		) );

		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
			array(
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => array(
					'Featured_Content_Manager\Rest',
					'update_featured_item',
				),
				'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
			),
			array(
				'methods' => \WP_REST_Server::DELETABLE,
				'callback' => array(
					'Featured_Content_Manager\Rest',
					'delete_featured_item',
				),
				'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
			),
		) );

		register_rest_route( $namespace, '/settings', array(
			array(
				'methods' => \WP_REST_Server::CREATABLE,
				'callback' => array(
					'Featured_Content_Manager\Rest',
					'save_settings',
				),
				'permission_callback' => array( 'Featured_Content_Manager\Rest', 'check_user_permission' ),
			),
		) );
	}

	public static function check_user_permission() {
		return current_user_can( 'edit_posts' );
	}

	public static function search_posts( \WP_REST_Request $request ) {
		$search_term = (isset( $request['s'] ) ? $request['s'] : '');
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => 10,
			'post_status' => 'publish',
			's' => $search_term,
		);

		$post_query = new \WP_Query( $args );
		$posts = $post_query->posts;
		return new \WP_REST_Response( $posts, 200 );
	}

	private static function create_featured_content( $post_data ) {
		$post = $post_data;

		// If featured content already exist make sure its a draft and return it
		// Else make a copy of the original post and return the copy
		if ( 'featured-content' === get_post_type( $post ) ) {
			if ( false === get_post_status( $post->ID ) ) {
				return self::create_featured_content_from_post( $post->ID, $post->original_post_id, 'draft' );
			} else {
				return self::update_featured_content( $post );
			}
		} else {
			return self::create_featured_content_from_post( $post, $post->ID, 'draft' );
		}
	}

	private static function update_featured_content( $post ) {
		$result = wp_update_post( array(
			'ID' => intval( $post->ID ),
			'post_status' => 'draft',
		), true );
		if ( ! is_wp_error( $result ) ) {
			$result = get_post( $result );
			$result = self::populate_original_post_id( $result );
			return self::populate_thumbnail( $result );
		}
	}

	private static function create_featured_content_from_post( $post, $org_post_id, $post_status, $post_parent = 0 ) {
		$author = wp_get_current_user();
		$org_post_id = $post->ID;
		$menu_order = $post->menu_order;

		$accepted_values = [
			'post_title' => '',
			'post_content' => '',
			'featured_media' => '',
		];

		$org_post = get_post( $org_post_id, ARRAY_A );
		$new_post = array_intersect_key( $org_post, $accepted_values );
		$new_data = array(
			'post_status' => $post_status,
			'post_author' => $author->ID,
			'post_type' => 'featured-content',
			'menu_order' => $menu_order,
			'post_parent' => $post_parent,
		);

		$new_post = array_merge( $new_post, $new_data );
		$result = wp_insert_post( $new_post );

		add_post_meta( $result, 'original_post_id', $org_post_id );

		if ( get_post_meta( $org_post_id, '_thumbnail_id', true ) ) {
			add_post_meta( $result, '_thumbnail_id', get_post_meta( $org_post_id, '_thumbnail_id', true ) );
		}

		wp_set_post_terms( $result, $post->featured_area, 'featured-area', false );

		// If orgininal post has thumbnail, set same thumbnail for featured item
		$org_post_thumbnail = get_post_thumbnail_id( $post->ID );
		if ( $org_post_thumbnail ) {
			set_post_thumbnail( $result, $org_post_thumbnail );
		}
		$result = get_post( $result );
		$result = self::populate_original_post_id( $result );
		return self::populate_thumbnail( $result );
	}

	public static function create_featured_item( \WP_REST_Request $request ) {
		// Get request body as JSON Object
		$data = json_decode( $request->get_body() );

		// Populate result with featured content
		$result = array();

		// If request contains more than one post loop through
		if ( isset( $data->settings ) && is_array( $data->settings ) ) {
			foreach ( $data->settings as $post_data ) {
				$result[] = self::create_featured_content( $post_data );
			}
			return new \WP_REST_Response( $result, 200 );
		} else {
			$result[] = self::create_featured_content( $data->obj );
			return new \WP_REST_Response( $result, 200 );
		}

		// If something goes wrong return response error
		return new \WP_REST_Response( 'ERROR', 200 );
	}

	private function update_featured_item( \WP_REST_Request $request ) {
		$fields = Featured_Content::get_fields();
		$post_id = intval( $request['id'] );
		$post_parent = intval( $request['post_parent'] );
		$menu_order = intval( $request['menu_order'] );
		$thumbnail = intval( $request['thumbnail'] );
		$post = array(
			'ID' => $post_id,
			'post_parent'   => $post_parent,
			'menu_order' => $menu_order,
			'post_status' => 'draft',
		);

		foreach ( $fields as $field ) {
			$post[ $field['name'] ] = $request[ $field['name'] ];
		}

		$result = wp_update_post( $post );

		if ( null !== $thumbnail ) {
			set_post_thumbnail( $result, $thumbnail );
		}
		$result = get_post( $result );
		$result = self::populate_original_post_id( $result );
		$result = self::populate_thumbnail( $result );

		if ( $result ) {
			return new \WP_REST_Response( $result, 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}

	public static function delete_featured_item( \WP_REST_Request $request ) {
		$post_id = intval( $request['id'] );
		$result = wp_delete_post( $post_id, true );

		if ( $result ) {
			return new \WP_REST_Response( get_post( $result ), 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}

	public static function save_settings( \WP_REST_Request $request ) {
		$featured_items = json_decode( $request->get_body() );

		if ( empty( $featured_items ) ) {
			return new \WP_REST_Response( 'OK', 200 );
		}

		$saved_items = [];
		foreach ( $featured_items as $featured_item ) {
			$result = wp_update_post( $featured_item, true );
			if ( ! is_wp_error( $result ) ) {
				$saved_items[] = $result;
			}
		}

		return new \WP_REST_Response( 'OK', 200 );
	}

	private static function populate_original_post_id( $post ) {
		$original_post_id = get_post_meta( $post->ID, 'original_post_id', true );
		$post->original_post_id = $original_post_id;
		return $post;
	}

	private static function populate_thumbnail( $args ) {
		$result = array();
		if ( is_array( $args ) ) {
			foreach ( $args as $post ) {
				$thumbnail_id = get_post_thumbnail_id( $post->ID );
				$thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'small' );
				$post->thumbnail = $thumbnail_id;
				$post->thumbnail_src = $thumbnail_src[0];
				$result[] = $post;
			}
		} else {
				$thumbnail_id = get_post_thumbnail_id( $args->ID );
				$thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'small' );
				$args->thumbnail = $thumbnail_id;
				$args->thumbnail_src = $thumbnail_src[0];
				$result = $args;
		}
		return $result;
	}
}
