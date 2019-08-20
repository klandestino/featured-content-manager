<?php
/**
 * Featured Area Control.
 *
 * @package Featured_Content_Manager
 * @author  Jesper Nilsson <jesper@klandestino.se>
 */

namespace Featured_Content_Manager;

/**
 * Class extending WP_Customize_Control for Area Control in customizer.
 */
class Featured_Area_Control extends \WP_Customize_Control {
	/**
	 * Class public variables.
	 *
	 * @var string $type The taxonomy type.
	 */
	public $type = 'featured-area';

	/**
	 * Extending content_template function.
	 */
	public function content_template() {
		parent::content_template();
		?>
		<ol id={{data.section}} class="featured-area"></ol>
		<button class="add-featured-item button">Lägg till</button>
		<div class="search-panel"></div>
		<?php
	}
}
