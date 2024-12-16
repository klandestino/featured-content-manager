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
						'title'    => esc_html( $featured_area['title'] ),
						'priority' => 1,
						'panel'    => 'featured_content_panel',
					)
				);

				$wp_customize->add_control(
					new Featured_Area_Control(
						$wp_customize,
						$featured_area_slug,
						array(
							'label'          => esc_html__( 'Featured Area', 'featured-content-manager' ),
							'section'        => $featured_area_slug,
							'max'            => $featured_area['max'] ?? null,
							'levels'         => $featured_area['levels'] ?? null,
							'object_type'    => $featured_area['object_type'] ?? 'post',
							'object_subtype' => $featured_area['object_subtype'] ?? [ 'post' ],
							'fsettings'      => $featured_area['settings'] ?? [],
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
			'sortable-js',
			plugins_url( 'dist/js/Sortable.min.js', dirname( __FILE__ ) ),
			array(),
			filemtime( dirname( __DIR__, 1 ) . '/dist/js/Sortable.min.js' ),
			true
		);
		wp_register_script(
			'featured-area-script',
			plugins_url( 'dist/js/customizer.js', dirname( __FILE__ ) ),
			array( 'customize-controls', 'sortable-js', 'wp-i18n' ),
			filemtime( dirname( __DIR__, 1 ) . '/dist/js/customizer.js' ),
			true
		);
		wp_set_script_translations(
			'featured-area-script',
			'featured-content-manager',
			dirname( __DIR__, 1 ) . '/languages'
		);
		wp_localize_script(
			'featured-area-script',
			'wpFeaturedContentApiSettings',
			array(
				'base'       => 'featured-content-manager/v1/',
				'go_to_urls' => self::get_go_to_urls(),
			)
		);
		wp_enqueue_script( 'featured-area-script' );
	}

	/**
	 * Function that prints out the setting accordion markup.
	 */
	public static function customize_print_accordion() {
		?>
			<div id="featured-items-search-panel" class="featured-item-container">
				<div class="customize-section-title">
					<button type="button" class="customize-section-back" tabindex="-1">
						<span class="screen-reader-text">Back</span>
					</button>
					<h3>
						<span class="customize-action"><?php esc_html_e( 'Customizing ▸ Featured Area', 'featured-content-manager' ); ?></span>
						<?php esc_html_e( 'Add Featured Items', 'featured-content-manager' ); ?>
					</h3>
				</div>
				<div id="featured-items-search-title">
					<div class="search-icon" aria-hidden="true"></div>
					<label class="screen-reader-text" for="featured-items-search"><?php esc_html_e( 'Search Featured Items', 'featured-content-manager' ); ?></label>
					<input type="text" id="featured-items-search-input" placeholder="<?php esc_html_e( 'Search Featured Items', 'featured-content-manager' ); ?>" aria-describedby="featured-items-search-desc" />
					<p class="screen-reader-text" id="featured-items-search-desc"><?php esc_html_e( 'The search results will be updated as you type.', 'featured-content-manager' ); ?></p>
					<span class="spinner"></span>
				</div>
				<ol id="featured-items-search-list" class="accordion-section-content">
					<li class="nothing-found"><?php echo esc_html_e( 'No results found.', 'featured-content-manager' ); ?></li>
				</ol>
			</div>
		<?php
	}

	/**
	 * Function that prints WP Template markup for an item.
	 */
	public static function customize_print_featured_item_template() {
		?>
		<script type="text/html" id="tmpl-featured-item">
			<# if ( data.title ) { #>
				<li data-id="{{data.id}}" data-title="{{data.title}}" data-type="{{data.type}}" data-subtype="{{data.subtype}}" data-subtype_label="{{data.subtype_label}}" class="featured-item-tpl">
					<div class="handle">
						<# if ( data.meta && data.meta.post_status && 'future' == data.meta.post_status	) { #>
							<span class="dashicons dashicons-clock"></span>
						<# } #>
						<span class="featured-item-title">
							{{data.title}}
						</span>
						<span class="featured-item-controls">
							<span class="featured-item-type" aria-hidden="true">{{data.subtype_label}}</span>
							<button type="button" class="button-link button-link-delete featured-item-delete">
								<span class="screen-reader-text">
									Remove Featured Item: {{data.title}} ({{data.subtype_label}})
								</span>
							</button>
							<button type="button" class="button-link button-link-add featured-item-add">
								<span class="screen-reader-text">
									Add Featured Item: {{data.title}} ({{data.subtype_label}})
								</span>
							</button>
						</span>
					</div>
					<div class="settings"></div>
					<ol class="nested-sortable"></ol>
				</li>
			<# } #>
		</script>
		<?php
	}

	/**
	 * Print some *hack* css style.
	 */
	public static function customizer_css() {
		$featured_areas = Featured_Content::get_featured_areas();
		?>
		<style type="text/css">
		<?php
		foreach ( $featured_areas as $id => $featured_area ) {
			$levels    = $featured_area['levels'] ?? 0;
			$max       = isset( $featured_area['max'] ) ? $featured_area['max'] + 1 : 11; // Setting this to +1 to get the :before css at the right place.
			$level_css = '';
			for ( $i = 1; $i < $levels; $i++ ) {
				$level_css .= '>li>ol';
				echo "ol#{$id}{$level_css} { display: block; }\n";
			}

			?>
			ol#<?php echo esc_attr( $id ); ?> li:nth-child(<?php echo esc_attr( $max ); ?>)::before {
				content: "";
				border-bottom: 1px dashed lightgray;
				margin: 10px 0;
				display: block;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}
			<?php
		}
		?>
		</style>
		<?php
	}

	/**
	 * If an area has registered a url to go to when it's opened,
	 * add it here.
	 */
	public static function get_go_to_urls() {
		$featured_areas = Featured_Content::get_featured_areas();
		$go_to_urls     = [];
		foreach ( $featured_areas as $id => $featured_area ) {
			if ( ! empty( $featured_area['go_to_url'] ) ) {
				$go_to_urls[ $id ] = $featured_area['go_to_url'];
			}
		}
		return $go_to_urls;
	}
}
