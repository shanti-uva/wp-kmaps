<?php
/**
 * Mandala Admin
 *
 * @class    Mandala_Admin
 * @package  Mandala\Admin
 * @version  1.0
 * @author Than Grove
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// require_once 'HTML/CSS.php';

/**
 * WC_Admin class.
 */
class Mandala_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_mandala/mandala.php', array( $this, 'add_settings_link' ) );
        add_action('admin_init', array($this, 'register_settings'));
		add_action( 'admin_menu', array($this, 'settings_page'));
		wp_enqueue_style('mandala-admin-css', plugins_url("css/mandala-admin.css", __FILE__),
            array(), "1.0", "all");
		wp_enqueue_style('jquery-linedtextarea-css', plugins_url("css/jquery-linedtextarea.css", __FILE__),
			array(), "1.0", "all");
		wp_enqueue_script( 'jquery-linedtextarea', plugins_url( "js/jquery-linedtextarea.js", __FILE__ ),
            array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'mandala-admin', plugins_url( "js/mandala-admin.js", __FILE__ ),
            array( 'jquery-linedtextarea' ), '1.0', true );
	}

    public function add_settings_link( $links ) {
	    // Build and escape the URL.
	    $url = esc_url( add_query_arg(
		    'page',
		    'mandala_settings',
		    get_admin_url() . 'options-general.php?page=mandala_settings'
	    ) );
	    // Create the link.
	    $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	    // Adds the link to the end of the array.
	    array_push(
		    $links,
		    $settings_link
	    );
	    return $links;
    }

    public function register_settings() {
	    register_setting(
            'mandala_plugin_options',
            'mandala_plugin_options',
            array($this, 'options_validate'));
        add_settings_section(
            'mandala_hook_names',
            'Theme Hook Names',
            array($this, 'hook_names_section'),
            'mandala_settings');
	    add_settings_field(
		    'automatic_insert',
		    'Insert Shorcodes Automatically',
		    array($this, 'automatic_insert_field'),
		    'mandala_settings',
		    'mandala_hook_names');
	    add_settings_field(
            'main_hook_name',
            'Mandala Root Hook',
            array($this, 'main_hook_name_field'),
            'mandala_settings',
            'mandala_hook_names');
	    add_settings_field(
            'search_hook_name',
            'Global Search Hook',
            array($this, 'global_search_hook_name_field'),
            'mandala_settings',
            'mandala_hook_names');
	    add_settings_field(
            'advanced_search_hook_name',
            'Advanced Search Hook',
            array($this, 'advance_search_hook_name_field'),
            'mandala_settings',
            'mandala_hook_names');
	    add_settings_section(
		    'other_settings_section',
		    'Other Settings',
		    array($this, 'other_settings_section'),
		    'mandala_settings');
	    add_settings_field(
		    'default_sidebar',
		    'Default Sidebar',
		    array($this, 'default_sidebar_field'),
		    'mandala_settings',
		    'other_settings_section');
        add_settings_field(
            'hash_exceptions',
            'Hash Exceptions',
            array($this, 'hash_exception_field'),
            'mandala_settings',
            'other_settings_section');

    }
