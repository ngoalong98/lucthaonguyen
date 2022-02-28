<?php
/**
 * Full Page Empty Page Template
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that other
 * 'pages' on your WordPress site may use a different template.
 *
 * @package FullPage for Elementor
 */

?><!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
<link rel="profile" href="https://gmpg.org/xfn/11"/>
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>"/>
<?php
  wp_head();
?>
</head>
<body <?php body_class(); ?>>
<div id="page" class="site">
<section id="primary" class="content-area">
<?php
if ( have_posts() ) {
  // Load posts loop.
  while ( have_posts() ) {
    the_post();
    the_content();
  }
}
?>
</section><!-- .content-area -->
</div><!-- #page -->
<?php
  wp_footer();
?>
</body>
</html>
