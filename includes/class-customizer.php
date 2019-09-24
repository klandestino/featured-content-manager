<?php
/**
 * Featured Content Manager Customizer.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

/**
 * The customizer class handling all the customizer functionality.
 */
class Customizer {

	/**
	 * Register panels and settings.
	 *
	 * @param WP_Customize_Manager $wp_customize A customizer class.
	 */
	public static function customize_register( $wp_customize ) {
		$featured_areas = Featured_Content::get_featured_areas();

		$wp_customize->register_control_type( 'Featured_Content_Manager\Featured_Area_Control' );

		if ( $featured_areas ) {

			$wp_customize->add_panel(
				'featured_content_panel',
				array(
					'title'       => __( 'Featured Content Manager', 'featured-content-manager' ),
					'description' => 'This is a description of this panel',
					'priority'    => 10,
				)
			);

			foreach ( $featured_areas as $featured_area_slug => $featured_area ) {

				$wp_customize->add_setting(
					$featured_area_slug,
					array(
						'default'           => '[]',
						'sanitize_callback' => 'sanitize_text_field',
						'transport'         => 'postMessage',
					)
				);

				$wp_customize->add_section(
					$featured_area_slug,
					array(
						'title'    => esc_html( $featured_area ),
						'priority' => 1,
						'panel'    => 'featured_content_panel',
					)
				);

				$wp_customize->add_control(
					new Featured_Area_Control(
						$wp_customize,
						$featured_area_slug,
						array(
							'label'   => esc_html__( 'Featured Area', 'customizer-background-control' ),
							'section' => $featured_area_slug,
						)
					)
				);
			}
		}
	}

	/**
	 * Enque customizer scripts for Featured Content.
	 */
	public static function enqueue_customize_control() {
		$fields = Featured_Content::get_fields();
		wp_enqueue_media();
		wp_enqueue_style(
			'featured-area-style',
			plugins_url( 'dist/css/customizer.css', dirname( __FILE__ ) ),
			array(),
			filemtime( dirname( __DIR__, 1 ) . '/dist/css/customizer.css' ),
			'screen'
		);
		wp_enqueue_script(
			'whatwg-fetch-script',
			plugins_url( 'dist/js/fetch.js', dirname( __FILE__ ) ),
			array(),
			filemtime( dirname( __DIR__, 1 ) . '/dist/js/fetch.js' ),
			true
		);
		wp_enqueue_script(
			'nested-sortable',
			plugins_url( 'dist/js/jquery.mjs.nestedSortable.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			filemtime( dirname( __DIR__, 1 ) . '/dist/js/jquery.mjs.nestedSortable.js' ),
			true
		);
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_register_script(
				'featured-area-script',
				plugins_url( 'dist/js/customizer.js', dirname( __FILE__ ) ),
				array( 'jquery', 'customize-controls', 'nested-sortable', 'wp-i18n' ),
				filemtime( dirname( __DIR__, 1 ) . '/dist/js/customizer.js' ),
				true
			);
			wp_set_script_translations(
				'featured-area-script',
				'featured-content-manager',
				dirname( __DIR__, 1 ) . '/languages'
			);
		} else {
			wp_register_script(
				'featured-area-script',
				plugins_url( 'dist/js/customizer.min.js', dirname( __FILE__ ) ),
				array( 'jquery', 'customize-controls', 'nested-sortable', 'wp-i18n' ),
				filemtime( dirname( __DIR__, 1 ) . '/dist/js/customizer.min.js' ),
				true
			);
			wp_set_script_translations(
				'featured-area-script',
				'featured-content-manager',
				dirname( __DIR__, 1 ) . '/languages'
			);
		}
		wp_localize_script(
			'featured-area-script',
			'wpFeaturedContentApiSettings',
			array(
				'base'   => 'featured-content-manager/v1/',
				'fields' => wp_json_encode( $fields ),
			)
		);
		wp_enqueue_script( 'featured-area-script' );
	}

	/**
	 * Function that prints out the setting accordion markup.
	 */
	public static function customize_print_accordion() {
		?>
			<div id="available-featured-items" class="accordion-container">
				<div class="accordion-section-title">
					<div class="search-icon" aria-hidden="true"></div>
					<label class="screen-reader-text" for="featured-items-search"><?php echo esc_html( __( 'Search Featured Items', 'featured-content-manager' ) ); ?></label>
					<input type="text" id="featured-items-search" placeholder="<?php echo esc_html( __( 'Search Featured Items', 'featured-content-manager' ) ); ?>" aria-describedby="featured-items-search-desc" />
					<p class="screen-reader-text" id="featured-items-search-desc"><?php echo esc_html( __( 'The search results will be updated as you type.', 'featured-content-manager' ) ); ?></p>
					<span class="spinner"></span>
					<div class="search-icon" aria-hidden="true"></div>
				</div>
				<ul id="available-featured-items-list" class="accordion-section-content">
					<li class="nothing-found"><?php echo esc_html( __( 'No results found.', 'featured-content-manager' ) ); ?></li>
				</ul>
			</div>
		<?php
	}

