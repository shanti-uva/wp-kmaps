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

require_once(MANDALA_INCLUDES . '/enqueue.php');


/**
 * Adding custom div with id mandala-root for embedding mandala
 */
function acuf_add_mandala_div() {
    echo '<div id="mandala-root"></div>';
}

add_action('astra_entry_before', 'acuf_add_mandala_div');


/**
 * Add Redirect for /mandala
 */



/************* old stuff remove (create /mandala page) ****************************/
/**
 * Create a custom page with slug -> /mandala
 */
function add_my_custom_page() {
  $my_post = array(
    'post_title' => wp_strip_all_tags('mandala'),
    'post_content' => '',
    'post_status' => 'publish',
    'post_author' => 1,
    'post_type' => 'page',
    'page_template' => 'mandala',
  );
  $postValue = wp_insert_post( $my_post );
  update_option('mandalapage', $postValue);
}
register_activation_hook(__FILE__, 'add_my_custom_page');

/**
 * Delete the page created when the module is removed.
 */
function delete_my_custom_page() {
  $page_id = get_option('mandalapage');
  wp_delete_post($page_id);
}
register_deactivation_hook(__FILE__, 'delete_my_custom_page');

/**
 * Set page template for the mandala page.
 */
function mandala_page_template( $page_template ) {
  if ( is_page('mandala') ) {
    $page_template = dirname(__FILE__) . '/mandala-page-template.php';
  }
  return $page_template;
}
add_filter('page_template', 'mandala_page_template');

/**
 * Add wrapper for react app initialization in all pages except mandala page type.
 */
function mandala_wp_footer() {
  global $template;
  if (!(basename( $template ) === 'mandala-page-template.php')) {
    echo '<div id="root" style="display:none"></div>';
  }
}
add_action('wp_footer', 'mandala_wp_footer');