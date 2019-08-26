<?php
/**
 * Class Featured Content Manager Query Class.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

/**
 * A query class that extends WP_Query.
 */
class Featured_Query extends WP_Query {
	/**
	 * Override the WP_Query initial constructor.
	 *
	 * @param array $args Arguments for the query.
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			array(
				'posts_per_page' => -1,
				'post_type'      => 'featured-content',
				'post_parent'    => 0,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_status'    => ( is_customize_preview() ? 'draft' : 'publish' ),
			),
			$args
		);

		if ( isset( $args['featured-area'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'featured-area',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $args['featured-area'] ),
				),
			);
			unset( $args['featured-area'] );
		}

		parent::__construct( $args );
	}
}
