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

	/**
	 * The ID for the Featured Area.
	 *
	 * @var string $id The area ID.
	 */
	public $id = '';

	/**
	 * The number of items that will be visable in the area.
	 *
	 * @var int $max Max items.
	 */
	public $max = 10;

	/**
	 * The number of levels to stack featured items.
	 *
	 * @var int $levels The levels.
	 */
	public $levels = 1;

	/**
	 * Constructor.
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Arguments to override class property defaults.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$this->id             = $id;
		$this->max            = $args['max'] ?? 10;
		$this->levels         = $args['levels'] ?? 1;
		$this->object_type    = $args['object_type'] ?? 'post';
		$this->object_subtype = $args['object_subtype'] ?? 'post';
	}

	/**
	 * Set JSON values from class attributes.
	 */
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
		<button class="add-featured-item button"><?php esc_html_e( 'Add', 'featured-content-manager' ); ?></button>
		<div class="search-panel"></div>
		<?php
	}
}
