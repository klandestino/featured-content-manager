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
		$featured_areas = Featured_Content::get_areas();

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

			foreach ( $featured_areas as $featured_area ) {
				$featured_area_slug = sanitize_title( $featured_area );

				$wp_customize->add_setting(
					$featured_area_slug,
					array(
						'default'           => '[]',
						'sanitize_callback' => 'sanitize_text_field',
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

	/**
	 * Function that prints WP Template markup for an search result item.
	 */
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
							<# if ( <?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?> ) { #>
								<img src="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] . '_src' ); ?>}}" alt="" />
								<input type="hidden" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-hidden" value="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}">
								<a class="featured-item-image-field-upload" style="display: none;" href="#">Välj bild</a>
								<a class="featured-item-image-field-remove" href="#">Ta bort</a>
							<# } else { #>
								<img src="#" style="display: none;" />
								<input type="hidden" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-hidden" value="">
								<a class="featured-item-image-field-upload" href="#">Välj bild</a>
								<a class="featured-item-image-field-remove" href="#">Ta bort</a>
							<# } #>
							</div>
						</div>
					</label>
				</p>
				<?php
				break;
			case 'taxonomy':
				?>
				<p>
					<label>
						<?php echo esc_html( $field['display_name'] ); ?><br/>
						<select name="taxonomy_<?php echo esc_html( $field['name'] ); ?>">
						<?php foreach ( $field['terms'] as $term ) { ?>
							<option name="<?php echo esc_html( $term->name ); ?>" value="<?php echo esc_html( $term->name ); ?>" <# if (<?php echo esc_html( $sign ); ?>.taxonomy_<?php echo esc_html( $field['name'] ); ?>=='<?php echo esc_html( $term->name ); ?>' ) { #>selected<# } #>><?php echo esc_html( $term->name ); ?></option>
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
						<input type="text" name="<?php echo esc_html( $field['name'] ); ?>" class="featured-item-edit-input" value="{{<?php echo esc_html( $sign ); ?>.<?php echo esc_html( $field['name'] ); ?>}}"/>
					</label>
				</p>
				<?php
				break;
		}
	}

	/**
	 * Functions for saving customizer settings.
	 *
	 * @param WP_Customize_Manager $wp_customize A customizer class.
	 */
	public static function customize_save_customizer( $wp_customize ) {
		$featured_areas = Featured_Content::get_areas();

		if ( $featured_areas ) {

			foreach ( $featured_areas as $featured_area ) {
				$featured_area_slug = sanitize_title( $featured_area );
				$theme_mod          = get_theme_mod( $featured_area_slug, array() );

				if ( $theme_mod ) {
					$featured_items = json_decode( $theme_mod );
					$old_to_new_id  = array();

					/**
					 * Loop through all currently published featured items in this area
					 * and add them to an array keyed by original post id.
					 * This is so that we can update that post below
					 * instead of trashing all posts and then creating new ones.
					 */
					$query = new \WP_Query(
						array(
							'post_type'      => 'featured-content',
							'post_status'    => [ 'publish', 'future' ],
							'fields'         => 'ids',
							'posts_per_page' => 200,
							'tax_query'      => [
								[
									'taxonomy' => 'featured-area',
									'terms'    => $featured_area_slug,
									'field'    => 'slug',
								],
							],
						)
					);

					$published_ids = [];
					if ( $query->have_posts() ) {
						while ( $query->have_posts() ) {
							$query->the_post();
							$published_ids[ get_post_meta( get_the_id(), 'original_post_id', true ) ] = get_the_id();
						}
						wp_reset_postdata();
					}

					/**
					 * Update all featured content in settings.
					 *
					 * We store the new ID as a value where the new published post id is
					 * the key in $old_to_new_id. So we as fast as posible can change the
					 * old post_parent to the new one.
					 */
					foreach ( $featured_items as $featured_item ) {
						// If the post has a parent we fetches the new one from $old_to_new_id.
						$post_parent = ( 0 === $featured_item->post_parent ? 0 : $old_to_new_id[ $featured_item->post_parent ] );

						/**
						 * Match if there already exists a published featured item in this area
						 * with the same original_post_id. If so let's update that instead
						 * of creating a new one.
						 */
						$featured_item->draft_id = $featured_item->ID;
						$draft_original_post_id  = get_post_meta( $featured_item->draft_id, 'original_post_id', true );
						if ( isset( $published_ids[ $draft_original_post_id ] ) ) {
							$featured_item->ID = $published_ids[ $draft_original_post_id ];
							unset( $published_ids[ $draft_original_post_id ] );
						} else {
							$featured_item->ID = null;
						}

						// Publish the featured item and store the new ID in $old_to_new_id.
						$old_to_new_id[ $featured_item->ID ] = self::publish_featured_item( $featured_item, $post_parent );
					}

					/**
					 * Delete all existing items in this area that didn't match
					 * any of the newly published ones.
					 */
					foreach ( $published_ids as $published_id ) {
						wp_delete_post( $published_id, true );
					}
				}
			}
		}
	}

	/**
	 * Functions for publishing a featured item.
	 *
	 * @param WP_Post $post A post object.
	 * @param int     $post_parent A post id for the parent post.
	 */
	private static function publish_featured_item( $post, $post_parent ) {
		$draft_id          = $post->draft_id;
		$post->post_parent = $post_parent;
		$post->post_status = 'publish';
		$post_id           = wp_insert_post( $post );

		wp_set_post_terms( $post_id, $post->featured_area, 'featured-area', false );
		update_post_meta( $post_id, 'original_post_id', get_post_meta( $draft_id, 'original_post_id', true ) );

		$org_post_thumbnail = get_post_thumbnail_id( $draft_id );
		if ( $org_post_thumbnail ) {
			set_post_thumbnail( $post_id, $org_post_thumbnail );
		}

		$taxonomies = get_object_taxonomies( 'featured-content' );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $draft_id, $taxonomy );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					wp_set_post_terms( $post_id, $term->name, $taxonomy, false );
				}
			}
		}

		return $post_id;
	}
}
