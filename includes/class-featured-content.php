<?php
/**
 * Class Featured Content Manager.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

class Featured_Content {
	public function get_fields() {
		$fields = [];
		$args = get_theme_support( 'featured-content-manager' )[0];
		if ( $args['fields'] ) {
			foreach ( $args['fields'] as $field ) {
				switch ( $field ) {
					case 'post_title':
						$fields['post_title']['name'] = 'post_title';
						$fields['post_title']['display_name'] = 'Post title';
						$fields['post_title']['type'] = 'input';
						break;
					case 'post_content':
						$fields['post_content']['name'] = 'post_content';
						$fields['post_content']['display_name'] = 'Post Content';
						$fields['post_content']['type'] = 'textarea';
						break;
					case 'thumbnail':
						$fields['thumbnail']['name'] = 'thumbnail';
						$fields['thumbnail']['display_name'] = 'Thumbnail';
						$fields['thumbnail']['type'] = 'media';
						break;
				}
			}
		}
		return $fields;
	}

	public function get_areas() {
		$areas = [];
		$args = get_theme_support( 'featured-content-manager' )[0];
		if ( $args['featured_areas'] ) {
			foreach ( $args['featured_areas'] as $area ) {
				$areas[] = $area;
			}
		}
		return $areas;
	}

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

		register_taxonomy(
			'featured-area',
			'featured-content',
			array(
				'label' => __( 'Featured Area', 'featured-content-manager' ),
				'rewrite' => array(
					'slug' => 'featured-area',
				),
			)
		);
	}
}
