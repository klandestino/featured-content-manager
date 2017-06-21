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
			'sanitize_callback' => 'sanitize_text_field'
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
					'section'	=> 'featured_area'
				)
			)
		);
	}

	public static function customize_print_template() {
	?>
		<script type="text/html" id="tmpl-featured-item">
			<li id="featured_item_{{data.ID}}">
				<div class="handle">
					{{data.post_title}}
					<button type="button" class="button-link featured-item-edit" aria-expanded="false">
						<span class="screen-reader-text">Edit menu item: Frontpage (Page)</span><span class="toggle-indicator" aria-hidden="true"></span>
					</button>
				</div>
				<div class="featured-item-settings">
					Inställningar.
				</div>
				<# if ( data.children.length >= 1 ) { #>
					<ol>
					<# for (i = 0; i < data.children.length; i++) { #>
						<li id="featured_item_{{data.children[i].ID}}">
							<div class="handle">
								{{data.children[i].post_title}}
								<button type="button" class="button-link featured-item-edit" aria-expanded="false">
									<span class="screen-reader-text">Edit menu item: Frontpage (Page)</span><span class="toggle-indicator" aria-hidden="true"></span>
								</button>
							</div>
							<div class="featured-item-settings">
								Inställningar.
							</div>
						</li>
					<# } #>
					<ol>
				<# } #>
			</li>
		</script>
	<?php
	}
}
