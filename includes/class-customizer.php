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
			'default'   => '#000000',
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
		wp_enqueue_style( 'featured-area', plugins_url( 'dist/css/customizer.css', dirname( __FILE__ ) ), array(), '1', 'screen' );
		wp_enqueue_script( 'nested-sortable', plugins_url( 'dist/js/jquery.mjs.nestedSortable.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_enqueue_script( 'featured-area', plugins_url( 'dist/js/customizer-debug.js', dirname( __FILE__ ) ), array( 'jquery', 'customize-controls', 'nested-sortable' ) );
		wp_localize_script( 'featured-area', 'wpApiSettings', array(
			'root' => esc_url_raw( '/wp-json/featured-content-manager/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		) );
		wp_localize_script( 'featured-area', 'wpApiSettings', array(
			'root' => esc_url_raw( '/wp-json/featured-content-manager/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		) );

	}

	public function enqueue_customize_preview() {
	}


	public static function customize_print_accordion() {
		?>
			<div id="available-featured-items" class="accordion-container">
				<div class="accordion-section-title">
					<div class="search-icon" aria-hidden="true"></div>
					<label class="screen-reader-text" for="featured-items-search">Search Featured Items</label>
					<input type="text" id="featured-items-search" placeholder="Search features items…" aria-describedby="featured-items-search-desc" />
					<p class="screen-reader-text" id="featured-items-search-desc">The search results will be updated as you type.</p>
					<span class="spinner"></span>
					<div class="search-icon" aria-hidden="true"></div>
				</div>
				<ul class="accordion-section-content available-featured-items-list">
					<li class="nothing-found">No results found.</li>
				</ul>
			</div>
		<?php
	}

	public static function customize_print_featured_item_template() {
		$fields = Featured_Content::get_fields();
	?>
		<script type="text/html" id="tmpl-featured-item">
			<li id="featured_item_{{data.ID}}" data-featured-item-id="{{data.ID}}">
				<div class="handle">
					{{data.post_title}}
					<button type="button" class="button-link featured-item-edit" aria-expanded="false">
						<span class="screen-reader-text"><?php echo esc_html( __( 'Edit featured item', 'featured-content-manager' ) ); ?>: {{data.post_title}} ({{data.post_type}})</span><span class="toggle-indicator" aria-hidden="true"></span>
					</button>
				</div>
				<div class="featured-item-settings">
					<?php foreach ( $fields as $field ) :
						Customizer::render_input( $field, 'data' );
					endforeach ?>
					<div class="featured-item-actions">
						<button type="button" class="button-link button-link-delete item-delete"><?php echo esc_html( __( 'Remove', 'featured-content-manager' ) ); ?></button>
						<span class="spinner"></span>
					</div>
				</div>
				<# if ( data.children ) { #>
				<# if ( data.children.length >= 1 ) { #>
					<ol>
					<# for (i = 0; i < data.children.length; i++) { #>
						<li id="featured_item_{{data.children[i].ID}}" data-featured-item-id="{{data.children[i].ID}}">
							<div class="handle">
								{{data.children[i].post_title}}
								<button type="button" class="button-link featured-item-edit" aria-expanded="false">
									<span class="screen-reader-text"><?php echo esc_html( __( 'Edit featured item', 'featured-content-manager' ) ); ?>: {{data.children[i].post_title}} ({{data.children[i].port_type}})</span><span class="toggle-indicator" aria-hidden="true"></span>
								</button>
							</div>
							<div class="featured-item-settings">
								<?php foreach ( $fields as $field ) :
									Customizer::render_input( $field, 'data.children[i]' );
								endforeach ?>
								<div class="featured-item-actions">
									<button type="button" class="button-link button-link-delete item-delete"><?php echo esc_html( __( 'Remove', 'featured-content-manager' ) ); ?></button>
									<span class="spinner"></span>
								</div>
							</div>
						</li>
					<# } #>
					<ol>
					<# } #>
				<# } #>
			</li>
		</script>
	<?php
	}

	public static function customize_print_search_result_item_template() {
	?>
		<script type="text/html" id="tmpl-search-item">
			<li id="search-item-tpl-post-{{data.id}}" class="search-item-tpl" data-search-item-id="{{data.id}}">
				<div class="search-item-bar">
					<div class="search-item-handle">
						<span class="search-type" aria-hidden="true">{{data.type}}</span>
						<span class="search-title" aria-hidden="true">
							<span class="search-item-title">{{data.title.rendered}}</span>
						</span>
						<button type="button" class="button-link item-add">
							<span class="screen-reader-text"><?php echo esc_html( __( 'Add to featured area', 'featured-content-manager' ) ); ?>: {{data.title.rendered}} ({{data.post_type}})</span>
						</button>
					</div>
				</div>
			</li>
		</script>
	<?php
	}

	public function render_input( $field, $sign ) {
		switch ( $field['type'] ) {
			case 'textarea':
				?>
				<p>
					<label for="edit-post-title">
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<textarea rows="4" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-input">{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}</textarea>
					</label>
				</p>
				<?php
				break;
			default:
				?>
				<p>
					<label for="edit-post-title">
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<input type="text" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-input" value="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}"/>
					</label>
				</p>
				<?php
				break;
		}
	}
}
