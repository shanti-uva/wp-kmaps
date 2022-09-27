<?php
/**
 * @wordpress-plugin
 * Plugin Name: Mandala React App
 * Description: Mandala React App embedded in a WordPress Page called /mandala
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Gerard Ketuma, Than Grove
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

final class Mandala {

	/**
	 * Mandala version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var Mandala
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Main Mandala Instance.
	 *
	 * Ensures only one instance of Mandala is loaded or can be loaded.
	 *
	 * @return Mandala - Main instance.
	 */
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Are Shortcodes Turned on for given page ID?
	 * Depends on Advanced Custom Fields and that a page field has been added called "use_short_codes"
	 * If ACF is not installed, always returns True.
	 *
	 * @param $pgid
	 *
	 * @return bool
	 */
	public static function shortcodesOn($pgid) {
		if (!function_exists('get_field')) {
			return true;
		}
		if ( is_int($pgid) ) {
			$use_shortcodes = get_field('use_short_codes', $pgid);
			if ($use_shortcodes == 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Mandala Constructor.
	 */
	public function __construct() {
		$this->redirect_check();  // do before set up because it exits if redirect is triggered
		$this->setup();
		$this->add_shortcodes();
		$this->add_widgets();
		$this->add_filters();
		$this->enqueue_scripts();
		$this->enqueue_styles();
		$this->enqueue_mandala_manifest();
		$this->add_mandala();
	}

	private function redirect_check() {
		//error_log("request: " . $_SERVER['REQUEST_URI'] . ' : ' . $_SERVER['HTTP_REFERER']);
		if ($_SERVER['REQUEST_URI'] == '/mandala/') {
			$redir_url = (empty($_SERVER['HTTP_REFERER'])) ? '/' : $_SERVER['HTTP_REFERER'];
			error_log("Redirecting to: " . $redir_url);
			header("Location: $redir_url");
			exit(0);
		}
	}

	/**
	 * Define Mandala Constants and perform includes.
	 */
	private function setup() {
		define('MANDALA_HOME', plugin_dir_path(__FILE__) );
		define('MANDALA_APP_PATH', MANDALA_HOME . 'app/');
		define('MANDALA_ASSET_MANIFEST', MANDALA_APP_PATH . 'build/asset-manifest.json');
		define('MANDALA_INCLUDES', MANDALA_HOME . 'includes/');
		define('MANDALA_ADMIN', MANDALA_HOME . 'admin/');
		require_once(MANDALA_ADMIN . 'class-mandala-admin.php');  // Admin Class
		require_once(MANDALA_INCLUDES . 'class-mandala-widget.php'); // Widget Class
	}

	/**
	 * Define Mandala short codes
	 */
	private function add_shortcodes() {
		// Add shortcode for mandalaroot div
		add_shortcode('mandalaroot', function () {
			$form = file_get_contents(MANDALA_INCLUDES . 'mandala-root.php');
			return $form;
		});

		//Add shortcode for basic search div
		add_shortcode('mandalaglobalsearch', function () {
			$form = file_get_contents(MANDALA_INCLUDES . 'global-search.php');
			return $form;
		});

		//Add shortcode for advanced search div
		add_shortcode('madvsearch', function () {
			$form = file_get_contents(MANDALA_INCLUDES . 'advanced-search.php');
			return $form;
		});

	}

	/**
	 * Add filters for Mandala
	 */
	private function add_filters() {
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( ! preg_match( '/^mandala-/', $handle ) ) {
				return $tag;
			}
			return str_replace( ' src', ' async defer src', $tag );
		}, 10, 2 );


		// Add the page-custom (copy of Astra's page.php) template to template list
		add_filter('theme_page_templates', function ($templates) {
			$templates[plugin_dir_path(__FILE__) . 'templates/page-custom.php'] = 'Custom Mandala Page Template';
			return $templates;
		});

		// Add "mandala" body class so content hidden by default
		// Content revealed if there is no hash
		add_filter( 'body_class','add_mandala_class' );
		function add_mandala_class( $classes ) {
			$classes[] = 'mandala';
			$classes[] = 'devtest';
			return $classes;
		}
	}

	public function add_widgets() {
		add_action( 'widgets_init', array($this, 'add_widget_action') );
	}

	public function add_widget_action() {
		register_widget( 'mandala_widget' );
	}

	/**
	 * Define Mandala enqueue_scripts.
	 */
	private function enqueue_scripts() {
		if (!is_admin()) {
			wp_enqueue_script( 'googlemaps', esc_url_raw( 'https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyAXpnXkPS39-Bo5ovHQWvyIk6eMgcvc1q4&amp;sensor=false' ), array(), null );
			wp_enqueue_script( 'jquery-resizable', plugins_url( "public/js/jquery-resizable.min.js", __FILE__ ), array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'mandala-js', plugins_url( "public/js/mandala.js", __FILE__ ), array( 'jquery' ), '1.0', true );

            // Add hash exception array to DOM
            $options = get_option( 'mandala_plugin_options' );
            $hash_exceptions = !empty($options['hash_exceptions']) ? $options['hash_exceptions'] : '';
            $hash_exceptions = explode("\n", $hash_exceptions);
            $hash_exceptions = array_map(function($item) { return trim($item); }, $hash_exceptions);
            $hash_exceptions = 'window.mandala = { hash_execptions: ' . json_encode($hash_exceptions) . '};';
            wp_add_inline_script( 'mandala-js', $hash_exceptions);
		}
	}

	/**
	 * Define Mandala enqueue_styles.
	 */
	private function enqueue_styles() {
		if (!is_admin()) {
			wp_enqueue_style( 'mandala-googlefonts', esc_url_raw( 'https://fonts.googleapis.com/css?family=EB+Garamond:400,400i,500,700|Open+Sans:400,400i,600&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext' ), array(), null );
			wp_enqueue_style( 'fontawesome-main', esc_url_raw( 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/fontawesome.min.css' ), array(), null );
			wp_enqueue_style( 'fontawesome-solid', esc_url_raw( 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/solid.min.css' ), array(), null );
			wp_enqueue_style( 'bootstrap-main', esc_url_raw( 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css' ), array(), null );
			wp_enqueue_style( 'mandala-css', plugins_url( "public/css/mandala.css", __FILE__ ), array(), "1.0", "all" );
		}
	}

	/**
	 * Define Mandala enqueue_mandala_manifest.
	 * Enqueues the scripts and styles used for the Mandala app
	 */
	private function enqueue_mandala_manifest() {
		if (!is_admin()) {
			$asset_manifest_raw = file_get_contents(MANDALA_ASSET_MANIFEST);
			// error_log($asset_manifest_raw);
			$asset_manifest = json_decode($asset_manifest_raw, true)['files'];

			if (isset($asset_manifest['main.css'])) {
				wp_enqueue_style('mandala', get_site_url() . $asset_manifest['main.css']);
			}

			wp_enqueue_script('mandala-runtime', get_site_url() . $asset_manifest['runtime-main.js'], array('wp-element'), null, true);
			wp_enqueue_script('mandala-main', get_site_url() . $asset_manifest['main.js'], array('mandala-runtime'), null, true);

			foreach ($asset_manifest as $key => $value) {
				if (preg_match('@static/js/(.*)\.chunk\.js@', $key, $matches)) {
					if ($matches && is_array($matches) && count($matches) === 2) {
						$name = 'mandala-' . preg_replace('/[^A-Za-z0-9]/', '-', $matches[1]);
						wp_enqueue_script($name, get_site_url() . $value, array('mandala-main'), null, true);
					}
				}

				if (preg_match('@static/css/(.*)\.chunk\.css@', $key, $matches)) {
					if ($matches && is_array($matches) && count($matches) === 2) {
						$name = 'mandala-' . preg_replace('/[^A-Za-z0-9]/', '-', $matches[1]);
						wp_enqueue_style($name, get_site_url() . $value, array('mandala'), null);
					}
				}
			}
		}
	}


	/**
	 * Function to add actions to the theme hooks named in the admin settings page
	 * The actions defined below insert the shortcodes for mandala root, global search, and advanced search
	 * This allows admins to determine on a site wide basis where to put mandala content=
	 */
	public function add_mandala()
	{
		// Add using the hook defined in settings
		$options = get_option( 'mandala_plugin_options' );

		// Do not add hook actions if checkbox is not checked so just return
		if (empty($options['automatic_insert'])) {
			error_log("Automatic insert disabled!");
			return;
		}

		if (!empty($options['main_hook_name'])) {
			add_action($options['main_hook_name'], array($this, 'add_mandala_root'));
		}

		if (!empty($options['search_hook_name'])) {
			add_action($options['search_hook_name'], array($this, 'add_search'));
		}

		if (!empty($options['advanced_search_hook_name'])) {
			add_action($options['advanced_search_hook_name'], array($this, 'add_advanced_search'));
		}
	}

	// Add Functions called from the add actions in add_mandala()

	/**
	 * Adds Shortcode for mandala root (<div id="mandala-root"></div>)
	 */
	public function add_mandala_root() {
		// error_log("In add mandala root");
		$page_id = get_queried_object_id();
		if (Mandala::shortcodesOn($page_id)) {
			echo do_shortcode( '[mandalaroot]' );
		}
	}

	/**
	 * Adds Shortcode for global search
	 */
	public function add_search() {
		$page_id = get_queried_object_id();
		if (Mandala::shortcodesOn($page_id)) {
			echo do_shortcode( '[mandalaglobalsearch]' );
		}
	}

	/**
	 * Adds Shortcode for advanced search
	 */
	public function add_advanced_search() {
		$page_id = get_queried_object_id();
		if (Mandala::shortcodesOn($page_id)) {
			echo do_shortcode( '[madvsearch]' );
		}
	}

	/**
	 * For Removing unwanted hook actions if they happen to accord
	 * Not called here, just from command line
	 * @param $hookname
	 * @param $callback
	 */
	public function remove_action($hookname, $callback) {
		remove_action($hookname, array($this, $callback));
	}

}


// Instantiate our class
$Mandala = Mandala::getInstance();