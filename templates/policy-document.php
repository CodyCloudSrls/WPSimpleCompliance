<?php
/**
 * Minimal public template for policy pages managed by WPSimpleCompliance.
 */

if (! defined('ABSPATH')) {
	exit;
}

$home_url = home_url('/');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class('spcp-document-page'); ?>>
<?php wp_body_open(); ?>
<main id="main" class="spcp-document-shell">
	<a class="spcp-document-back" href="<?php echo esc_url($home_url); ?>">&larr; Torna al sito</a>
	<div class="spcp-document-card">
		<?php
		while (have_posts()) :
			the_post();
			the_content();
		endwhile;
		?>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
