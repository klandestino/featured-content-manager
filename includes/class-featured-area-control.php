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

	public $id = '';

	public $max = 10;

	public $levels = 1;

	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$this->id             = $id;
		$this->max            = $args['max'] ?? 10;
		$this->levels         = $args['levels'] ?? 1;
		$this->object_type    = $args['object_type'] ?? 'post';
		$this->object_subtype = $args['object_subtype'] ?? 'post';
	}

	public function to_json() {
		parent::to_json();
		$this->json['max']            = $this->max;
		$this->json['levels']         = $this->levels;
		$this->json['object_type']    = $this->object_type;
		$this->json['object_subtype'] = $this->object_subtype;
	}

	/**
	 * Extending content_template function.
	 */
	public function content_template() {
		?>
		<ol id={{data.section}} class="nested-sortable featured-area" data-max="{{data.max}}" data-levels="{{data.levels}}" data-type="{{data.object_type}}" data-subtype="{{data.object_subtype}}"></ol>
		<button class="add-featured-item button"><?php _e( 'Lägg till', 'featured-content-manager' ); ?></button>
		<div class="search-panel"></div>
		<?php
	}
}
