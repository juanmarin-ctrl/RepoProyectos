<?php
if (!defined('ABSPATH')) {
	exit;
}

class AVSI_Importer {
	private $media_map = array();
	private $url_map = array();
	private $messages = array();
	private $template_ids = array();
	private $template_content = array(); // title => elements array (from JSON files)
	private $page_ids = array();
	private $menu_id = 0;
	private $log_lines = array();

	public function run_full_site() {
		$this->log('=== AV Studio Maestro ' . AVSI_VERSION . ' ===');
		$this->log('PHP ' . PHP_VERSION . ' | WP ' . get_bloginfo('version'));
		$this->log('Elementor: ' . (defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'NO'));
		$this->log('Elementor Pro: ' . (class_exists('\\ElementorPro\\Plugin') ? 'SI' : 'NO'));
		$this->log('Tema: ' . get_template());

		$this->bootstrap_wp_files();
		$this->ensure_elementor_ready();

		$images = $this->import_images();
		$templates = $this->import_templates();
		$pages = $this->create_pages_with_elementor();
		$posts = $this->create_blog_posts();
		$menu_ok = $this->create_menus();
		$this->configure_reading();
		$this->configure_site_identity();
		$this->assign_header_footer();
		$this->maybe_save_custom_css();
		$runtime_ok = $this->deploy_runtime();
		$this->flush_elementor_css();
		flush_rewrite_rules(false);

		$this->log('=== FIN OK ===');
		if ($runtime_ok) {
			$this->log('Runtime MU-plugin listo: puedes DESACTIVAR y ELIMINAR AV Studio Maestro sin perder estilos.');
		}
		$this->persist_log();

		return array(
			'images' => $images,
			'templates' => $templates,
			'pages' => $pages,
			'posts' => $posts,
			'menu' => $menu_ok,
			'runtime' => $runtime_ok,
			'messages' => $this->messages,
			'log' => $this->log_lines,
		);
	}

	/**
	 * Copia CSS/JS/plantillas blog a wp-content/mu-plugins para que sobrevivan al borrar el instalador.
	 */
	public function deploy_runtime() {
		$mu_dir = WP_CONTENT_DIR . '/mu-plugins';
		$dest = $mu_dir . '/av-studio-runtime';
		$loader_dest = $mu_dir . '/av-studio-runtime.php';

		if (!wp_mkdir_p($mu_dir)) {
			$this->log('RUNTIME ERROR: no se pudo crear ' . $mu_dir);
			return false;
		}
		if (!wp_mkdir_p($dest)) {
			$this->log('RUNTIME ERROR: no se pudo crear ' . $dest);
			return false;
		}

		$copies = array(
			array(AVSI_PATH . 'includes/avsi-frontend.php', $dest . '/includes/avsi-frontend.php'),
			array(AVSI_PATH . 'assets/av-frontend.css', $dest . '/assets/av-frontend.css'),
			array(AVSI_PATH . 'assets/av-frontend.js', $dest . '/assets/av-frontend.js'),
			array(AVSI_PATH . 'assets/elementor-custom.css', $dest . '/assets/elementor-custom.css'),
			array(AVSI_PATH . 'templates/av-blog-archive.php', $dest . '/templates/av-blog-archive.php'),
			array(AVSI_PATH . 'templates/av-single-post.php', $dest . '/templates/av-single-post.php'),
		);

		// Brand + stock (incluye fallback de portada Tutor).
		$stock_files = array(
			'assets/images/brand/monograma-av-oro.png',
			'assets/images/stock/blog-porosidad.jpg',
			'assets/images/stock/curso-tricologia.jpg',
			'assets/images/stock/curso-ciencia-color.jpg',
			'assets/images/stock/curso-correccion-blonde.jpg',
			'assets/images/stock/metodologia-retrato.jpg',
		);
		foreach ($stock_files as $rel) {
			$from = AVSI_PATH . $rel;
			if (is_readable($from)) {
				$copies[] = array($from, $dest . '/' . $rel);
			}
		}

		$ok = 0;
		foreach ($copies as $pair) {
			list($from, $to) = $pair;
			if (!is_readable($from)) {
				$this->log('RUNTIME WARN: falta ' . $from);
				continue;
			}
			$dir = dirname($to);
			if (!wp_mkdir_p($dir)) {
				$this->log('RUNTIME ERROR: mkdir ' . $dir);
				continue;
			}
			if (!@copy($from, $to)) {
				$this->log('RUNTIME ERROR: copy → ' . $to);
				continue;
			}
			$ok++;
		}

		$loader_src = AVSI_PATH . 'runtime/mu-loader.php';
		if (!is_readable($loader_src)) {
			$this->log('RUNTIME ERROR: falta runtime/mu-loader.php');
			return false;
		}
		$loader = file_get_contents($loader_src);
		// Mantener la versión del Maestro en el runtime desplegado.
		$loader = preg_replace("/Version: [0-9.]+/", 'Version: ' . AVSI_VERSION, $loader, 1);
		$loader = preg_replace(
			"/define\\('AVSI_RUNTIME_VERSION', '[^']+'\\);/",
			"define('AVSI_RUNTIME_VERSION', '" . AVSI_VERSION . "');",
			$loader,
			1
		);
		if (false === file_put_contents($loader_dest, $loader)) {
			$this->log('RUNTIME ERROR: no se pudo escribir ' . $loader_dest);
			return false;
		}

		update_option('avsi_runtime_deployed', array(
			'version' => AVSI_VERSION,
			'path' => $dest,
			'time' => time(),
		), false);

		$this->ensure_course_thumbnails();

		$this->log('RUNTIME OK: ' . $ok . ' archivos → ' . $dest);
		$this->log('RUNTIME loader: ' . $loader_dest);
		return $ok > 0 && is_readable($loader_dest) && is_readable($dest . '/includes/avsi-frontend.php');
	}

	/**
	 * Cursos Tutor sin imagen destacada → asigna stock AV (tricología por defecto).
	 */
	public function ensure_course_thumbnails() {
		if (!post_type_exists('courses')) {
			$this->log('THUMBS: post type courses no existe');
			return 0;
		}
		$src = AVSI_PATH . 'assets/images/stock/curso-tricologia.jpg';
		if (!is_readable($src)) {
			$this->log('THUMBS: falta curso-tricologia.jpg');
			return 0;
		}
		$this->bootstrap_wp_files();
		$attach_id = $this->find_attachment_by_filename('curso-tricologia.jpg');
		if (!$attach_id) {
			$attach_id = $this->sideload_local_file($src, 'curso-tricologia.jpg');
		}
		if (!$attach_id || is_wp_error($attach_id)) {
			$this->log('THUMBS ERROR: no se pudo crear/encontrar attachment');
			return 0;
		}
		$courses = get_posts(array(
			'post_type' => 'courses',
			'post_status' => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => 100,
			'fields' => 'ids',
			'no_found_rows' => true,
		));
		$n = 0;
		foreach ($courses as $cid) {
			if (!has_post_thumbnail((int) $cid)) {
				set_post_thumbnail((int) $cid, (int) $attach_id);
				$n++;
			}
		}
		$this->log('THUMBS: ' . $n . ' cursos sin imagen → attachment #' . (int) $attach_id);
		return $n;
	}

	public function run() {
		return $this->run_full_site();
	}

	public function get_log_lines() {
		return $this->log_lines;
	}

	private function log($msg) {
		$line = '[' . gmdate('H:i:s') . '] ' . $msg;
		$this->log_lines[] = $line;
		$this->messages[] = $msg;
	}

	private function persist_log() {
		update_option('avsi_last_log', $this->log_lines, false);
		$upload = wp_upload_dir();
		if (empty($upload['error'])) {
			$file = trailingslashit($upload['basedir']) . 'avsi-import-log.txt';
			file_put_contents($file, implode("\n", $this->log_lines) . "\n");
			$this->log_lines[] = '[file] ' . $file;
		}
	}

	private function ensure_elementor_ready() {
		foreach (array('container', 'nested-elements', 'container_grid') as $feature) {
			update_option('elementor_experiment-' . $feature, 'active');
		}
		update_option('elementor_element_cache_ttl', 'disable');
		$this->log('Experimentos: container/nested ON + element cache OFF');
	}

