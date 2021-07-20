<?php
// This file creates the page needed to load the application with slug /mandala

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