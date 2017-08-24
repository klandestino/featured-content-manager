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
