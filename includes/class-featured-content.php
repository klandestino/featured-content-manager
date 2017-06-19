<?php
/**
 * Class Featured Content Manager.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

class Featured_Content {

	public static function register() {

		register_post_type( 'featured-content', array(
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'rest_base' => 'featured-content',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'taxonomies' => array( 'featured-area' ),
			'supports' => array( 'title', 'thumbnail', 'excerpt' ),
			'labels' => array(
				'name' => _x( 'Featured Items', 'post type general name', 'featured-content-manager' ),
				'singular_name' => _x( 'Featured Item', 'post type singular name', 'featured-content-manager' ),
				'add_new' => _x( 'Add New', 'Featured Item' ),
				'add_new_item' => __( 'Add New Featured Item', 'featured-content-manager' ),
				'edit_item' => __( 'Edit Featured Item', 'featured-content-manager' ),
				'new_item' => __( 'New Featured Item', 'featured-content-manager' ),
				'view_item' => __( 'View Featured Item', 'featured-content-manager' ),
				'search_items' => __( 'Search Featured Items', 'featured-content-manager' ),
				'not_found' => __( 'No Featured Items found', 'featured-content-manager' ),
				'not_found_in_trash' => __( 'No Featured Items found in Trash', 'featured-content-manager' ),
			),
		));
	}
}
