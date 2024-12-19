<?php
/**
 * Plugin Name:     Featured Content Manager
 * Plugin URI:      https://github.com/redundans/featured-content-manager
 * Description:     Lets users create featured items that mirrors order posts and edit them inside featured areas.
 * Author:          Jesper Nilsson <jesper@klandestino.se>
 * Author URI:      http://www.klandestino.se
 * Text Domain:     featured-content-manager
 * Domain Path:     /languages
 * Version:         0.1.0
 * License:         GPL v3
 *
 * @package         Featured Content Manager
 */

namespace Featured_Content_Manager;

use stdClass;

/**
 * Autoloading classes.
 *
 * @param string $class_name Every class name.
 */
spl_autoload_register(
	function( $class_name ) {
		if ( 0 !== strpos( $class_name, __NAMESPACE__ . '\\' ) ) {
			return;
		}
		$class_name = substr( $class_name, strlen( __NAMESPACE__ ) + 1 );
		$filename   = 'class-' . str_replace( array( '\\', '_' ), array( '/', '-' ), strtolower( $class_name ) ) . '.php';
		require_once dirname( __FILE__ ) . '/includes/' . $filename;
	}
);

add_action(
	'init',
	function() {
		if ( current_theme_supports( 'featured-content-manager' ) ) {
			add_action( 'customize_controls_enqueue_scripts', array( 'Featured_Content_Manager\Customizer', 'customizer_css' ) );
			add_action( 'customize_register', array( 'Featured_Content_Manager\Customizer', 'customize_register' ) );
			add_action( 'customize_controls_enqueue_scripts', array( 'Featured_Content_Manager\Customizer', 'enqueue_customize_control' ) );
			add_action( 'customize_controls_print_footer_scripts', array( 'Featured_Content_Manager\Customizer', 'customize_print_featured_item_template' ) );
			add_action( 'customize_controls_print_footer_scripts', array( 'Featured_Content_Manager\Customizer', 'customize_print_accordion' ) );
			add_action( 'rest_api_init', array( 'Featured_Content_Manager\Rest', 'register_routes' ) );

			foreach ( array( 'post', 'term' ) as $object_type ) {
				add_filter( "fcm_{$object_type}_search", array( 'Featured_Content_Manager\Rest', "fcm_{$object_type}_search" ), 10, 1 );
				add_filter( "fcm_{$object_type}_filter_result", array( 'Featured_Content_Manager\Rest', "fcm_{$object_type}_filter_result" ), 10, 1 );
			}

			foreach ( Featured_Content::get_featured_areas() as $slug => $area ) {
				add_filter(
					"customize_sanitize_js_{$slug}",
					function( $value ) {
						$value = json_decode( $value );
						$ids   = array_column( $value, 'id' );
						_prime_post_caches( $ids, false, false );
						foreach ( $value as $key => $item ) {
							// Backwards compatibilty. Map old values from options to new.
							if ( ! isset( $item->title ) ) {
								$new_item                = new \stdclass();
								$new_item->id            = $item->original_post_id ?? $item->id;
								$new_item->title         = $item->post_title;
								$new_item->type          = 'post';
								$new_item->subtype       = 'post';
								$new_item->subtype_label = 'Artikel';
								if ( isset( $item->fcm_select_style ) ) {
									$new_item->fcm_select_style = $item->fcm_select_style;
								}
								$value[ $key ] = $new_item;
							}
							// Update title and post_status from original post.
							$original_post = get_post( $value[ $key ]->id );
							if ( $original_post ) {
								$value[ $key ]->title = esc_attr( $original_post->post_title );
								if ( ! isset( $value[ $key ]->meta ) ) {
									$value[ $key ]->meta = new \stdClass();
								}
								$value[ $key ]->meta->post_status = esc_attr( $original_post->post_status );
							}
						}
						$value = wp_json_encode( $value );
						return $value;
					}
				);
			}
		}
	},
	1
);
