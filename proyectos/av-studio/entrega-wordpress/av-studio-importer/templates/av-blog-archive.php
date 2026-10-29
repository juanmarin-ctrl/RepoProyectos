<?php
/**
 * Blog archive — diseño AV (maqueta blog.html), entradas nativas de WP.
 */
if (!defined('ABSPATH')) {
	exit;
}

$hero_img = avsi_blog_hero_image_url();
$contact  = home_url('/contacto/');

get_header();
avsi_print_free_chrome('header');
?>
<main class="av-blog-page" id="content">
	<section class="av-blog-hero">
		<div class="av-blog-hero__media" aria-hidden="true">
			<img src="<?php echo esc_url($hero_img); ?>" alt="">
			<div class="av-blog-hero__overlay"></div>
		</div>
		<div class="av-blog-wrap av-blog-hero__copy">
			<p class="av-blog-eyebrow">Blog</p>
			<h1>Ciencia, color y criterio para tu práctica diaria</h1>
			<p class="av-blog-hero__lead">Contenido editable en WordPress: crea, corrige y publica entradas desde el panel sin tocar código.</p>
		</div>
	</section>

	<section class="av-blog-section">
		<div class="av-blog-wrap">
			<?php if (have_posts()) : ?>
				<div class="av-blog-grid">
					<?php
					while (have_posts()) :
						the_post();
						$cats = get_the_category();
						$cat  = !empty($cats[0]) ? $cats[0]->name : 'Blog';
						$mins = max(1, (int) ceil(str_word_count(wp_strip_all_tags(get_the_content() . ' ' . get_the_title())) / 180));
						$thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
						if (!$thumb) {
							$thumb = $hero_img;
						}
						?>
						<article <?php post_class('av-blog-card-native'); ?>>
							<a class="av-blog-card-native__media" href="<?php the_permalink(); ?>">
								<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
							</a>
							<p class="av-blog-card-native__meta"><?php echo esc_html($cat); ?> · <?php echo esc_html(sprintf('%02d min', $mins)); ?></p>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html(wp_trim_words(get_the_excerpt() ? get_the_excerpt() : get_the_content(), 22)); ?></p>
							<a class="av-blog-card-native__more" href="<?php the_permalink(); ?>">Leer más →</a>
						</article>
					<?php endwhile; ?>
				</div>
				<div class="av-blog-pagination">
					<?php
					the_posts_pagination(array(
						'mid_size'  => 1,
						'prev_text' => '← Anterior',
						'next_text' => 'Siguiente →',
					));
					?>
				</div>
			<?php else : ?>
				<p class="av-blog-empty">Aún no hay entradas publicadas. Crea la primera desde Entradas → Añadir.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="av-blog-section av-blog-section--mist">
		<div class="av-blog-wrap av-blog-cta">
			<div>
				<p class="av-blog-eyebrow av-blog-eyebrow--ink">Autoadministrable</p>
				<h2>El blog es 100% editable desde WordPress</h2>
				<p class="av-blog-cta__lead">Cada artículo es una entrada nativa: título, imagen destacada, texto y SEO sin código.</p>
			</div>
			<a class="av-blog-btn" href="<?php echo esc_url($contact); ?>">Hablar del proyecto</a>
		</div>
	</section>
</main>
<?php
avsi_print_free_chrome('footer');
get_footer();
