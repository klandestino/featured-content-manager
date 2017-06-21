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

		$wp_customize->register_control_type(  'Featured_Content_Manager\Featured_Area_Control' );
		
		$wp_customize->add_setting( 'featured_area' , array(
			'default'   => '#000000',
			'transport' => 'refresh',
		) );

		$wp_customize->add_section( 'featured_area' , array(
			'title'      => __( 'Featured Content Manager', 'featured-content-manager' ),
			'priority'   => 1,
		) );

		// Registers example_background control
		$wp_customize->add_control(
			new Featured_Area_Control(
				$wp_customize,
				'featured_area',
				array(
					'label'		=> esc_html__( 'Example Background', 'customizer-background-control' ),
					'section'	=> 'featured_area',
					// Tie a setting (defined via `$wp_customize->add_setting()`) to the control.
					'settings'    => array(
						'image_url' => 'example_background_image_url',
						'image_id' => 'example_background_image_id',
						'repeat' => 'example_background_repeat', // Use false to hide the field
						'size' => 'example_background_size',
						'position' => 'example_background_position',
						'attach' => 'example_background_attach'
					)
				)
			)
		);
	}

	public static function customize_print_template() {
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
