<?php
/**
 * Frontend runtime AV Studio (estilos, JS, blog, body classes).
 * Lo carga el plugin Maestro y también el MU-plugin desplegado en wp-content/mu-plugins.
 * Así al borrar el instalador el sitio sigue viéndose igual.
 */
if (!defined('ABSPATH')) {
	exit;
}

if (defined('AVSI_FRONTEND_LOADED')) {
	return;
}
define('AVSI_FRONTEND_LOADED', true);

if (!defined('AVSI_RUNTIME_VERSION')) {
	define('AVSI_RUNTIME_VERSION', defined('AVSI_VERSION') ? AVSI_VERSION : '2.7.9');
}

/**
 * Ruta/URL de assets: MU-plugin primero; si no, el plugin Maestro.
 */
function avsi_runtime_base_path() {
	if (defined('AVSI_RUNTIME_PATH') && AVSI_RUNTIME_PATH) {
		return AVSI_RUNTIME_PATH;
	}
	if (defined('AVSI_PATH')) {
		return AVSI_PATH;
	}
	return '';
}

function avsi_runtime_base_url() {
	if (defined('AVSI_RUNTIME_URL') && AVSI_RUNTIME_URL) {
		return AVSI_RUNTIME_URL;
	}
	if (defined('AVSI_URL')) {
		return AVSI_URL;
	}
	return '';
}

/**
 * Body classes (server-side) para que el CSS de marca aplique desde el primer paint.
 */
add_filter('body_class', function ($classes) {
	$classes[] = 'av-studio-site';
	if (avsi_pro_chrome_active()) {
		$classes[] = 'av-pro-chrome';
	}
	if (avsi_is_custom_blog_view()) {
		$classes[] = 'av-blog-native';
	}
	// Tutor LMS: ayuda a aplicar el look AV en curso individual / archivo.
	if (function_exists('tutor') || post_type_exists('courses')) {
		if (is_singular('courses') || is_post_type_archive('courses') || is_tax('course-category')) {
			$classes[] = 'av-tutor-skin';
		}
	}
	return $classes;
});

/**
 * ¿Header/footer Elementor Pro (Theme Builder) activo?
 */
function avsi_pro_chrome_active() {
	if (!class_exists('\\ElementorPro\\Plugin')) {
		return false;
	}
	$ids = get_option('avsi_pro_chrome_ids');
	if (empty($ids['header']) || empty($ids['footer'])) {
		return false;
	}
	return get_post_status((int) $ids['header']) === 'publish' && get_post_status((int) $ids['footer']) === 'publish';
}

/**
 * Vista blog / single post con plantilla AV.
 */
function avsi_is_custom_blog_view() {
	return (is_home() && !is_front_page()) || is_singular('post');
}

/**
 * ID de plantilla Elementor Library por título.
 */
function avsi_library_id_by_title($title) {
	$all = get_posts(array(
		'post_type' => 'elementor_library',
		'post_status' => 'publish',
		'numberposts' => 80,
	));
	foreach ($all as $p) {
		if ($p->post_title === $title) {
			return (int) $p->ID;
		}
	}
	return 0;
}

/**
 * Imagen hero del blog (medios importados o fallback del runtime).
 */
function avsi_blog_hero_image_url() {
	$base = avsi_runtime_base_url();
	$fallback = $base ? trailingslashit($base) . 'assets/images/stock/blog-porosidad.jpg' : '';
	$q = new WP_Query(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'posts_per_page' => 1,
		'meta_query' => array(
			array(
				'key' => '_wp_attached_file',
				'value' => 'blog-porosidad',
				'compare' => 'LIKE',
			),
		),
	));
	if (!empty($q->posts[0])) {
		$url = wp_get_attachment_image_url((int) $q->posts[0]->ID, 'full');
		if ($url) {
			return $url;
		}
	}
	return $fallback;
}

/**
 * En Free: pinta header/footer Elementor en vistas de blog (Pro ya lo hace vía Theme Builder).
 */
function avsi_print_free_chrome($which) {
	if (avsi_pro_chrome_active() || !class_exists('\\Elementor\\Plugin')) {
		return;
	}
	$title = $which === 'footer' ? 'AV Footer' : 'AV Header';
	$id = avsi_library_id_by_title($title);
	if (!$id) {
		return;
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($id);
}

/**
 * Hello Elementor: no pintar su chrome si Pro o si nosotros lo sustituimos en el blog.
 */
add_filter('hello_elementor_header_footer', function ($show) {
	if (avsi_pro_chrome_active()) {
		return false;
	}
	if (avsi_is_custom_blog_view() && (avsi_library_id_by_title('AV Header') || avsi_library_id_by_title('AV Footer'))) {
		return false;
	}
	return $show;
});

/**
 * Sustituye el listado WP por la plantilla AV del mockup.
 */
add_filter('template_include', function ($template) {
	$base = avsi_runtime_base_path();
	if (!$base) {
		return $template;
	}
	if (is_home() && !is_front_page()) {
		$custom = $base . 'templates/av-blog-archive.php';
		if (is_readable($custom)) {
			return $custom;
		}
	}
	if (is_singular('post')) {
		$custom = $base . 'templates/av-single-post.php';
		if (is_readable($custom)) {
			return $custom;
		}
	}
	return $template;
}, 99);

add_action('wp_enqueue_scripts', function () {
	$base_url = avsi_runtime_base_url();
	if (!$base_url) {
		return;
	}
	$ver = AVSI_RUNTIME_VERSION;

	wp_enqueue_style(
		'avsi-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Manrope:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'avsi-elementor-custom',
		trailingslashit($base_url) . 'assets/elementor-custom.css',
		array(),
		$ver
	);

	$front_deps = array('avsi-elementor-custom');
	foreach (array('tutor-frontend', 'tutor-front', 'tutor') as $tutor_handle) {
		if (wp_style_is($tutor_handle, 'registered') || wp_style_is($tutor_handle, 'enqueued')) {
			$front_deps[] = $tutor_handle;
			break;
		}
	}

	wp_enqueue_style(
		'avsi-frontend',
		trailingslashit($base_url) . 'assets/av-frontend.css',
		$front_deps,
		$ver
	);
	wp_enqueue_script(
		'avsi-frontend',
		trailingslashit($base_url) . 'assets/av-frontend.js',
		array(),
		$ver,
		true
	);

	$monogram = trailingslashit($base_url) . 'assets/images/brand/monograma-av-oro.png';
	$logo_id = (int) get_theme_mod('custom_logo');
	if ($logo_id) {
		$url = wp_get_attachment_image_url($logo_id, 'full');
		if ($url) {
			$monogram = $url;
		}
	}
	$course_fallback = trailingslashit($base_url) . 'assets/images/stock/curso-tricologia.jpg';
	wp_localize_script(
		'avsi-frontend',
		'avsiFrontend',
		array(
			'monogramUrl' => esc_url_raw($monogram),
			'courseFallbackUrl' => esc_url_raw($course_fallback),
		)
	);
}, 100);
