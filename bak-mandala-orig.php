<?php

/*** Original  version of the plugin for reference **/

defined('ABSPATH') or die('Direct script access disallowed.');

define('MANDALA_APP_PATH', plugin_dir_path(__FILE__) . '/app');
define('MANDALA_ASSET_MANIFEST', MANDALA_APP_PATH . '/build/asset-manifest.json');
define('MANDALA_INCLUDES', plugin_dir_path(__FILE__) . '/includes');
defined('ABSPATH') or die('Direct script access disallowed.');

// Comment following line out when not debugging or on Prod
// require_once(MANDALA_INCLUDES . '/debug.php');

require_once(MANDALA_INCLUDES . '/mandala-admin.php');

add_action('init', function () {
    error_log("request: " . $_SERVER['REQUEST_URI'] . ' : ' . $_SERVER['HTTP_REFERER']);
    if ($_SERVER['REQUEST_URI'] == '/mandala/') {
        $redir_url = (empty($_SERVER['HTTP_REFERER'])) ? '/' : $_SERVER['HTTP_REFERER'];
        error_log("Redirecting to: " . $redir_url);
        header("Location: $redir_url");
        exit(0);
    }

    // Add shortcode for mandalaroot div
    add_shortcode('mandalaroot', function () {
        $form = file_get_contents(__DIR__ . '/includes/mandala-root.php');
        return $form;
    });


    //Add shortcode for basic search div
    add_shortcode('mandalaglobalsearch', function () {
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
            wp_enqueue_script('jquery-resizable', plugins_url("jquery-resizable.min.js", __FILE__), array('jquery'), '1.0', true);
            wp_enqueue_script('mandala-js', plugins_url("mandala.js", __FILE__), array('jquery'), '1.0', true);
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
            }
        }
    });

    // Add the page-custom (copy of Astra's page.php) template to template list
    add_filter('theme_page_templates', function ($templates) {
        $templates[plugin_dir_path(__FILE__) . 'templates/page-custom.php'] = 'Custom Mandala Page Template';
        return $templates;
    });

    // Add the Mandala root div to standard pages that do not have the page-custom template
    function add_mandala_root()
    {
		if (get_option('mandala_autoinsert_setting', 1) == 1) {
			global $template;
			$template_path = get_page_template_slug();

			if ( ! strstr( $template_path, 'plugins/mandala/templates/page-custom.php' ) &&
			     ! strstr( $template, 'index.php' ) && empty(get_post_meta(get_the_ID(), 'subsite')[0]) ) {
				echo do_shortcode( '[mandalaroot]' );
			}
		}
    }

    // Add before content astra_entry_content_before
    add_action('astra_content_top', 'add_mandala_root');

	// Add advanced search div to show facets and trees
	/*
	function add_advanced_search_side()
	{
		global $template;
		$template_path = get_page_template_slug();
		if (!strstr($template_path, 'plugins/mandala/templates/page-custom.php')) {
			echo do_shortcode('[madvsearch]');
        }
	}
	*/
	// add before side bar
	// add_action('astra_sidebars_before', 'add_advanced_search_side');

    //Add filter to create the site title.

    function mandala_site_title($title) {
        $meta = get_post_meta(get_the_ID(), 'subsite');
        if (!empty($meta[0])) {
            return ' -- <span class="mandala-site-title">' . $meta[0]  . '</span>';
        } else {
            return $title;
        }
    }
    add_filter('astra_site_title_output', 'mandala_site_title');

    // Add filter to display Astra logo:
    function mandala_astra_logo() {
        global $post;
        // Custom fields for subsite existence and subsite alt text.
        $subsite = get_post_meta(get_the_ID(), 'subsite');

        $html = '';
        $html .= '<span class="site-logo-img">';
        //$html .= get_custom_logo();
        if (!empty($subsite[0])) {
            //Check if post has a parent and use the parent for link. If not assume post is parent.
            $link = '';
            if ((bool) $post->post_parent) {
                $link = get_permalink($post->post_parent);
            } else {
                $link = get_permalink($post);
            }

            $html .= '<a href="/" rel="home" class="main-logo-only">';
            $html .= file_get_contents( WP_PLUGIN_DIR . '/mandala/images/mandala_logo.svg');
            $html .= '</a>';
            $html .= '<a href="' . $link . '" class="custom-logo-link">';
            $html .= '<img src="' . get_the_post_thumbnail_url() . '" decoding="async" class="custom-logo" alt="' . get_post_meta(get_the_ID(), 'subsite-alt-text')[0] . '">';
            $html .= '</a>';
        } else {
            $html .= get_custom_logo();
        }
        $html .= '</span>';
        return $html;
    }
    add_filter('astra_logo', 'mandala_astra_logo');

});

