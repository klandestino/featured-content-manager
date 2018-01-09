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
		$featured_areas = Featured_Content::get_areas();

		$wp_customize->register_control_type( 'Featured_Content_Manager\Featured_Area_Control' );

		if ( $featured_areas ) {

			$wp_customize->add_panel( 'featured_content_panel', array(
				'title' => __( 'Featured Content Manager', 'featured-content-manager' ),
				'description' => 'This is a description of this panel',
				'priority' => 10,
			) );

			foreach ( $featured_areas as $featured_area ) {
				$featured_area_slug = sanitize_title( $featured_area );

				$wp_customize->add_setting( $featured_area_slug, array(
					'default' => '[]',
					'sanitize_callback' => 'sanitize_text_field',
				) );

				$wp_customize->add_section( $featured_area_slug, array(
					'title' => esc_html( $featured_area ),
					'priority' => 1,
					'panel' => 'featured_content_panel',
				) );

				// Registers example_background control
				$wp_customize->add_control(
					new Featured_Area_Control(
						$wp_customize,
						$featured_area_slug,
						array(
							'label' => esc_html__( 'Featured Area', 'customizer-background-control' ),
							'section' => $featured_area_slug,
						)
					)
				);
			}
		}
	}

	public static function enqueue_customize_control() {
		$fields = Featured_Content::get_fields();
		wp_enqueue_style( 'featured-area-style', plugins_url( 'dist/css/customizer.css', dirname( __FILE__ ) ), array(), '1', 'screen' );
		wp_enqueue_script( 'nested-sortable', plugins_url( 'dist/js/jquery.mjs.nestedSortable.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_register_script( 'featured-area-script', plugins_url( 'dist/js/customizer.min.js', dirname( __FILE__ ) ), array( 'jquery', 'customize-controls', 'nested-sortable' ) );
		wp_localize_script( 'featured-area-script', 'wpFeaturedContentApiSettings', array(
			'base' => 'featured-content-manager/v1/',
			'fields' => wp_json_encode( $fields ),
		) );
		wp_enqueue_script( 'featured-area-script' );
	}

	public function enqueue_customize_preview() {
	}


	public static function customize_print_accordion() {
		?>
			<div id="available-featured-items" class="accordion-container">
				<div class="accordion-section-title">
					<div class="search-icon" aria-hidden="true"></div>
					<label class="screen-reader-text" for="featured-items-search"><?php echo esc_html( __( 'Search Featured Items', 'featured-content-manager' ) ); ?></label>
					<input type="text" id="featured-items-search" placeholder="Search features items…" aria-describedby="featured-items-search-desc" />
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

	public static function customize_print_featured_item_template() {
		$fields = Featured_Content::get_fields();
	?>
		<script type="text/html" id="tmpl-featured-item">
			<# if ( data.post_title ) { #>
			<div class="handle">
				{{data.post_title}} 
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

	public static function customize_print_search_result_item_template() {
	?>
		<script type="text/html" id="tmpl-search-item">
				<div class="search-item-bar">
					<div class="search-item-handle">
						<span class="search-type" aria-hidden="true">{{data.post_type}}</span>
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

	public function render_input( $field, $sign ) {
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
							<div class="featured-item-image-field-container" >
								<# if ( <?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?> ) { #>
									<img src="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] . '_src' ); ?>}}" alt="" />
								<# } #>
							</div>
							<input type="hidden" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-hidden" value="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}"/>
						</div>
					</label>
				</p>
				<?php
				break;
			default:
				?>
				<p>
					<label>
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<input type="text" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-input" value="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}"/>
					</label>
				</p>
				<?php
				break;
		}
	}

	public static function customize_init_customizer() {
		global $wpdb;

		$wpdb->update( $wpdb->posts,
			array(
				'post_status' => 'trash',
			), array(
				'post_type' => 'featured-content',
				'post_status' => 'draft',
			)
		);
	}

	public function customize_save_customizer( $wp_customize ) {
		global $wpdb;

		$featured_areas = Featured_Content::get_areas();

		if ( $featured_areas ) {

			// Delete all published featured content
			$wpdb->delete( $wpdb->posts,
				array(
					'post_type' => 'featured-content',
					'post_status' => 'publish',
				)
			);

			// Delete all featured content in trash
			$wpdb->delete( $wpdb->posts,
				array(
					'post_type' => 'featured-content',
					'post_status' => 'trash',
				)
			);

			foreach ( $featured_areas as $featured_area ) {
				$featured_area_slug = sanitize_title( $featured_area );
				$theme_mod = get_theme_mod( $featured_area_slug, array() );
				if ( $theme_mod ) {
					$featured_items = json_decode( $theme_mod );
					$converts = array();
					$last_item = null;

					// Update all featured content in settings
					foreach ( $featured_items as $featured_item ) {
						$post_parent = $converts[ $featured_item->post_parent ];
						$converts[ $featured_item->ID ] = self::publish_featured_item( $featured_item, $post_parent );
					}
				}
			}
		}
	}

	private function publish_featured_item( $post, $post_parent ) {
		$draft_id = $post->ID;
		$post->post_parent = $post_parent;
		$post->ID = null;
		$post->post_status = 'publish';
		$post_id = wp_insert_post( $post );

		wp_set_post_terms( $post_id, $post->featured_area, 'featured-area', false );

		if ( get_post_meta( $draft_id, '_thumbnail_id', true ) ) :
			update_post_meta( $post_id, 'thumbnail_id', get_post_meta( $draft_id, '_thumbnail_id', true ) );
		endif;

		update_post_meta( $post_id, 'original_post_id', get_post_meta( $draft_id, 'original_post_id', true ) );

		return $post_id;
	}
}
