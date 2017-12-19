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

		$wp_customize->register_control_type( 'Featured_Content_Manager\Featured_Area_Control' );

		$wp_customize->add_setting( 'featured_area' , array(
			'default'   => '[]',
			'sanitize_callback' => 'sanitize_text_field',
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
					'label' => esc_html__( 'Example Background', 'customizer-background-control' ),
					'section' => 'featured_area',
				)
			)
		);

	}

	public function enqueue_customize_control() {
		$fields = Featured_Content::get_fields();
		wp_enqueue_style( 'featured-area-style', plugins_url( 'dist/css/customizer.css', dirname( __FILE__ ) ), array(), '1', 'screen' );
		wp_enqueue_script( 'nested-sortable', plugins_url( 'dist/js/jquery.mjs.nestedSortable.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_register_script( 'featured-area-script', plugins_url( 'dist/js/customizer-debug.js', dirname( __FILE__ ) ), array( 'jquery', 'customize-controls', 'nested-sortable' ) );
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
					<label class="screen-reader-text" for="featured-items-search"><?php _e( 'Search Featured Items', 'featured-content-manager' ); ?></label>
					<input type="text" id="featured-items-search" placeholder="Search features items…" aria-describedby="featured-items-search-desc" />
					<p class="screen-reader-text" id="featured-items-search-desc"><?php _e( 'The search results will be updated as you type.', 'featured-content-manager' ); ?></p>
					<span class="spinner"></span>
					<div class="search-icon" aria-hidden="true"></div>
				</div>
				<ul id="available-featured-items-list" class="accordion-section-content">
					<li class="nothing-found"><?php _e( 'No results found.', 'featured-content-manager' ); ?></li>
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
					<?php foreach ( $fields as $field ) :
						Customizer::render_input( $field, 'data' );
					endforeach ?>
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

	public function customize_init_customizer() {
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

	public function customize_save_customizer() {
		global $wpdb;

		// Delete all featured content that is sitll trashed
		$wpdb->delete( $wpdb->posts,
			array(
				'post_type' => 'featured-content',
				'post_status' => 'publish',
			)
		);
		$wpdb->delete( $wpdb->posts,
			array(
				'post_type' => 'featured-content',
				'post_status' => 'trash',
			)
		);

		$drafts = get_posts( array(
			'post_type' => 'featured-content',
			'post_status' => 'draft',
		));
		foreach ( $drafts as $post ) {
			Rest::copy_post_to_featured_content( $post, 'publish', $post->post_parent );
		}
	}
}
