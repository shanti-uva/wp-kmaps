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
		    'mandala_custom_styles',
		    'Custom Styles for Mandala',
		    array($this, 'custom_styles_section'),
		    'mandala_settings');
	    add_settings_field(
		    'custom_styles',
		    'Custom Styles',
		    array($this, 'custom_styles_field'),
		    'mandala_settings',
		    'mandala_custom_styles');

    }

    public function options_validate($input) {
        return $input;
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

	public function custom_styles_section() {
		echo "<p>In this area you can enter custom styles to be used on the site.</p>";
	}

	public function custom_styles_field() {
		$options = get_option( 'mandala_plugin_options' );
		$option_val = !empty($options['custom_styles']) ? $options['custom_styles'] : '';
		echo "<textarea id='mandala_custom_styles' name='mandala_plugin_options[custom_styles]' " .
		     "rows='25' cols='130' value='" . esc_attr( $option_val ) . "' ></textarea>" .
		     "<p><em>Enter custom styles here. " .
		     "Use the prefix “.mandala ” to target pages showing Mandala content</em></p>";
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