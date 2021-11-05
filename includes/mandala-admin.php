<?php

function mandala_settings_init() {
	// Add the section to reading settings so we can add our
	// fields to it
	add_settings_section(
		'mandala_setting_section',
		'Mandala settings section in reading',
		'mandala_setting_section_callback',
		'writing'
	);

	// Add the field with the names and function to use for our new
	// settings, put it in our new section
	add_settings_field(
		'mandala_autoinsert_setting',
		'Mandala Auto insert',
		'mandala_setting_callback',
		'writing',
		'mandala_setting_section'
	);

	// Register our setting so that $_POST handling is done for us and
	// our callback function just has to echo the <input>
	register_setting( 'writing', 'mandala_autoinsert_setting' );
} // eg_settings_api_init()

add_action( 'admin_init', 'mandala_settings_init' );


// ------------------------------------------------------------------
// Settings section callback function
// ------------------------------------------------------------------
//
// This function is needed if we added a new section. This function
// will be run at the start of our section
//

function mandala_setting_section_callback() {
	echo '<p>Settings for Mandala content insertion plugin</p>';
}

// ------------------------------------------------------------------
// Callback function for our example setting
// ------------------------------------------------------------------
//
// creates a checkbox true/false option. Other types are surely possible
//

function mandala_setting_callback() {
	echo '<input name="mandala_autoinsert_setting" id="mandala_autoinsert_setting" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'mandala_autoinsert_setting', 1 ), false ) . ' /> Do we want to auto insert Mandala divs?';
}