	/**
	 * Function that prints WP Template markup for an item.
	 */
	public static function customize_print_featured_item_template() {
		$fields = Featured_Content::get_fields();
		?>
		<script type="text/html" id="tmpl-featured-item">
			<# if ( data.post_title ) { #>
			<div class="handle">
				<span class="featured-item-title">
					{{data.post_title}}
				</span>
				<button type="button" class="button-link featured-item-edit" aria-expanded="false">
					<span class="screen-reader-text"><?php echo esc_html( __( 'Edit featured item', 'featured-content-manager' ) ); ?>: {{data.post_title}}</span><span class="toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
			<div class="featured-item-settings">
				<form>
					<?php
					if ( $fields ) :
						foreach ( $fields as $field ) :
							self::render_input( $field, 'data' );
						endforeach;
					endif;
					?>
					<div class="featured-item-actions">
						<button type="button" class="button-link button-link-delete item-delete"><?php echo esc_html( __( 'Remove', 'featured-content-manager' ) ); ?></button>
						<span class="spinner"></span>
					</div>
				</form>
			</div>
			<ol></ol>
			<# } #>
		</script>
		<?php
	}

	/**
	 * Function that prints WP Template markup for an search result item.
	 */
	public static function customize_print_search_result_item_template() {
		?>
		<script type="text/html" id="tmpl-search-item">
				<div class="search-item-bar {{data.post_status}}">
					<div class="search-item-handle">
						<span class="search-time" aria-hidden="true">{{data.human_time_diff}}</span>
						<span class="search-title" aria-hidden="true">
							<span class="search-item-title">{{data.post_title}}</span>
						</span>
						<button type="button" class="button-link item-add">
							<span class="screen-reader-text"><?php echo esc_html( __( 'Add to featured area', 'featured-content-manager' ) ); ?>: {{data.post_title}} ({{data.post_type}})</span>
						</button>
					</div>
				</div>
		</script>
		<?php
	}

	/**
	 * Function that prints markup for setting inputs.
	 *
	 * @param array  $field A field setting array.
	 * @param string $sign A sign string.
	 */
	public static function render_input( $field, $sign ) {
		switch ( $field['type'] ) {
			case 'textarea':
				?>
				<p>
					<label>
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<textarea rows="4" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-input">{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}</textarea>
					</label>
				</p>
				<?php
				break;
			case 'media':
				?>
				<p>
					<label>
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<div class="featured-item-image-field-wrapper">
							<div class="featured-item-image-field-container">
							<# if ( <?php echo esc_attr( $sign ); ?>.<?php echo esc_attr( $field['name'] ); ?> ) { #>
								<img src="{{<?php echo esc_attr( $sign ); ?>.<?php echo esc_attr( $field['name'] . '_src' ); ?>}}" alt="" />
								<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>" class="featured-item-edit-hidden" value="{{<?php echo esc_attr( $sign ); ?>.<?php echo esc_attr( $field['name'] ); ?>}}">
								<a class="featured-item-image-field-upload" style="display: none;" href="#">Välj bild</a>
								<a class="featured-item-image-field-remove" href="#">Ta bort</a>
							<# } else { #>
								<img src="#" style="display: none;" />
								<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>" class="featured-item-edit-hidden" value="">
								<a class="featured-item-image-field-upload" href="#">Välj bild</a>
								<a class="featured-item-image-field-remove" href="#" style="display: none;" >Ta bort</a>
							<# } #>
							</div>
						</div>
					</label>
				</p>
				<?php
				break;
			case 'select':
				?>
				<p>
					<label>
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<select name="fcm_select_<?php echo esc_attr( $field['name'] ); ?>">
						<?php foreach ( $field['values'] as $name => $value ) { ?>
							<option name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $name ); ?>" <# if ( <?php echo esc_attr( $sign ); ?>.fcm_select_<?php echo esc_html( $field['name'] ); ?>=='<?php echo esc_attr( $name ); ?>' ) { #>selected<# } #>><?php echo esc_html( $value ); ?></option>
						<?php } ?>
						</select>
					</label>
				</p>
				<?php
				break;
			default:
				?>
				<p>
					<label>
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" class="featured-item-edit-input" value="{{<?php echo esc_attr( $sign ); ?>.<?php echo esc_attr( $field['name'] ); ?>}}"/>
					</label>
				</p>
				<?php
				break;
		}
	}

	/**
	 * A functions that prints the user color scheme as CSS in header.
	 */
	public static function customizer_colors() {
		global $_wp_admin_css_colors;

		$color_scheme = get_user_option( 'admin_color' );

		// It's possible to have a color scheme set that is no longer registered.
		if ( empty( $_wp_admin_css_colors[ $color_scheme ] ) ) {
			$color_scheme = 'fresh';
		}

		if ( ! empty( $_wp_admin_css_colors[ $color_scheme ] ) ) {
			$text_color = $_wp_admin_css_colors[ $color_scheme ]->colors[2];
		} else {
			$text_color = '#222';
		}
		echo '<style>
		ol.featured-area li.future > .handle,
		#available-featured-items .accordion-section-content .search-item-tpl .future .search-item-handle,
		#available-featured-items .accordion-section-content .search-item-tpl .future .search-item-handle .search-time,
		#available-featured-items .accordion-section-content .search-item-tpl .future .search-item-handle .item-add {
			color: ' . esc_html( $text_color ) . ' !important;
		} 
		</style>';
	}
}
