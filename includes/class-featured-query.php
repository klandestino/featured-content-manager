<?php
/**
 * Class Featured Content Manager Query Class.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

class Featured_Query extends WP_Query {
	function __construct( $args = array() ) {
		$args = wp_parse_args( array(
			'post_type' => 'featured-content',
			'orderby' => 'menu_order',
			'post_parent' => 0,
			'order' => 'ASC',
			'post_status' => ( is_customize_preview() ? 'draft' : 'publish'),
		), $args );

		parent::__construct( $args );

	}
}
