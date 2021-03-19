<?php
/**
 * Class Featured Content Manager.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

/**
 * Class for registering the post types, taxonomies and theme setup.
 */
class Featured_Content {
	/**
	 * Get all the registred areas.
	 */
	public static function get_featured_areas() {
		$areas = array();
		$args  = get_theme_support( 'featured-content-manager' )[0];
		if ( isset( $args['featured_areas'] ) ) {
			foreach ( $args['featured_areas'] as $slug => $area ) {
				// Back compat, allow unkeyed arrays when registering featured areas.
				if ( is_int( $slug ) ) {
					$slug = sanitize_title( $area );
				}
				$areas[ $slug ] = $area;
			}
		}
		return $areas;
	}

	/**
	 * Getting fields for this theme setup.
	 */
	public static function get_fields() {
		$fields = array();
		$args   = get_theme_support( 'featured-content-manager' )[0];
		if ( isset( $args['fields'] ) ) {
			foreach ( $args['fields'] as $key => $field ) {
				if ( 'select' === $key ) {
					$field_name                            = $field['name'];
					$fields[ $field_name ]['name']         = $field['name'];
					$fields[ $field_name ]['display_name'] = $field['display_name'];
					$fields[ $field_name ]['type']         = 'select';
					$fields[ $field_name ]['values']       = $field['values'];
				}
			}
		}
		return $fields;
	}

	/**
	 * Returns a array of featured items for a specific area.
	 *
	 * @param string $featured_area The name or slug for the area.
	 */
	public static function get_featured_area_items( string $featured_area ): array {
		$featured_area       = sanitize_title( $featured_area );
		$featured_area_items = get_theme_mod( $featured_area );
		if (
			isset( $featured_area_items )
		) {
			return ( is_array( $featured_area_items ) ) ? $featured_area_items : json_decode( $featured_area_items );
		}
		return array();
	}
}
