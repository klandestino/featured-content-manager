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

		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods'         => \WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_featured_items' )
			),
			array(
				'methods'         => \WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_featured_item' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			),
		) );

		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
			array(
				'methods'         => \WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_featured_item' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			),
		) );
	}

	public static function check_user_permission(){
		return current_user_can( 'edit_posts' );
	}

	public static function get_featured_items() {
		$posts = get_posts( array(
			'post_type' => 'featured-content',
			'posts_per_page' => 5,
			'order' => 'ASC',
			'orderby' => 'menu_order',
		) );
		return new \WP_REST_Response( $posts, 200 );
	}

	public function create_featured_item(\WP_REST_Request $request) {
		$author = wp_get_current_user();

		$post = array(
			'post_title' => 'Nytt inlägg',
			'post_content' => 'Innehåll',
			'post_status' => 'publish',
			'post_author' => $author->ID,
			'post_type' => 'featured-content',
		);
		$result = wp_insert_post( $post );

		if ( $result ) {
			return new \WP_REST_Response( get_post( $result ), 200 );
		}

		return new \WP_REST_Response( 'ERROR', 500 );
	}

	public function update_featured_item(\WP_REST_Request $request) {
		$post_id = intval( $request['id'] );
		$post_parent = intval( $request['post_parent'] );
		$menu_order = intval( $request['menu_order'] );

		$post = array(
			'ID' => $post_id,
			'post_parent'   => $post_parent,
			'menu_order' => $menu_order,
		);
		$result = wp_update_post( $post );

		if ( $result ) {
			return new \WP_REST_Response( get_post( $result ), 200 );
		}
		return new \WP_REST_Response( 'ERROR', 500 );
	}
}