<?php
/**
 * @wordpress-plugin
 * Plugin Name: Mandala React App
 * Description: Mandala React App embedded in a WordPress Page called /mandala
 * Version: 0.1
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Gerard Ketuma
 * Domain Path: /languages
 */

defined('ABSPATH') or die('Direct script access disallowed.');

define('MANDALA_APP_PATH', plugin_dir_path(__FILE__) . '/app');
define('MANDALA_ASSET_MANIFEST', MANDALA_APP_PATH . '/build/asset-manifest.json');
define('MANDALA_INCLUDES', plugin_dir_path(__FILE__) . '/includes');
defined('ABSPATH') or die('Direct script access disallowed.');

// Comment following line out when not debugging or on Prod
require_once(MANDALA_INCLUDES . '/debug.php');

/**
 * TODO: Add Redirect for /mandala
 */


add_action('init', function () {
    //Register js and css for shortcodes
    // wp_register_script('mandala-shortcode-script', plugins_url('scripts/global-search.js', __FILE__), array('jquery'), '1.0', true);
    // wp_register_style('mandala-shortcode-style', plugins_url('styles/global-search.css', __FILE__), array(), '1.0', 'all');
    //wp_register_sty le("mandala-global-style", plugins_url("styles/global-site.css", __FILE__), array(), "1.0", "all");

    // Add shortcode for mandalaroot div
    add_shortcode('mandalaroot', function () {
//       wp_enqueue_script('jquery');
//       wp_enqueue_script('mandala-shortcode-script', array('jquery'), '1.0', true);
//       wp_enqueue_style('mandala-shortcode-style');
        $form = file_get_contents(__DIR__ . '/includes/mandala-root.php');
        return $form;
    });


    //Add shortcode for basic search div
    add_shortcode('mandalaglobalsearch', function () {
//        wp_enqueue_script('jquery');
//        wp_enqueue_script('mandala-shortcode-script', array('jquery'), '1.0', true);
//        wp_enqueue_style('mandala-shortcode-style');
        $form = file_get_contents(__DIR__ . '/includes/global-search.php');
        return $form;
    });

    //Add shortcode for advanced search div
    add_shortcode('madvsearch', function () {
        $form = file_get_contents(__DIR__ . '/includes/advanced-search.php');
        return $form;
    });

    add_filter('script_loader_tag', function ($tag, $handle) {
        if (!preg_match('/^mandala-/', $handle)) {
            return $tag;
        }
        return str_replace(' src', ' async defer src', $tag);
    }, 10, 2);

    add_action('wp_enqueue_scripts', function () {
        //wp_enqueue_style('mandala-global-style');
        if (!is_admin()) {
            wp_enqueue_style('mandala-googlefonts', esc_url_raw('https://fonts.googleapis.com/css?family=EB+Garamond:400,400i,500,700|Open+Sans:400,400i,600&subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext'), array(), null);
            wp_enqueue_style('fontawesome-main', esc_url_raw('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/fontawesome.min.css'), array(), null);
            wp_enqueue_style('fontawesome-solid', esc_url_raw('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/solid.min.css'), array(), null);
            wp_enqueue_style('bootstrap-main', esc_url_raw('https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'), array(), null);
            wp_enqueue_script('googlemaps', esc_url_raw('https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyAXpnXkPS39-Bo5ovHQWvyIk6eMgcvc1q4&amp;sensor=false'), array(), null);
            wp_enqueue_style('mandala-css', plugins_url("mandala.css", __FILE__), array(), "1.0", "all");
            //wp_register_script('mandalajs', plugin_dir_url(__FILE__) . '/mandala.js', array('jquery'), '1.0', true);
            // wp_enqueue_script('mandalajs');
            $asset_manifest = json_decode(file_get_contents(MANDALA_ASSET_MANIFEST), true)['files'];

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

                // if (preg_match('@static/media/(.*)\.css@', $key, $matches)) {
                //   if ($matches && is_array($matches) && count($matches) === 2) {
                //     $name = "mandala-" . preg_replace('/[^A-Za-z0-9]/', '-', $matches[1]);
                //     wp_enqueue_style($name, get_site_url() . $value, array('mandala'), null);
                //   }
                // }
            }
        }
    });

    // Add advanced search portal on page-custom.php templates for custom placement of mandala content
    function custom_adv_search_portal()
    {
        global $template;
        $template_name = basename($template);
        if ($template_name == 'page-custom.php') {
            error_log("Not adding mandala root");
            //echo do_shortcode('[madvsearch]');
        } else {
            echo do_shortcode('[mandalaroot]');
        }
    }

    add_action('astra_primary_content_top', 'custom_adv_search_portal');
});

