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

spl_autoload_register( function( $class_name ) {
	if ( 0 !== strpos( $class_name, __NAMESPACE__ . '\\' ) ) {
		return;
	}
	$class_name = substr( $class_name, strlen( __NAMESPACE__ ) + 1 );
	$filename = 'class-' . str_replace( array( '\\', '_' ), array( '/', '-' ), strtolower( $class_name ) ) . '.php';
	require_once dirname( __FILE__ ) . '/includes/' . $filename;
} );

require_once dirname( __FILE__ ) . '/includes/class-featured-query.php';

add_action( 'init', function() {
	if ( current_theme_supports( 'featured-content-manager' ) ) {
		add_action( 'init', array( 'Featured_Content_Manager\Featured_Content', 'register' ) );
		add_action( 'customize_register', array( 'Featured_Content_Manager\Customizer', 'customize_register' ) );
		add_action( 'customize_controls_enqueue_scripts', array( 'Featured_Content_Manager\Customizer', 'enqueue_customize_control' ) );
		add_action( 'customize_controls_print_footer_scripts', array( 'Featured_Content_Manager\Customizer', 'customize_print_featured_item_template' ) );
		add_action( 'customize_controls_print_footer_scripts', array( 'Featured_Content_Manager\Customizer', 'customize_print_search_result_item_template' ) );
		add_action( 'customize_controls_print_footer_scripts', array( 'Featured_Content_Manager\Customizer', 'customize_print_accordion' ) );
		add_action( 'customize_save_after', array( 'Featured_Content_Manager\Customizer', 'customize_save_customizer' ), 10, 1 );
		add_action( 'rest_api_init', array( 'Featured_Content_Manager\Rest', 'register_routes' ) );
		add_action( 'customize_controls_init', array( 'Featured_Content_Manager\Customizer', 'customize_init_customizer' ) );
		add_action( 'query_featured_content', array( 'Featured_Content_Manager\Featured_Content', 'query_featured_content' ) );
	}
}, 1 );
