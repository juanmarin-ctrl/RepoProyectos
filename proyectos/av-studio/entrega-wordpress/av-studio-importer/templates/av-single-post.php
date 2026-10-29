<?php
/**
 * Single post — atmósfera AV alineada con el blog de la maqueta.
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();
avsi_print_free_chrome('header');

while (have_posts()) :
	the_post();
	$cats  = get_the_category();
	$cat   = !empty($cats[0]) ? $cats[0]->name : 'Blog';
	$hero  = get_the_post_thumbnail_url(get_the_ID(), 'full');
	if (!$hero) {
		$hero = avsi_blog_hero_image_url();
	}
	?>
	<main class="av-blog-page av-blog-single" id="content">
		<section class="av-blog-hero av-blog-hero--single">
			<div class="av-blog-hero__media" aria-hidden="true">
				<img src="<?php echo esc_url($hero); ?>" alt="">
				<div class="av-blog-hero__overlay"></div>
			</div>
			<div class="av-blog-wrap av-blog-hero__copy">
				<p class="av-blog-eyebrow"><?php echo esc_html($cat); ?></p>
				<h1><?php the_title(); ?></h1>
				<p class="av-blog-hero__lead"><?php echo esc_html(get_the_date()); ?></p>
			</div>
		</section>

		<section class="av-blog-section">
			<div class="av-blog-wrap av-blog-prose">
				<?php the_content(); ?>
				<p class="av-blog-back">
					<a href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/blog/')); ?>">← Volver al blog</a>
				</p>
			</div>
		</section>
	</main>
	<?php
endwhile;

avsi_print_free_chrome('footer');
get_footer();
