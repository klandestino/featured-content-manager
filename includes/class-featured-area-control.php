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
	 * @var string $type The type.
	 */
	public $type = 'featured-area';

	public $max = 0;

	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$this->max = $args['max'] ?? 0;
	}

	public function to_json() {
		parent::to_json();
		$this->json['max'] = $this->max;
	}

	/**
	 * Extending content_template function.
	 */
	public function content_template() {
		?>
		<ol id={{data.section}} class="nested-sortable featured-area" data-max="{{data.max}}"></ol>
		<button class="add-featured-item button">Lägg till</button>
		<div class="search-panel"></div>
		<?php
	}
}