	private function bootstrap_wp_files() {
		if (!function_exists('wp_generate_attachment_metadata')) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
	}

	private function import_images() {
		$dirs = array(
			AVSI_PATH . 'assets/images/brand',
			AVSI_PATH . 'assets/images/stock',
		);
		$count = 0;
		foreach ($dirs as $dir) {
			if (!is_dir($dir)) {
				$this->log('WARN: no existe carpeta ' . $dir);
				continue;
			}
			$files = array_merge(
				glob($dir . '/*.jpg') ?: array(),
				glob($dir . '/*.jpeg') ?: array(),
				glob($dir . '/*.png') ?: array(),
				glob($dir . '/*.webp') ?: array()
			);
			foreach ($files as $file) {
				$filename = basename($file);
				$existing = $this->find_attachment_by_filename($filename);
				if ($existing) {
					$refreshed = $this->refresh_attachment_file($existing, $file);
					$this->media_map[$filename] = $existing;
					$this->url_map[$filename] = wp_get_attachment_url($existing);
					if ($refreshed) {
						$this->log('IMG refresh #' . $existing . ' ' . $filename);
						$count++;
					} else {
						$this->log('IMG reuse #' . $existing . ' ' . $filename);
					}
					continue;
				}
				$attachment_id = $this->sideload_local_file($file, $filename);
				if (is_wp_error($attachment_id)) {
					$this->log('IMG ERROR ' . $filename . ': ' . $attachment_id->get_error_message());
					continue;
				}
				$this->media_map[$filename] = (int) $attachment_id;
				$this->url_map[$filename] = wp_get_attachment_url($attachment_id);
				$this->log('IMG new #' . $attachment_id . ' ' . $filename . ' → ' . $this->url_map[$filename]);
				$count++;
			}
		}
		$this->log('Medios mapeados: ' . count($this->url_map) . ' (nuevos ' . $count . ')');
		return $count;
	}

