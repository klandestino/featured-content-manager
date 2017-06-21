<?php
/**
 * Featured Area Control.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

class Featured_Area_Control extends \WP_Customize_Control {
	public $type = 'featured-area';

	public function enqueue() {
		parent::enqueue();

		wp_enqueue_style( 'featured-area', plugins_url( 'dist/css/customizer.css', dirname( __FILE__ ) ), array(), '1', 'screen' );
		wp_enqueue_script( 'nested-sortable', plugins_url( 'dist/js/jquery.mjs.nestedSortable.js', dirname( __FILE__ ) ), array( 'jquery' ) );
  		wp_enqueue_script( 'featured-area', plugins_url( 'dist/js/customizer-debug.js', dirname( __FILE__ ) ), array( 'jquery', 'nested-sortable' ) );		  		wp_enqueue_script( 'featured-area', plugins_url( 'dist/js/customizer-debug.js', dirname( __FILE__ ) ), array( 'jquery', 'nested-sortable' ) );
		wp_localize_script( 'featured-area', 'wpApiSettings', array( 'root' => esc_url_raw( '/wp-json/featured-content-manager/v1/' ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
		wp_localize_script( 'featured-area', 'wpApiSettings', array(
			'root' => esc_url_raw( '/wp-json/featured-content-manager/v1/' ),
 			'nonce' => wp_create_nonce( 'wp_rest' ),
 		) );
	}
	
	public function to_json() {
		parent::to_json();
	}

	public function content_template() {
		parent::content_template();
	?>
		<ol class="featured-area"></ol>
		<span class="add-featured-item button">Lägg till</span>
	<?php
	}
}
