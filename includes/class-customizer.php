<?php
/**
 * Featured Content Manager Customizer.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

class Customizer {
	public static function customize_register( $wp_customize ) {

		$wp_customize->add_setting( 'featured_area' , array(
			'default'   => '#000000',
			'transport' => 'refresh',
		) );

		$wp_customize->add_section( 'featured_area' , array(
			'title'      => __( 'Featured Content Manager', 'featured-content-manager' ),
			'priority'   => 1,
		) );

		$wp_customize->add_control( new Featured_Area_Control( $wp_customize, 'featured_area_1', array(
			'label'      => __( 'Featured area 1', 'featured-content-manager' ),
			'section'    => 'featured_area',
			'settings'   => 'featured_area',
		) ) );
	}

	public static function enqueue_admin_scripts() {
		wp_enqueue_script( 'nested-sortable', plugins_url( 'dist/js/jquery.mjs.nestedSortable.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_enqueue_script( 'featured-area', plugins_url( 'dist/js/customizer-debug.js', dirname( __FILE__ ) ), array( 'jquery', 'nested-sortable' ) );
		wp_localize_script( 'featured-area', 'wpApiSettings', array(
			'root' => esc_url_raw( '/wp-json/featured-content-manager/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		) );
	}

	public static function template() {
	?>
		<script type="text/html" id="tmpl-featured-item">
			<li id="featured_item_{{data.ID}}">
				<div>{{data.post_title}}</div>
				<# if ( data.children.length >= 1 ) { #>
					<ol>
					<# for (i = 0; i < data.children.length; i++) { #>
						<li id="featured_item_{{data.children[i].ID}}">
							<div>{{data.children[i].post_title}}</div>
						</li>
					<# } #>
					<ol>
				<# } #>
			</li>
		</script>
	<?php
	}
}
