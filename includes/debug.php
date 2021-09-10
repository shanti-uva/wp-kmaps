<?php

/**
 * Functions for debugging.
 *
 * In mandala.php (un)comment the include for this file to activate/deactivate debugging functions
 *
 */

function mandala_which_template_is_loaded()
{
    if (is_super_admin()) {
        global $template;
        print_r($template);
    }
}

add_action('wp_footer', 'mandala_which_template_is_loaded');
