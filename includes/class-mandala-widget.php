<?php
// Creating the widget
class mandala_widget extends WP_Widget {

	function __construct() {
		$widget_options = array (
			'classname' => 'mandala_widget',
			'description' => 'Widget for adding Mandala search divs to sidebar'
		);
        $control_options = array (
            "insert_search" => 3,
            "insert_advanced" => 1,
        );
		parent::__construct(

			'mandala_widget',

			__('Mandala Widget', 'wpb_widget_domain'),

			$widget_options,
			$control_options
		);
	}

// Creating widget front-end

	public function widget( $args, $instance ) {
        //echo "<div id='basicAndBrowse'><!--span id='basicSearchPortal'></span--><span id='browseSearchPortal'></span></div>";
		echo "<div id='advancedSearchPortal'></div>";
	}

// Widget Backend
	public function form( $instance ) {
		?>
		<p>Inserts Mandala Search box and Advanced Search column.</p>
		<?php
	}

// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

}
