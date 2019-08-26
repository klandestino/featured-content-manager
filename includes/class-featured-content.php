<?php
/**
 * Class Featured Content Manager.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

/**
 * Class for registering the post types, taxonomies and theme setup.
 */
class Featured_Content {
	/**
	 * Getting fields for this theme setup.
	 */
	public static function get_fields() {
		$fields = [];
		$args   = get_theme_support( 'featured-content-manager' )[0];
		if ( isset( $args['fields'] ) ) {
			foreach ( $args['fields'] as $key => $field ) {
				switch ( $field ) {
					case 'post_title':
						$fields['post_title']['name']         = 'post_title';
						$fields['post_title']['display_name'] = 'Post title';
						$fields['post_title']['type']         = 'input';
						break;
					case 'post_content':
						$fields['post_content']['name']         = 'post_content';
						$fields['post_content']['display_name'] = 'Post Content';
						$fields['post_content']['type']         = 'textarea';
						break;
					case 'post_excerpt':
						$fields['post_excerpt']['name']         = 'post_excerpt';
						$fields['post_excerpt']['display_name'] = 'Post excerpt';
						$fields['post_excerpt']['type']         = 'textarea';
						break;
					case 'thumbnail':
						$fields['thumbnail']['name']         = 'thumbnail';
						$fields['thumbnail']['display_name'] = 'Thumbnail';
						$fields['thumbnail']['type']         = 'media';
						break;
				}
				if ( 'taxonomy' === $key ) {
					$taxonomy = get_taxonomy( $field );
					if ( $taxonomy ) {
						$terms                                     = get_terms(
							$taxonomy->name,
							array(
								'hide_empty' => false,
								'orderby'    => 'term_id',
								'order'      => 'ASC',
							)
						);
						$fields[ $taxonomy->name ]['name']         = $taxonomy->name;
						$fields[ $taxonomy->name ]['display_name'] = $taxonomy->label;
						$fields[ $taxonomy->name ]['type']         = 'taxonomy';
						$fields[ $taxonomy->name ]['terms']        = $terms;
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * Get all the registred areas.
	 */
	public static function get_areas() {
		$areas = [];
		$args  = get_theme_support( 'featured-content-manager' )[0];
		if ( isset( $args['featured_areas'] ) ) {
			foreach ( $args['featured_areas'] as $area ) {
				$areas[] = $area;
			}
		}
		return $areas;
	}

	/**
	 * Register post type and taxonomies.
	 */
	public static function register() {
		register_post_type(
			'featured-content',
			array(
				'public'                => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
				'show_ui'               => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
				'show_in_menu'          => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
				'show_in_rest'          => true,
				'rest_base'             => 'featured-content',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'taxonomies'            => array( 'featured-area' ),
				'supports'              => array( 'title', 'thumbnail', 'excerpt' ),
				'labels'                => array(
					'name'               => _x( 'Featured Items', 'post type general name', 'featured-content-manager' ),
					'singular_name'      => _x( 'Featured Item', 'post type singular name', 'featured-content-manager' ),
					'add_new'            => _x( 'Add New', 'Featured Item' ),
					'add_new_item'       => __( 'Add New Featured Item', 'featured-content-manager' ),
					'edit_item'          => __( 'Edit Featured Item', 'featured-content-manager' ),
					'new_item'           => __( 'New Featured Item', 'featured-content-manager' ),
					'view_item'          => __( 'View Featured Item', 'featured-content-manager' ),
					'search_items'       => __( 'Search Featured Items', 'featured-content-manager' ),
					'not_found'          => __( 'No Featured Items found', 'featured-content-manager' ),
					'not_found_in_trash' => __( 'No Featured Items found in Trash', 'featured-content-manager' ),
				),
			)
		);

		register_taxonomy(
			'featured-area',
			'featured-content',
			array(
				'label'   => __( 'Featured Area', 'featured-content-manager' ),
				'rewrite' => array(
					'slug' => 'featured-area',
				),
			)
		);
	}
}
