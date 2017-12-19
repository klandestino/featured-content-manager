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
					'get_posts',
				),
			),
		) );

		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array(
					'Featured_Content_Manager\Rest',
					'get_featured_items',
				),
			),
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
				'methods'         => \WP_REST_Server::EDITABLE,
				'callback'        => array( 'Featured_Content_Manager\Rest', 'update_featured_item' ),
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
	}

	public static function check_user_permission() {
		return current_user_can( 'edit_posts' );
	}

	public static function get_posts( \WP_REST_Request $request ) {
		$search_term = (isset( $request['s'] ) ? $request['s'] : '');
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => 10,
			'order' => 'ASC',
			'post_status' => 'publish',
			's' => $search_term,
		);

		$post_query = new \WP_Query( $args );
		$posts = $post_query->posts;
		return new \WP_REST_Response( $posts, 200 );
	}

	public static function get_featured_items( \WP_REST_Request $request ) {
		$post_status = (isset( $request['post_status'] ) ? $request['post_status'] : 'publish');

		$args = array(
			'post_type' => 'featured-content',
			'posts_per_page' => 0,
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'post_parent' => 0,
			'post_status' => $post_status,
			'suppress_filters' => false,
		);

		$featured_item_query = new \WP_Query( $args );
		$posts = $featured_item_query->posts;
		$posts = self::populate_thumbnail( $posts );

		foreach ( $posts as $post ) {
			$children_args = array(
				'post_type' => 'featured-content',
				'posts_per_page' => 0,
				'order' => 'ASC',
				'orderby' => 'menu_order',
				'post_parent' => $post->ID,
				'post_status' => $post_status,
				'suppress_filters' => false,
			);

			$featured_item_children_query = new \WP_Query( $children_args );
			$post->children = $featured_item_children_query->posts;
			$post->children = self::populate_thumbnail( $post->children );
		}
		return new \WP_REST_Response( $posts, 200 );
	}

	public function copy_post_to_featured_content( $post, $post_status, $post_parent = 0 ) {
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

		// If orgininal post has thumbnail, set same thumbnail for featured item
		$org_post_thumbnail = get_post_thumbnail_id( $post->ID );
		if ( $org_post_thumbnail ) {
			set_post_thumbnail( $result, $org_post_thumbnail );
		}
		return self::populate_thumbnail( get_post( $result ) );
	}

	private function create_featured_content( $post_data ) {
		$post = get_post( $post_data->ID );

		// If featured content already exist make sure its a draft and return it
		// Else make a copy of the original post and return the copy
		if ( 'featured-content' === get_post_type( $post ) ) {
			return self::draft_featured_content( $post );
		} else {
			return self::copy_post_to_featured_content( $post, 'draft' );
		}
	}

	private function draft_featured_content( $post ) {
		$result = wp_update_post( array(
			'ID' => intval( $post->ID ),
			'post_status' => 'draft',
		) );
		if ( ! is_wp_error( $result ) ) {
			$result = get_post( $result );
			return self::populate_thumbnail( get_post( $result ) );
		}
	}

	public function create_featured_item( \WP_REST_Request $request ) {
		// Get request body as JSON Object
		$data = json_decode( $request->get_body() );

		// Populate result with featured content
		$result = array();

		// If request contains more than one post loop through
		if ( is_array( $data->settings ) ) {
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

	public function update_featured_item( \WP_REST_Request $request ) {
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

		$result = self::populate_thumbnail( get_post( $result ) );

		if ( $result ) {
			return new \WP_REST_Response( $result, 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}

	public function delete_featured_item( \WP_REST_Request $request ) {
		$post_id = intval( $request['id'] );
		$result = wp_delete_post( $post_id, true );

		if ( $result ) {
			return new \WP_REST_Response( get_post( $result ), 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}

	private function populate_thumbnail( $args ) {
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