/*
    public function options_validate($input) {
        // Input keys are ["automatic_insert","main_hook_name","search_hook_name","advanced_search_hook_name","custom_styles"]
        $hook_fields = ["main_hook_name","search_hook_name","advanced_search_hook_name"];

        return $input;
    }
*/
	/**
     * Checks and sanitizes custom CSS styles
	 * @param $styles
	 *
	 * @return string
	 */
    /*
    private function check_css_styles($styles) {
	    $sanitized_styles = sanitize_textarea_field($styles);
	    $validator_url = 'https://jigsaw.w3.org/css-validator/validator?output=json&text=';
	    $result = file_get_contents($validator_url . urlencode($sanitized_styles));
	    $result = json_decode($result, true);
	    if ($result['cssvalidation']['validity']) {
		    add_settings_error('Custom Styles', esc_attr( 'custom_styles' ),
			    "Your custom Styles are Valid", 'success');
	    } else {
		    $errormsg = '<p>There was a problem with your custom styles:</p><blockquote>';
		    $subamount = 0;
		    foreach($result['cssvalidation']['errors'] as $eind => $err) {
			    if ($eind > 0) { $errormsg .= "<br/>"; }
			    $lnum = intval(($err['line'] * 1 + 1) / 2);
			    $errormsg .= "— {$err['message']} (line {$lnum})";
		    }
		    $errormsg .= '</blockquote>';
		    add_settings_error('Custom Styles', esc_attr( 'custom_styles' ),
			    $errormsg, 'error');
	    }
	    return $sanitized_styles;
    }
*/
	public function automatic_insert_field() {
		$options = get_option( 'mandala_plugin_options' );
        $check_val = $options['automatic_insert'] ?? 0;
		echo "<input id='mandala_main_hook_name' name='mandala_plugin_options[automatic_insert]' type='checkbox' " .
		     "value='1'" . checked( 1, $check_val, false ) . "' /><p></p>";
	}

    public function hook_names_section() {
        echo "<p>In this section enter the name of the main theme hook where Mandala content should be inserted.<br/>" .
             "The name of the hook for any of the mandala short codes.</p>";
        if (!file_exists(MANDALA_APP_PATH)) {
            echo  "<p class='warning'>Warning: The Mandala App directory has not been installed " .
                  "on this site. <br/>" .
                  "The Mandala plugin will not do anything without it. <br/>" .
                  "Please talk to your administrator.</p>";
        }
    }

    public function main_hook_name_field() {
	    $options = get_option( 'mandala_plugin_options' );
        $option_val = !empty($options['main_hook_name']) ? $options['main_hook_name'] : '';
	    echo "<input id='mandala_main_hook_name' name='mandala_plugin_options[main_hook_name]' type='text' " .
             "value='" . esc_attr( $option_val ) . "' /><p><em>Where to place the mandala root short code. (Required)</em></p>";
    }

	public function global_search_hook_name_field() {
		$options = get_option( 'mandala_plugin_options' );
		$option_val = !empty($options['search_hook_name']) ? $options['search_hook_name'] : '';
		echo "<input id='mandala_search_hook_name' name='mandala_plugin_options[search_hook_name]' type='text' " .
		     "value='" . esc_attr( $option_val ) . "' /><p><em>Where to place the mandala search box.</em></p>";
	}

	public function advance_search_hook_name_field() {
		$options = get_option( 'mandala_plugin_options' );
		$option_val = !empty($options['advanced_search_hook_name']) ? $options['advanced_search_hook_name'] : '';
		echo "<input id='mandala_advanced_search_hook_name' name='mandala_plugin_options[advanced_search_hook_name]' " .
		     "type='text' value='" . esc_attr( $option_val ) . "' /><p><em>Where to place the mandala <strong>advanced</strong> search box.</em></p>";
	}

	public function other_settings_section() {
		echo "<p>These are miscellaneous other settings for the Mandala plugin.</p>" .
		     "<div id='styles_messages'></div>";
	}

	public function default_sidebar_field() {
		$options = get_option( 'mandala_plugin_options' );
		$option_val = !empty($options['default_sidebar']) ? $options['default_sidebar'] : '';
        $advsel = ($option_val == 1) ? " selected='selected'" : '';
        $browsel = ($option_val == 2) ? " selected='selected'" : '';
		echo "<select id='mandala_default_sidebar' name='mandala_plugin_options[default_sidebar]' >" .
		    "<option value='1'$advsel>Advanced Search</option><option value='2'$browsel>Browse Trees</option></select>";
	}

    public function hash_exception_field() {
        $options = get_option( 'mandala_plugin_options' );
        $option_val = !empty($options['hash_exceptions']) ? $options['hash_exceptions'] : '';
        echo <<<EOT
            <div class='field-wrapper'>
                <textarea id='mandala_hash_exceptions' 
                          name='mandala_plugin_options[hash_exceptions]' 
                          rows="20" cols="40">$option_val</textarea>
                <p>Enter hash paths (including the hash) that you want the Mandala plugin to ignore (one per line).</p>
            </div>
        EOT;
    }

	public function settings_page() {
		add_options_page( 'Mandala Plugin Settings', 'Mandala',
			'manage_options', 'mandala_settings', array($this, 'render_settings_page') );
	}

	public function render_settings_page() {
		?>
		<h2>Mandala Plugin Settings</h2>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'mandala_plugin_options' );
			do_settings_sections( 'mandala_settings' ); ?>
			<input name="submit"
                   class="button button-primary"
                   type="submit"
                   value="<?php esc_attr_e( 'Save' ); ?>" />
		</form>
		<?php
	}
}

return new Mandala_Admin();