	private function find_attachment_by_filename($filename) {
		$query = new WP_Query(array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_wp_attached_file',
					'value' => $filename,
					'compare' => 'LIKE',
				),
			),
			'fields' => 'ids',
			'no_found_rows' => true,
		));
		return !empty($query->posts[0]) ? (int) $query->posts[0] : 0;
	}

	/**
	 * Sobrescribe el archivo de un attachment existente (mismas rutas/tamaños regenerados).
	 * Sirve para actualizar el banco de fotos al pulsar REPARAR.
	 */
	private function refresh_attachment_file($attachment_id, $absolute_path) {
		$path = get_attached_file($attachment_id);
		if (!$path || !is_readable($absolute_path)) {
			return false;
		}
		$dir = dirname($path);
		if (!is_dir($dir) || !is_writable($dir)) {
			return false;
		}
		$old_meta = wp_get_attachment_metadata($attachment_id);
		if (!empty($old_meta['sizes']) && is_array($old_meta['sizes'])) {
			foreach ($old_meta['sizes'] as $size) {
				if (empty($size['file'])) {
					continue;
				}
				$sized = trailingslashit($dir) . $size['file'];
				if (is_file($sized)) {
					@unlink($sized);
				}
			}
		}
		if (!@copy($absolute_path, $path)) {
			return false;
		}
		$meta = wp_generate_attachment_metadata($attachment_id, $path);
		if (!empty($meta) && !is_wp_error($meta)) {
			wp_update_attachment_metadata($attachment_id, $meta);
		}
		return true;
	}

	private function sideload_local_file($absolute_path, $filename) {
		$upload_dir = wp_upload_dir();
		if (!empty($upload_dir['error'])) {
			return new WP_Error('upload_dir', $upload_dir['error']);
		}
		$dest = trailingslashit($upload_dir['path']) . wp_unique_filename($upload_dir['path'], $filename);
		if (!copy($absolute_path, $dest)) {
			return new WP_Error('copy_failed', 'No se pudo copiar ' . $filename);
		}
		$filetype = wp_check_filetype($filename, null);
		$attach_id = wp_insert_attachment(array(
			'post_mime_type' => $filetype['type'],
			'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
			'post_content' => '',
			'post_status' => 'inherit',
		), $dest);
		if (is_wp_error($attach_id)) {
			return $attach_id;
		}
		wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $dest));
		update_post_meta($attach_id, '_avsi_source', 'av-studio-importer');
		return $attach_id;
	}

	private function import_templates() {
		$file_map = array(
			'av-inicio.json' => 'AV Inicio',
			'av-cursos.json' => 'AV Cursos',
			'av-contacto.json' => 'AV Contacto',
			'av-sobre-nosotros.json' => 'AV Sobre Nosotros',
			'av-servicios.json' => 'AV Servicios',
			'av-pagina-interior.json' => 'AV Pagina Interior',
			'av-header.json' => 'AV Header',
			'av-footer.json' => 'AV Footer',
		);
		$has_pro = class_exists('\\ElementorPro\\Plugin');

		$count = 0;
		foreach ($file_map as $filename => $forced_title) {
			$path = AVSI_PATH . 'templates/' . $filename;
			if (!file_exists($path)) {
				$this->log('JSON FALTA: ' . $filename);
				continue;
			}
			$raw = file_get_contents($path);
			if ($raw === false) {
				$this->log('JSON no legible: ' . $filename);
				continue;
			}
			if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
				$raw = substr($raw, 3);
				$this->log('JSON BOM eliminado: ' . $filename);
			}

			$before = substr_count($raw, '__AV_MEDIA__/');
			$raw = $this->replace_media_tokens($raw);
			$after_left = substr_count($raw, '__AV_MEDIA__/');
			$this->log($filename . ': tokens media ' . $before . ' → pendientes ' . $after_left);

			$data = json_decode($raw, true);
			if (!is_array($data)) {
				$this->log('JSON DECODE FAIL ' . $filename . ': ' . json_last_error_msg());
				continue;
			}
			if (empty($data['content']) || !is_array($data['content'])) {
				$this->log('JSON sin content: ' . $filename . ' keys=' . implode(',', array_keys($data)));
				continue;
			}

			$content = $this->hydrate_elementor_media($data['content']);
			if (!$has_pro) {
				$content = $this->adapt_for_free($content);
			}
			$title = $forced_title;
			$type = isset($data['type']) ? $data['type'] : 'page';
			$el_count = count($content);
			$this->log($filename . ': ' . strlen($raw) . ' bytes, widgets=' . $this->count_widgets($content));

			$this->template_content[$title] = $content;
			$post_id = $this->upsert_elementor_library($title, $type, $content, array());
			if ($post_id) {
				$this->template_ids[$title] = $post_id;
				$verify = $this->get_elementor_data($post_id);
				$vcount = is_array($verify) ? count($verify) : 0;
				$this->log("Plantilla OK '$title' #$post_id type=$type els=$el_count verify=$vcount");
				if ($vcount < 1) {
					$this->log("WARN: library vacía tras guardar $title — se usará JSON en memoria");
				}
				$count++;
			} else {
				$this->log("Plantilla FAIL guardar '$title'");
			}
		}
		$this->log('Plantillas listas: ' . $count);
		return $count;
	}

	private function create_pages_with_elementor() {
		$map = array(
			'Inicio' => array('template' => 'AV Inicio', 'slug' => 'inicio'),
			'Cursos' => array('template' => 'AV Cursos', 'slug' => 'cursos'),
			'Contacto' => array('template' => 'AV Contacto', 'slug' => 'contacto'),
			'Sobre nosotros' => array('template' => 'AV Sobre Nosotros', 'slug' => 'sobre-nosotros', 'builder' => 'about'),
			'Servicios' => array('template' => 'AV Servicios', 'slug' => 'servicios', 'builder' => 'services'),
			'Blog' => array('template' => null, 'slug' => 'blog'),
			'Aviso legal' => array('template' => null, 'slug' => 'aviso-legal', 'builder' => 'legal_aviso'),
			'Política de privacidad' => array('template' => null, 'slug' => 'politica-de-privacidad', 'builder' => 'legal_privacidad'),
			'Política de cookies' => array('template' => null, 'slug' => 'politica-de-cookies', 'builder' => 'legal_cookies'),
			'Política de reembolsos' => array('template' => null, 'slug' => 'politica-de-reembolsos', 'builder' => 'legal_reembolsos'),
			'Términos y condiciones' => array('template' => null, 'slug' => 'terminos-y-condiciones', 'builder' => 'legal_terminos'),
		);

		$count = 0;
		$embed_chrome = !class_exists('\\ElementorPro\\Plugin');
		$this->log('Embed header/footer en páginas: ' . ($embed_chrome ? 'SI (Free)' : 'NO (Pro Theme Builder)'));

		foreach ($map as $title => $cfg) {
			$page_id = $this->upsert_page($title, $cfg['slug']);
			$this->page_ids[$title] = $page_id;
			$count++;
			$this->log("Página '$title' #$page_id /{$cfg['slug']}");

			if ($title === 'Blog') {
				continue;
			}

			$source = 'none';
			$content = null;

			// 1) JSON template (full fidelity) → 2) PHP builder (rich fallback) → 3) generic
			if (!empty($cfg['template'])) {
				$tpl = $cfg['template'];
				if (!empty($this->template_content[$tpl])) {
					$content = $this->template_content[$tpl];
					$source = 'json-memory:' . $tpl;
				} elseif (!empty($this->template_ids[$tpl])) {
					$content = $this->get_elementor_data($this->template_ids[$tpl]);
					$source = 'library-meta:' . $tpl;
				}
			}
			if ((!is_array($content) || empty($content)) && !empty($cfg['builder'])) {
				$content = $this->build_simple_page_content($cfg['builder'], $title);
				$source = 'builder:' . $cfg['builder'];
			}

			if (!is_array($content) || empty($content)) {
				$content = $this->build_simple_page_content('generic', $title);
				$source = 'FALLBACK-generic';
				$this->log("ERROR página '$title': sin plantilla → genérico");
			}

			if ($embed_chrome) {
				$before = count($content);
				$content = $this->embed_header_footer($content);
				$this->log("Embed chrome '$title': els $before → " . count($content));
			}

			$ok = $this->apply_elementor_to_post($page_id, $content, 'wp-page');
			$verify = $this->get_elementor_data($page_id);
			$vcount = is_array($verify) ? count($verify) : 0;
			$this->log("SAVE '$title' source=$source ok=" . ($ok ? '1' : '0') . " verify_els=$vcount");

			if ($vcount < 1) {
				$this->log("REINTENTO meta-only '$title'");
				$this->write_elementor_meta($page_id, $content, 'wp-page');
				$verify = $this->get_elementor_data($page_id);
				$this->log("REINTENTO verify_els=" . (is_array($verify) ? count($verify) : 0));
			}
		}

		$this->log('Páginas procesadas: ' . $count);
		return $count;
	}

	private function upsert_page($title, $slug) {
		$existing = get_page_by_path($slug);
		if (!$existing) {
			$q = new WP_Query(array(
				'post_type' => 'page',
				'post_status' => 'any',
				'title' => $title,
				'posts_per_page' => 1,
			));
			// WP 6.2+ ignores title; fallback scan
			if (empty($q->posts)) {
				$all = get_posts(array('post_type' => 'page', 'post_status' => 'any', 'numberposts' => 100));
				foreach ($all as $p) {
					if ($p->post_title === $title) {
						$existing = $p;
						break;
					}
				}
			} else {
				$existing = $q->posts[0];
			}
		}

		$data = array(
			'post_title' => $title,
			'post_name' => $slug,
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_content' => '',
		);
		if ($existing) {
			$data['ID'] = $existing->ID;
			return (int) wp_update_post($data);
		}
		return (int) wp_insert_post($data);
	}

	private function get_elementor_data($post_id) {
		$raw = get_post_meta($post_id, '_elementor_data', true);
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			return is_array($decoded) ? $decoded : null;
		}
		return null;
	}

	private function write_elementor_meta($post_id, $content, $type = 'wp-page') {
		$json = wp_json_encode($content);
		if ($json === false) {
			$this->log('wp_json_encode FAIL post ' . $post_id);
			return false;
		}
		update_post_meta($post_id, '_elementor_edit_mode', 'builder');
		update_post_meta($post_id, '_elementor_template_type', $type);
		update_post_meta($post_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.21.0');
		update_post_meta($post_id, '_elementor_data', wp_slash($json));
		if (get_post_type($post_id) === 'page') {
			// Pro: Theme Builder header/footer → "Elementor Full Width" (theme header replaced by Pro location).
			// Free: header/footer embedded in the page → Canvas so the theme header does not duplicate ours.
			$template = class_exists('\\ElementorPro\\Plugin') ? 'elementor_header_footer' : 'elementor_canvas';
			update_post_meta($post_id, '_wp_page_template', $template);
		}
		delete_post_meta($post_id, '_elementor_element_cache');
		delete_post_meta($post_id, '_elementor_css');
		delete_post_meta($post_id, '_elementor_page_assets');
		return true;
	}

	private function apply_elementor_to_post($post_id, $content, $type = 'wp-page') {
		if (!$post_id || !is_array($content) || empty($content)) {
			return false;
		}
		// NO usar Document::save: en Hostinger vacía contenedores si el experimento no cargó en la misma request.
		$ok = $this->write_elementor_meta($post_id, $content, $type);

		if (class_exists('\\Elementor\\Core\\Files\\CSS\\Post')) {
			try {
				$css_file = new \Elementor\Core\Files\CSS\Post($post_id);
				$css_file->update();
			} catch (Exception $e) {
				$this->log('CSS Post warn #' . $post_id . ': ' . $e->getMessage());
			} catch (Throwable $e) {
				$this->log('CSS Post warn #' . $post_id . ': ' . $e->getMessage());
			}
		}
		return $ok;
	}

	private function upsert_elementor_library($title, $type, $content, $page_settings = array()) {
		$existing = null;
		$all = get_posts(array(
			'post_type' => 'elementor_library',
			'post_status' => 'any',
			'numberposts' => 200,
		));
		foreach ($all as $p) {
			if ($p->post_title === $title) {
				$existing = $p;
				break;
			}
		}

		$data = array(
			'post_title' => $title,
			'post_status' => 'publish',
			'post_type' => 'elementor_library',
		);
		if ($existing) {
			$data['ID'] = $existing->ID;
			$post_id = wp_update_post($data, true);
		} else {
			$post_id = wp_insert_post($data, true);
		}
		if (is_wp_error($post_id) || !$post_id) {
			return 0;
		}
		$elementor_type = $this->map_library_type($type);
		if (taxonomy_exists('elementor_library_type')) {
			wp_set_object_terms($post_id, $elementor_type, 'elementor_library_type');
		}
		$this->write_elementor_meta((int) $post_id, $content, $elementor_type);
		return (int) $post_id;
	}

	private function map_library_type($type) {
		$allowed = array('page', 'section', 'header', 'footer', 'single', 'archive', 'popup', 'container');
		$type = strtolower((string) $type);
		return in_array($type, $allowed, true) ? $type : 'page';
	}

	private function create_blog_posts() {
		$posts = array(
			array(
				'title' => 'Cómo leer la porosidad antes de formular',
				'content' => 'Una fibra porosa no responde igual a la misma fórmula. Aprende señales visuales y táctiles para ajustar el protocolo.',
				'image' => 'blog-porosidad.jpg',
				'category' => 'Tricología',
			),
			array(
				'title' => 'Fondos de aclaración sin miedo',
				'content' => 'Del amarillo al naranja: cómo elegir el reflejo corrector sin sobrecargar de pigmento.',
				'image' => 'blog-fondos.jpg',
				'category' => 'Color',
			),
			array(
				'title' => 'Cuándo tratar y cuándo derivar',
				'content' => 'Límites del tricólogo cosmético frente a alteraciones que requieren valoración médica.',
				'image' => 'fibra-brillo.jpg',
				'category' => 'Ética profesional',
			),
		);
		$count = 0;
		foreach ($posts as $p) {
			$existing_id = 0;
			foreach (get_posts(array('post_type' => 'post', 'post_status' => 'any', 'numberposts' => 50)) as $existing) {
				if ($existing->post_title === $p['title']) {
					$existing_id = (int) $existing->ID;
					break;
				}
			}
			if ($existing_id) {
				$this->assign_blog_category($existing_id, $p['category']);
				if (!has_post_thumbnail($existing_id) && !empty($this->media_map[$p['image']])) {
					set_post_thumbnail($existing_id, $this->media_map[$p['image']]);
				}
				continue;
			}
			$id = wp_insert_post(array(
				'post_title' => $p['title'],
				'post_content' => $p['content'],
				'post_status' => 'publish',
				'post_type' => 'post',
			));
			if ($id && !is_wp_error($id)) {
				if (!empty($this->media_map[$p['image']])) {
					set_post_thumbnail($id, $this->media_map[$p['image']]);
				}
				$this->assign_blog_category((int) $id, $p['category']);
				$count++;
			}
		}
		$this->log('Posts blog nuevos: ' . $count);
		return $count;
	}

	private function assign_blog_category($post_id, $name) {
		$post_id = (int) $post_id;
		$name = sanitize_text_field($name);
		if (!$post_id || $name === '') {
			return;
		}
		$term = term_exists($name, 'category');
		if (!$term) {
			$term = wp_insert_term($name, 'category');
		}
		if (is_wp_error($term)) {
			return;
		}
		$term_id = (int) (is_array($term) ? $term['term_id'] : $term);
		wp_set_post_categories($post_id, array($term_id), false);
	}

	private function create_menus() {
		$menu_name = 'AV Principal';
		$menu = wp_get_nav_menu_object($menu_name);
		if (!$menu) {
			$this->menu_id = (int) wp_create_nav_menu($menu_name);
		} else {
			$this->menu_id = (int) $menu->term_id;
			$items = wp_get_nav_menu_items($this->menu_id);
			if ($items) {
				foreach ($items as $item) {
					wp_delete_post($item->ID, true);
				}
			}
		}

		$order = array('Inicio', 'Cursos', 'Sobre nosotros', 'Servicios', 'Blog', 'Contacto');
		$i = 1;
		foreach ($order as $title) {
			if (empty($this->page_ids[$title])) {
				continue;
			}
			wp_update_nav_menu_item($this->menu_id, 0, array(
				'menu-item-title' => $title,
				'menu-item-object' => 'page',
				'menu-item-object-id' => $this->page_ids[$title],
				'menu-item-type' => 'post_type',
				'menu-item-status' => 'publish',
				'menu-item-position' => $i++,
			));
		}

		$legal_name = 'AV Legal';
		$legal = wp_get_nav_menu_object($legal_name);
		$legal_id = $legal ? (int) $legal->term_id : (int) wp_create_nav_menu($legal_name);
		if ($legal) {
			foreach (wp_get_nav_menu_items($legal_id) ?: array() as $item) {
				wp_delete_post($item->ID, true);
			}
		}
		$legal_pages = array('Aviso legal', 'Política de privacidad', 'Política de cookies', 'Política de reembolsos', 'Términos y condiciones');
		$j = 1;
		foreach ($legal_pages as $title) {
			if (empty($this->page_ids[$title])) {
				continue;
			}
			wp_update_nav_menu_item($legal_id, 0, array(
				'menu-item-title' => $title,
				'menu-item-object' => 'page',
				'menu-item-object-id' => $this->page_ids[$title],
				'menu-item-type' => 'post_type',
				'menu-item-status' => 'publish',
				'menu-item-position' => $j++,
			));
		}

		$locations = get_theme_mod('nav_menu_locations', array());
		if (!is_array($locations)) {
			$locations = array();
		}
		foreach (array('primary', 'menu-1', 'main_menu', 'header', 'footer') as $loc) {
			$locations[$loc] = ($loc === 'footer') ? $legal_id : $this->menu_id;
		}
		set_theme_mod('nav_menu_locations', $locations);
		$this->log('Menús asignados');
		return true;
	}

	private function configure_reading() {
		if (!empty($this->page_ids['Inicio'])) {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $this->page_ids['Inicio']);
		}
		if (!empty($this->page_ids['Blog'])) {
			update_option('page_for_posts', $this->page_ids['Blog']);
		}
		update_option('permalink_structure', '/%postname%/');
		$this->log('Reading: Inicio#' . ($this->page_ids['Inicio'] ?? 0) . ' Blog#' . ($this->page_ids['Blog'] ?? 0));
	}

	private function configure_site_identity() {
		update_option('blogname', 'AV Studio Academy');
		update_option('blogdescription', 'Formación en tricología cosmética, color y química capilar');
		if (!empty($this->media_map['monograma-av-oro.png'])) {
			set_theme_mod('custom_logo', $this->media_map['monograma-av-oro.png']);
			update_option('site_icon', $this->media_map['monograma-av-oro.png']);
		}
	}

	private function assign_header_footer() {
		$header_id = isset($this->template_ids['AV Header']) ? $this->template_ids['AV Header'] : 0;
		$footer_id = isset($this->template_ids['AV Footer']) ? $this->template_ids['AV Footer'] : 0;

		if (class_exists('\\ElementorPro\\Plugin') && $header_id && $footer_id) {
			// 1) Retirar condiciones de OTROS header/footer del Theme Builder (p. ej. plantillas de un sitio anterior)
			//    si no, Pro sigue mostrando el header antiguo porque lee su caché de condiciones, no el meta.
			$others = get_posts(array(
				'post_type' => 'elementor_library',
				'post_status' => 'any',
				'numberposts' => 200,
				'post__not_in' => array($header_id, $footer_id),
				'meta_query' => array(
					array('key' => '_elementor_template_type', 'value' => array('header', 'footer'), 'compare' => 'IN'),
				),
			));
			foreach ($others as $other) {
				$had = get_post_meta($other->ID, '_elementor_conditions', true);
				if (!empty($had)) {
					$how = $this->set_pro_conditions($other->ID, array());
					$this->log("Pro: condiciones retiradas de '{$other->post_title}' #{$other->ID} ({$how})");
				}
			}

			// 2) Nuestro header/footer → Entire Site (API de Pro + caché)
			$h = $this->set_pro_conditions($header_id, array('include/general'));
			$f = $this->set_pro_conditions($footer_id, array('include/general'));
			update_option('avsi_pro_chrome_ids', array('header' => $header_id, 'footer' => $footer_id), false);
			$this->log("Pro: Header #$header_id ($h) / Footer #$footer_id ($f) → Entire Site");

			$cache = get_option('elementor_pro_theme_builder_conditions', array());
			$this->log('Pro: caché condiciones header=' . wp_json_encode(isset($cache['header']) ? $cache['header'] : null) . ' footer=' . wp_json_encode(isset($cache['footer']) ? $cache['footer'] : null));

			// 3) Aviso si "Elementor Header & Footer Builder" (EHF) tiene plantillas activas que compitan
			if (post_type_exists('elementor-hf')) {
				$ehf = get_posts(array('post_type' => 'elementor-hf', 'post_status' => 'publish', 'numberposts' => 20));
				foreach ($ehf as $p) {
					$this->log("WARN: plugin EHF tiene plantilla '{$p->post_title}' #{$p->ID}; si duplica header/footer, desactívala o borra el plugin EHF");
				}
			}
			return;
		}
		if ($header_id && $footer_id) {
			update_option('ehf_settings', array(
				'type_header' => $header_id,
				'type_footer' => $footer_id,
			));
			delete_option('avsi_pro_chrome_ids');
			$this->log('Free: header/footer embebidos (plantilla Canvas) + EHF option');
		}
	}

	/**
	 * Guarda condiciones del Theme Builder de Elementor Pro.
	 * Usa la API de Pro (actualiza la caché `elementor_pro_theme_builder_conditions`) y, en cualquier caso,
	 * parchea la caché a mano para que el header/footer se muestre aunque la API cambie.
	 */
	private function set_pro_conditions($post_id, array $conditions) {
		$post_id = (int) $post_id;
		$how = 'meta';
		try {
			if (class_exists('\\ElementorPro\\Modules\\ThemeBuilder\\Module')) {
				$module = \ElementorPro\Modules\ThemeBuilder\Module::instance();
				if ($module && method_exists($module, 'get_conditions_manager')) {
					$manager = $module->get_conditions_manager();
					if ($manager && method_exists($manager, 'save_conditions')) {
						$manager->save_conditions($post_id, $conditions);
						$how = 'api';
					}
				}
			}
		} catch (Exception $e) {
			$this->log('Pro conditions API warn #' . $post_id . ': ' . $e->getMessage());
		} catch (Throwable $e) {
			$this->log('Pro conditions API warn #' . $post_id . ': ' . $e->getMessage());
		}

		if (empty($conditions)) {
			delete_post_meta($post_id, '_elementor_conditions');
		} else {
			update_post_meta($post_id, '_elementor_conditions', $conditions);
		}

		// Parche manual de la caché (idempotente)
		$location = get_post_meta($post_id, '_elementor_template_type', true);
		if (in_array($location, array('header', 'footer', 'single', 'archive'), true)) {
			$cache = get_option('elementor_pro_theme_builder_conditions', array());
			if (!is_array($cache)) {
				$cache = array();
			}
			if (!isset($cache[$location]) || !is_array($cache[$location])) {
				$cache[$location] = array();
			}
			if (empty($conditions)) {
				unset($cache[$location][$post_id]);
			} else {
				$cache[$location][$post_id] = $conditions;
			}
			update_option('elementor_pro_theme_builder_conditions', $cache);
			$how .= '+cache';
		}
		return $how;
	}

	private function count_widgets($elements) {
		$n = 0;
		foreach ((array) $elements as $el) {
			if (!empty($el['elType']) && $el['elType'] === 'widget') {
				$n++;
			}
			if (!empty($el['elements'])) {
				$n += $this->count_widgets($el['elements']);
			}
		}
		return $n;
	}

	/**
	 * Elementor Free: sustituye widgets Pro (nav-menu, form) por equivalentes Free.
	 */
	private function adapt_for_free($elements) {
		foreach ($elements as &$el) {
			if (!empty($el['widgetType']) && $el['widgetType'] === 'nav-menu') {
				$links = array();
				foreach (array('Inicio' => '/', 'Cursos' => '/cursos/', 'Sobre nosotros' => '/sobre-nosotros/', 'Servicios' => '/servicios/', 'Blog' => '/blog/', 'Contacto' => '/contacto/') as $label => $url) {
					$links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
				}
				$el['widgetType'] = 'text-editor';
				$el['settings'] = array(
					'editor' => '<p class="av-nav-fallback">' . implode(' ', $links) . '</p>',
					'align' => 'center',
					'text_color' => 'rgba(255,255,255,0.88)',
					'typography_typography' => 'custom',
					'typography_font_family' => 'Manrope',
					'typography_font_size' => array('unit' => 'px', 'size' => 12, 'sizes' => array()),
					'typography_font_weight' => '500',
					'typography_letter_spacing' => array('unit' => 'em', 'size' => 0.12, 'sizes' => array()),
					'typography_text_transform' => 'uppercase',
					'_flex_size' => 'grow',
					'_element_width' => 'auto',
					'css_classes' => 'av-nav av-nav--fallback',
					'_css_classes' => 'av-nav av-nav--fallback',
				);
			} elseif (!empty($el['widgetType']) && $el['widgetType'] === 'form') {
				$el['widgetType'] = 'text-editor';
				$el['settings'] = array(
					'editor' => '<p>Escríbenos a <a href="mailto:cervolallc@gmail.com"><strong>cervolallc@gmail.com</strong></a> o al <a href="tel:+16785371003"><strong>+1 (678) 537-1003</strong></a> indicando tu nombre, el curso de interés y tu mensaje. Te respondemos en menos de 48 horas.</p><p>Instagram: <a href="https://instagram.com/studioamevillasmil" target="_blank" rel="noopener">@studioamevillasmil</a></p><p><em>Con Elementor Pro este bloque se convierte en un formulario conectado por email.</em></p>',
					'text_color' => '#7a736a',
					'typography_typography' => 'custom',
					'typography_font_family' => 'Manrope',
					'typography_font_size' => array('unit' => 'px', 'size' => 17, 'sizes' => array()),
				);
			}
			if (!empty($el['elements']) && is_array($el['elements'])) {
				$el['elements'] = $this->adapt_for_free($el['elements']);
			}
		}
		return $elements;
	}

	private function embed_header_footer($content) {
		$header = !empty($this->template_content['AV Header']) ? $this->template_content['AV Header'] : array();
		$footer = !empty($this->template_content['AV Footer']) ? $this->template_content['AV Footer'] : array();
		if (!$header && !empty($this->template_ids['AV Header'])) {
			$header = $this->get_elementor_data($this->template_ids['AV Header']) ?: array();
		}
		if (!$footer && !empty($this->template_ids['AV Footer'])) {
			$footer = $this->get_elementor_data($this->template_ids['AV Footer']) ?: array();
		}
		return array_values(array_merge($header, $content, $footer));
	}

	/* ------------------------------------------------------------------
	 * Builders PHP (fallback / páginas legales): hero oscuro + tarjeta de prosa
	 * ------------------------------------------------------------------ */
	private function builder_id($kind, $n) {
		return substr(md5('avsi-' . $kind . '-' . $n), 0, 7);
	}

	private function builder_typo($family, $size, $extra = array()) {
		$t = array(
			'typography_typography' => 'custom',
			'typography_font_family' => $family,
			'typography_font_size' => array('unit' => 'px', 'size' => $size, 'sizes' => array()),
		);
		return array_merge($t, $extra);
	}

	private function builder_media($filename) {
		$url = isset($this->url_map[$filename]) ? $this->url_map[$filename] : '';
		$id = isset($this->media_map[$filename]) ? (int) $this->media_map[$filename] : '';
		if (!$url) {
			foreach (array('legal-fondo.jpg', 'sobre-nosotros-hero.jpg', 'herramientas.jpg') as $alt) {
				if (!empty($this->url_map[$alt])) {
					$url = $this->url_map[$alt];
					$id = (int) $this->media_map[$alt];
					break;
				}
			}
		}
		return array('url' => $url, 'id' => $id, 'source' => 'library');
	}

	private function build_simple_page_content($kind, $title) {
		$copy = $this->page_copy($kind, $title);
		$k = $kind;
		$hero = array(
			'id' => $this->builder_id($k, 1),
			'elType' => 'container',
			'isInner' => false,
			'settings' => array(
				'content_width' => 'boxed',
				'boxed_width' => array('unit' => 'px', 'size' => 1180, 'sizes' => array()),
				'flex_direction' => 'column',
				'flex_justify_content' => 'flex-end',
				'flex_gap' => array('column' => '14', 'row' => '14', 'isLinked' => true, 'unit' => 'px', 'size' => 14),
				'min_height' => array('unit' => 'vh', 'size' => 46, 'sizes' => array()),
				'min_height_mobile' => array('unit' => 'vh', 'size' => 52, 'sizes' => array()),
				'background_background' => 'classic',
				'background_image' => $this->builder_media($copy['img']),
				'background_position' => 'center center',
				'background_size' => 'cover',
				'background_overlay_background' => 'gradient',
				'background_overlay_color' => 'rgba(12,10,8,0.40)',
				'background_overlay_color_b' => 'rgba(12,10,8,0.78)',
				'background_overlay_gradient_type' => 'linear',
				'background_overlay_gradient_angle' => array('unit' => 'deg', 'size' => 180, 'sizes' => array()),
				'background_overlay_opacity' => array('unit' => 'px', 'size' => 1, 'sizes' => array()),
				'padding' => array('unit' => 'px', 'top' => '170', 'right' => '24', 'bottom' => '64', 'left' => '24', 'isLinked' => false),
				'padding_mobile' => array('unit' => 'px', 'top' => '130', 'right' => '18', 'bottom' => '44', 'left' => '18', 'isLinked' => false),
				'border_radius' => array('unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '32', 'left' => '32', 'isLinked' => false),
				'html_tag' => 'section',
				'css_classes' => 'av-page-hero',
				'_css_classes' => 'av-page-hero',
			),
			'elements' => array(
				array(
					'id' => $this->builder_id($k, 2),
					'elType' => 'widget',
					'widgetType' => 'heading',
					'settings' => array_merge(array(
						'title' => $copy['eyebrow'],
						'header_size' => 'div',
						'title_color' => '#E2D0A4',
						'css_classes' => 'av-eyebrow',
						'_css_classes' => 'av-eyebrow',
					), $this->builder_typo('Manrope', 12, array(
						'typography_font_weight' => '600',
						'typography_letter_spacing' => array('unit' => 'em', 'size' => 0.26, 'sizes' => array()),
						'typography_text_transform' => 'uppercase',
					))),
					'elements' => array(),
				),
				array(
					'id' => $this->builder_id($k, 3),
					'elType' => 'widget',
					'widgetType' => 'heading',
					'settings' => array_merge(array(
						'title' => $copy['h1'],
						'header_size' => 'h1',
						'title_color' => '#FFFFFF',
						'css_classes' => 'av-hero-title',
						'_css_classes' => 'av-hero-title',
					), $this->builder_typo('Cormorant Garamond', 60, array(
						'typography_font_size_tablet' => array('unit' => 'px', 'size' => 48, 'sizes' => array()),
						'typography_font_size_mobile' => array('unit' => 'px', 'size' => 36, 'sizes' => array()),
						'typography_font_weight' => '500',
						'typography_line_height' => array('unit' => 'em', 'size' => 1.1, 'sizes' => array()),
					))),
					'elements' => array(),
				),
				array(
					'id' => $this->builder_id($k, 4),
					'elType' => 'widget',
					'widgetType' => 'text-editor',
					'settings' => array_merge(array(
						'editor' => '<p>' . $copy['intro'] . '</p>',
						'text_color' => 'rgba(255,255,255,0.8)',
					), $this->builder_typo('Manrope', 18, array(
						'typography_line_height' => array('unit' => 'em', 'size' => 1.6, 'sizes' => array()),
					))),
					'elements' => array(),
				),
			),
		);

		$card_children = array();
		$n = 10;
		foreach ($copy['sections'] as $section) {
			$card_children[] = array(
				'id' => $this->builder_id($k, $n++),
				'elType' => 'widget',
				'widgetType' => 'heading',
				'settings' => array_merge(array(
					'title' => $section[0],
					'header_size' => 'h2',
					'title_color' => '#12100E',
				), $this->builder_typo('Cormorant Garamond', 32, array(
					'typography_font_weight' => '500',
					'typography_line_height' => array('unit' => 'em', 'size' => 1.15, 'sizes' => array()),
					'_margin' => array('unit' => 'px', 'top' => '14', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false),
				))),
				'elements' => array(),
			);
			$card_children[] = array(
				'id' => $this->builder_id($k, $n++),
				'elType' => 'widget',
				'widgetType' => 'text-editor',
				'settings' => array_merge(array(
					'editor' => $section[1],
					'text_color' => '#5f584f',
					'css_classes' => 'av-prose-text',
					'_css_classes' => 'av-prose-text',
				), $this->builder_typo('Manrope', 16.5, array(
					'typography_line_height' => array('unit' => 'em', 'size' => 1.75, 'sizes' => array()),
				))),
				'elements' => array(),
			);
		}
		if (!empty($copy['note'])) {
			$card_children[] = array(
				'id' => $this->builder_id($k, $n++),
				'elType' => 'widget',
				'widgetType' => 'text-editor',
				'settings' => array_merge(array(
					'editor' => '<p><em>' . $copy['note'] . '</em></p>',
					'text_color' => '#9a7a3c',
				), $this->builder_typo('Manrope', 13, array())),
				'elements' => array(),
			);
		}

		$card = array(
			'id' => $this->builder_id($k, 6),
			'elType' => 'container',
			'isInner' => true,
			'settings' => array(
				'content_width' => 'full',
				'width' => array('unit' => '%', 'size' => 100, 'sizes' => array()),
				'flex_direction' => 'column',
				'flex_gap' => array('column' => '12', 'row' => '12', 'isLinked' => true, 'unit' => 'px', 'size' => 12),
				'padding' => array('unit' => 'px', 'top' => '56', 'right' => '56', 'bottom' => '56', 'left' => '56', 'isLinked' => true),
				'padding_mobile' => array('unit' => 'px', 'top' => '28', 'right' => '22', 'bottom' => '28', 'left' => '22', 'isLinked' => false),
				'background_background' => 'classic',
				'background_color' => '#FFFCF8',
				'border_border' => 'solid',
				'border_width' => array('unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true),
				'border_color' => 'rgba(18,16,14,0.08)',
				'border_radius' => array('unit' => 'px', 'top' => '28', 'right' => '28', 'bottom' => '28', 'left' => '28', 'isLinked' => true),
				'box_shadow_box_shadow_type' => 'yes',
				'box_shadow_box_shadow' => array('horizontal' => 0, 'vertical' => 8, 'blur' => 28, 'spread' => 0, 'color' => 'rgba(18,16,14,0.05)'),
				'css_classes' => 'av-card av-prose',
				'_css_classes' => 'av-card av-prose',
			),
			'elements' => $card_children,
		);

		$body = array(
			'id' => $this->builder_id($k, 5),
			'elType' => 'container',
			'isInner' => false,
			'settings' => array(
				'content_width' => 'boxed',
				'boxed_width' => array('unit' => 'px', 'size' => 880, 'sizes' => array()),
				'flex_direction' => 'column',
				'padding' => array('unit' => 'px', 'top' => '88', 'right' => '24', 'bottom' => '112', 'left' => '24', 'isLinked' => false),
				'padding_mobile' => array('unit' => 'px', 'top' => '56', 'right' => '18', 'bottom' => '72', 'left' => '18', 'isLinked' => false),
				'html_tag' => 'section',
				'css_classes' => 'av-section av-section--prose',
				'_css_classes' => 'av-section av-section--prose',
			),
			'elements' => array($card),
		);

		return array($hero, $body);
	}

	private function page_copy($kind, $title) {
		$mail = '<a href="mailto:cervolallc@gmail.com">cervolallc@gmail.com</a>';
		$phone = '<a href="tel:+16785371003">+1 (678) 537-1003</a>';
		$address = '2286 Waycross Ln, Dacula, GA 30019, Estados Unidos';
		$updated = 'Última actualización: octubre 2026.';
		$copies = array(
			'about' => array(
				'img' => 'sobre-nosotros-hero.jpg',
				'eyebrow' => 'Sobre nosotros',
				'h1' => 'América Villasmil y una academia con identidad propia',
				'intro' => 'Conocimiento que transforma la práctica. De estilistas a tricólogos cosméticos.',
				'sections' => array(
					array('La fundadora', '<p>AV Studio nace de una historia familiar ligada a la peluquería durante más de 30 años. América Villasmil creció en un salón de belleza y construyó su trayectoria con más de 20 años de experiencia, formándose junto a marcas y academias como L’Oréal, Wella, Davines y Pivot Point.</p><p>De esa evolución nace <strong>AV Studio Academy</strong>: una academia online para quienes empiezan desde cero y para estilistas que buscan actualizarse con tricología cosmética, química capilar, color y diagnóstico profesional. Más de 200 personas ya se han capacitado con este enfoque.</p>'),
					array('Propósito', '<p><strong>Misión:</strong> brindar formación online profesional, accesible y estructurada —integrando experiencia de salón, tricología cosmética, química capilar, color y diagnóstico— para que cada estudiante comprenda qué hacer, por qué, cuándo y hasta dónde llegar.</p><p><strong>Visión:</strong> convertir a AV Studio Academy en una academia de referencia internacional que eleva el nivel profesional de la belleza con conocimiento, personalización y educación continua.</p><p><strong>Valores:</strong> amor, profesionalidad, ética, compromiso, conocimiento, seguridad, individualidad, evolución, empoderamiento y excelencia.</p>'),
				),
				'note' => '',
			),
			'services' => array(
				'img' => 'salon-interior.jpg',
				'eyebrow' => 'Servicios',
				'h1' => 'Academia digital y excelencia de salón',
				'intro' => 'Formación online con criterio científico y la mirada real de salón de AV Studio.',
				'sections' => array(
					array('Academia', '<ul><li>Cursos online en video con módulos y recursos descargables.</li><li>Rutas de aprendizaje desde fundamentos hasta corrección avanzada.</li><li>Orientación académica según nivel y objetivos.</li></ul>'),
					array('Salón AV Studio', '<ul><li>Coloración: canas, mechas, babylights y color personalizado.</li><li>Tratamientos: botox orgánico, nutrición, reparación y anticaída.</li><li>Alisados, acabados y belleza integral.</li></ul>'),
				),
				'note' => '',
			),
			'legal_aviso' => array(
				'img' => 'legal-fondo.jpg',
				'eyebrow' => 'Legal',
				'h1' => 'Aviso legal',
				'intro' => 'Información sobre la titularidad del sitio y las condiciones generales de uso.',
				'sections' => array(
					array('1. Datos identificativos', '<p>El sitio web <strong>abstudioacademy.com</strong> es operado por <strong>AV Studio Academy / América Villasmil</strong> (en adelante, “la Academia”), con domicilio en ' . $address . ', teléfono ' . $phone . ' y correo de contacto ' . $mail . '.</p>'),
					array('2. Objeto', '<p>El presente aviso regula el acceso y uso del sitio web, así como de los contenidos formativos, informativos y comerciales publicados en él.</p>'),
					array('3. Condiciones de uso', '<p>El usuario se compromete a utilizar el sitio de forma lícita, respetuosa y conforme a la buena fe. Queda prohibido el uso del sitio para fines ilícitos, lesivos de derechos de terceros o que puedan dañar la imagen o el funcionamiento de la Academia.</p>'),
					array('4. Propiedad intelectual', '<p>Los textos, diseños, logotipos, materiales de cursos, imágenes, vídeos y demás contenidos son titularidad de la Academia o de terceros licenciantes. Queda prohibida su reproducción, distribución o comunicación pública sin autorización expresa.</p>'),
					array('5. Exclusión de responsabilidad', '<p>La Academia procura la exactitud de la información publicada, sin garantizar la ausencia total de errores tipográficos o de actualización. Los contenidos formativos tienen carácter educativo/cosmético y no sustituyen asesoramiento médico.</p>'),
					array('6. Enlaces', '<p>Los enlaces a sitios de terceros se facilitan para conveniencia del usuario. La Academia no responde del contenido ni de las políticas de dichos sitios.</p>'),
					array('7. Legislación aplicable', '<p>El presente aviso se rige por las leyes aplicables del Estado de Georgia y de los Estados Unidos. Para cualquier controversia, las partes se someten a los tribunales competentes conforme a derecho.</p><p>' . $updated . '</p>'),
				),
				'note' => 'Documento modelo para revisión legal antes de publicar. Completar con razón social / EIN definitivos si aplica.',
			),
			'legal_privacidad' => array(
				'img' => 'legal-fondo.jpg',
				'eyebrow' => 'Legal',
				'h1' => 'Política de privacidad y tratamiento de datos personales',
				'intro' => 'Cómo recopilamos, usamos y protegemos tus datos personales.',
				'sections' => array(
					array('1. Responsable del tratamiento', '<p><strong>AV Studio Academy / América Villasmil</strong><br>Dirección: ' . $address . '<br>Teléfono: ' . $phone . '<br>Email: ' . $mail . '<br>Web: abstudioacademy.com<br>Instagram: <a href="https://instagram.com/studioamevillasmil" target="_blank" rel="noopener">@studioamevillasmil</a></p>'),
					array('2. Datos que recopilamos', '<ul><li>Datos de identificación y contacto (nombre, email, teléfono).</li><li>Datos de compra / matrícula de cursos (cuando aplique).</li><li>Datos de navegación mediante cookies (ver Política de cookies).</li><li>Mensajes enviados a través de formularios.</li></ul>'),
					array('3. Finalidades', '<ul><li>Atender solicitudes de información y soporte.</li><li>Gestionar inscripciones, accesos a cursos y facturación.</li><li>Enviar comunicaciones comerciales solo si el usuario lo autoriza.</li><li>Mejorar la experiencia del sitio y medir rendimiento.</li></ul>'),
					array('4. Base de legitimación', '<p>Consentimiento del titular, ejecución de relación contractual/precontractual e interés legítimo en la mejora del servicio, según corresponda.</p>'),
					array('5. Conservación', '<p>Los datos se conservarán mientras exista la relación o el consentimiento, y durante los plazos legales de obligaciones fiscales o reclamaciones.</p>'),
					array('6. Destinatarios', '<p>Podrán acceder proveedores tecnológicos necesarios (hosting, email SMTP, pasarela de pagos, herramientas de analítica o CRM), bajo obligaciones de confidencialidad. No se venden datos personales.</p>'),
					array('7. Derechos', '<p>El titular puede solicitar acceso, rectificación, cancelación, oposición y demás derechos reconocidos por la normativa vigente escribiendo a ' . $mail . '.</p>'),
					array('8. Seguridad', '<p>Se aplican medidas técnicas y organizativas razonables para proteger los datos frente a acceso no autorizado, pérdida o alteración.</p>'),
					array('9. Menores', '<p>Los servicios formativos están dirigidos a adultos. Si se detectan datos de menores sin autorización, se procederá a eliminarlos.</p><p>' . $updated . '</p>'),
				),
				'note' => 'Modelo orientativo. Validar con asesoría legal según jurisdicción (Georgia / EE. UU. y usuarios internacionales).',
			),
			'legal_cookies' => array(
				'img' => 'legal-fondo.jpg',
				'eyebrow' => 'Legal',
				'h1' => 'Política de cookies',
				'intro' => 'Qué cookies utiliza este sitio y cómo puedes gestionarlas.',
				'sections' => array(
					array('1. ¿Qué son las cookies?', '<p>Las cookies son pequeños archivos que el sitio puede almacenar en el dispositivo del usuario para recordar preferencias, mantener sesiones o medir el uso del sitio.</p>'),
					array('2. Tipos de cookies que podemos utilizar', '<ul><li><strong>Esenciales:</strong> necesarias para el funcionamiento (sesión, seguridad, carrito/matrícula).</li><li><strong>Preferencias:</strong> recuerdan opciones del usuario.</li><li><strong>Analíticas:</strong> ayudan a entender tráfico y rendimiento.</li><li><strong>Marketing:</strong> solo si se activan campañas (p. ej. Meta Pixel), con consentimiento previo cuando corresponda.</li></ul>'),
					array('3. Gestión del consentimiento', '<p>El sitio puede mostrar un aviso de cookies que permite aceptar, rechazar o configurar categorías no esenciales.</p>'),
					array('4. Cómo desactivar cookies', '<p>El usuario puede configurar su navegador para bloquear o eliminar cookies. Algunas funciones del sitio podrían verse limitadas.</p>'),
					array('5. Actualizaciones', '<p>Esta política puede modificarse para reflejar cambios técnicos o legales. La fecha de actualización se indicará en esta página.</p><p>Contacto: ' . $mail . '</p><p>' . $updated . '</p>'),
				),
				'note' => '',
			),
			'legal_reembolsos' => array(
				'img' => 'legal-fondo.jpg',
				'eyebrow' => 'Legal',
				'h1' => 'Política de reembolsos',
				'intro' => 'Condiciones de devolución para cursos online y productos digitales.',
				'sections' => array(
					array('1. Alcance', '<p>Esta política aplica a la compra de cursos online y productos digitales de AV Studio Academy a través de abstudioacademy.com.</p>'),
					array('2. Naturaleza del producto', '<p>Los cursos son contenidos digitales de acceso inmediato o programado. Una vez entregado el acceso, el producto se considera de carácter intangible.</p>'),
					array('3. Derecho a reembolso', '<ul><li>Solicitudes dentro de <strong>7 días corridos</strong> desde la compra, siempre que el alumno no haya consumido más del 20% del contenido del curso.</li><li>Fallos técnicos imputables a la plataforma que impidan el acceso y no se resuelvan en un plazo razonable.</li><li>Cobros duplicados demostrables.</li></ul>'),
					array('4. No reembolsable', '<ul><li>Cursos mayoritariamente consumidos o descargados.</li><li>Insatisfacción subjetiva no vinculada a un defecto del servicio.</li><li>Promociones, bonos o accesos regalados, salvo indicación expresa.</li></ul>'),
					array('5. Cómo solicitarlo', '<p>Escribir a ' . $mail . ' indicando nombre, email de compra, curso y motivo. La Academia responderá en un plazo estimado de 5 días hábiles.</p>'),
					array('6. Método de devolución', '<p>Si corresponde, el reembolso se realizará por el mismo medio de pago u otro acordado, en los plazos que permita la pasarela financiera.</p>'),
					array('7. Servicios de salón', '<p>Los abonos y reservas de servicios presenciales de AV Studio se rigen por las condiciones informadas al agendar y pueden no ser reembolsables si se incumplen políticas de cancelación.</p><p>' . $updated . '</p>'),
				),
				'note' => 'Política modelo para productos digitales formativos. Ajustar plazos y excepciones antes del lanzamiento comercial.',
			),
			'legal_terminos' => array(
				'img' => 'legal-fondo.jpg',
				'eyebrow' => 'Legal',
				'h1' => 'Términos y condiciones',
				'intro' => 'Reglas de uso del sitio y de los servicios formativos de la Academia.',
				'sections' => array(
					array('1. Aceptación', '<p>Al acceder a abstudioacademy.com o adquirir un curso, el usuario acepta estos términos, la Política de privacidad, la Política de cookies y la Política de reembolsos.</p>'),
					array('2. Servicios', '<p>AV Studio Academy ofrece formación online en belleza, tricología cosmética, color y química capilar, así como información sobre servicios vinculados a la marca. El acceso a cursos puede requerir cuenta de usuario y pago previo.</p>'),
					array('3. Cuenta y acceso', '<ul><li>El alumno es responsable de la confidencialidad de sus credenciales.</li><li>El acceso es personal e intransferible, salvo autorización escrita.</li><li>Queda prohibido compartir, revender o redistribuir materiales del curso.</li></ul>'),
					array('4. Precios y pagos', '<p>Los precios se mostrarán en el sitio o en la propuesta comercial vigente. La Academia podrá modificar precios para nuevas compras sin afectar compras ya confirmadas.</p>'),
					array('5. Uso educativo y límites profesionales', '<p>Los contenidos tienen finalidad formativa y cosmética. No constituyen diagnóstico médico ni sustituyen atención de salud. El profesional debe respetar los límites de su actuación y derivar cuando corresponda.</p>'),
					array('6. Propiedad intelectual', '<p>Todo el material didáctico, branding y contenidos del sitio pertenecen a la Academia o a sus licenciantes. El alumno recibe una licencia de uso limitada para su aprendizaje personal/profesional, no una cesión de derechos.</p>'),
					array('7. Conducta', '<p>Se espera un uso respetuoso de foros, comentarios o canales de soporte. La Academia podrá suspender accesos ante usos abusivos o ilícitos.</p>'),
					array('8. Disponibilidad', '<p>Se busca mantener el sitio operativo, sin garantizar disponibilidad ininterrumpida. Podrán realizarse mantenimientos programados o de emergencia.</p>'),
					array('9. Modificaciones', '<p>Estos términos pueden actualizarse. La versión vigente será la publicada en esta página.</p>'),
					array('10. Contacto', '<p>' . $mail . '</p><p>' . $updated . '</p>'),
				),
				'note' => '',
			),
			'generic' => array(
				'img' => 'legal-fondo.jpg',
				'eyebrow' => 'AV Studio Academy',
				'h1' => $title,
				'intro' => 'Página editable con Elementor.',
				'sections' => array(
					array($title, '<p>Contenido editable con Elementor. Duplica esta tarjeta para añadir más secciones.</p>'),
				),
				'note' => '',
			),
		);
		return isset($copies[$kind]) ? $copies[$kind] : $copies['generic'];
	}

	private function replace_media_tokens($json) {
		$json = preg_replace(
			'/__AV_MEDIA__\/([A-Za-z0-9._\-]+\.(?:jpg|jpeg|png|webp))[^"\\\\]*/i',
			'__AV_MEDIA__/$1',
			$json
		);
		return preg_replace_callback('/__AV_MEDIA__\/([A-Za-z0-9._\-]+\.(?:jpg|jpeg|png|webp))/i', function ($m) {
			$filename = $m[1];
			return !empty($this->url_map[$filename]) ? $this->url_map[$filename] : $m[0];
		}, $json);
	}

	private function hydrate_elementor_media($elements) {
		if (!is_array($elements)) {
			return $elements;
		}
		foreach ($elements as &$el) {
			if (!empty($el['settings']) && is_array($el['settings'])) {
				$el['settings'] = $this->hydrate_settings_media($el['settings']);
			}
			if (!empty($el['elements']) && is_array($el['elements'])) {
				$el['elements'] = $this->hydrate_elementor_media($el['elements']);
			}
		}
		return $elements;
	}

	private function hydrate_settings_media($settings) {
		foreach (array('image', 'background_image', 'background_overlay_image') as $key) {
			if (empty($settings[$key]) || !is_array($settings[$key])) {
				continue;
			}
			$url = isset($settings[$key]['url']) ? (string) $settings[$key]['url'] : '';
			if (strpos($url, '__AV_MEDIA__/') === 0) {
				$filename = basename(preg_replace('/[?#].*$/', '', $url));
				if (!empty($this->url_map[$filename])) {
					$url = $this->url_map[$filename];
					$settings[$key]['url'] = $url;
				}
			}
			if ($url === '') {
				continue;
			}
			$id = $this->attachment_id_from_url($url);
			if (!$id) {
				$filename = basename(preg_replace('/[?#].*$/', '', $url));
				if (!empty($this->media_map[$filename])) {
					$id = (int) $this->media_map[$filename];
					$url = $this->url_map[$filename];
				}
			}
			if ($id) {
				$settings[$key]['id'] = $id;
				$settings[$key]['url'] = $url ? $url : wp_get_attachment_url($id);
				$settings[$key]['source'] = 'library';
			}
		}
		return $settings;
	}

	private function attachment_id_from_url($url) {
		foreach ($this->url_map as $filename => $mapped_url) {
			if ($mapped_url === $url && isset($this->media_map[$filename])) {
				return (int) $this->media_map[$filename];
			}
		}
		return function_exists('attachment_url_to_postid') ? (int) attachment_url_to_postid($url) : 0;
	}

	private function maybe_save_custom_css() {
		$css_file = AVSI_PATH . 'assets/elementor-custom.css';
		if (!file_exists($css_file)) {
			return;
		}
		$css = file_get_contents($css_file);
		update_option('avsi_custom_css', $css);
		$kit_id = (int) get_option('elementor_active_kit');
		if ($kit_id) {
			$meta = get_post_meta($kit_id, '_elementor_page_settings', true);
			if (!is_array($meta)) {
				$meta = array();
			}
			$existing = isset($meta['custom_css']) ? (string) $meta['custom_css'] : '';
			if (strpos($existing, 'AV Studio Academy — Custom CSS') === false) {
				$meta['custom_css'] = trim($existing . "\n\n" . $css);
				update_post_meta($kit_id, '_elementor_page_settings', $meta);
				$this->log('CSS en kit Elementor #' . $kit_id);
			}
		}
	}

	private function flush_elementor_css() {
		if (class_exists('\\Elementor\\Plugin')) {
			try {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
				$this->log('Caché Elementor limpiada');
			} catch (Exception $e) {
				// ignore
			}
		}
		if (function_exists('wp_cache_flush')) {
			wp_cache_flush();
		}
	}
}
