<?php
/**
 * Template Name: Page Mandala Custom
 *
 * The template for displaying all pages.
 * This is a copy of Astra page.php template unchanged.
 * Merely needed for its name to indicate not to embed custom mandala content automatically
 * So it can be positioned with shortcode [mandalaroot]
 *
 * @package WordPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
get_header(); ?>

<?php if (astra_page_layout() == 'left-sidebar') : ?>

    <?php get_sidebar(); ?>

<?php endif ?>

<div id="primary" <?php astra_primary_class(); ?>>

    <?php astra_primary_content_top(); ?>

    <?php astra_content_page_loop(); ?>

    <?php astra_primary_content_bottom(); ?>

</div><!-- #primary -->


<?php get_sidebar(); ?>
<?php if (astra_page_layout() == 'right-sidebar') : ?>


<?php endif ?>

<?php get_footer(); ?>
