<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Importador genérico: todo sale de payload/site.json + media + templates.
 */
class SM_Importer {
	private $site = array();
	private $media_map = array();
	private $url_map = array();
	private $template_ids = array();
	private $template_content = array();
	private $page_ids = array();
	private $log_lines = array();
	private $media_token = '__SITE_MEDIA__';

	public function get_log_lines() {
		return $this->log_lines;
	}

	private function log($msg) {
		$this->log_lines[] = '[' . gmdate('H:i:s') . '] ' . $msg;
	}

	private function persist_log() {
		update_option('sm_last_log', $this->log_lines, false);
		$upload = wp_upload_dir();
		if (empty($upload['error'])) {
			file_put_contents(
				trailingslashit($upload['basedir']) . 'sm-import-log.txt',
				implode("\n", $this->log_lines) . "\n"
			);
		}
	}

	public function run_full_site() {
		if (!$this->load_site_json()) {
			$this->persist_log();
			return array(
				'images' => 0,
				'templates' => 0,
				'pages' => 0,
				'posts' => 0,
				'runtime' => false,
				'log' => $this->log_lines,
			);
		}

		$this->log('=== Site Maestro ' . SM_VERSION . ' ===');
		$this->log('Proyecto: ' . ($this->site['project_id'] ?? '?'));
		$this->log('Marca: ' . ($this->site['brand']['name'] ?? '?'));
		$this->log('Archetype: ' . ($this->site['experience']['archetype'] ?? '?'));
		$this->log('Elementor: ' . (defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'NO'));
		$this->log('Elementor Pro: ' . (class_exists('\\ElementorPro\\Plugin') ? 'SI' : 'NO'));

		$this->bootstrap_wp_files();
		$this->ensure_elementor_ready();

		$images = $this->import_images();
		$templates = $this->import_templates();
		$pages = $this->create_pages();
		$posts = $this->create_posts();
		$this->create_menus();
		$this->configure_reading();
		$this->configure_site_identity();
		$this->assign_header_footer();
		$runtime = $this->deploy_runtime();
		$this->flush_elementor_css();
		flush_rewrite_rules(false);

		$this->log('=== FIN ===');
		$this->persist_log();

		return array(
			'images' => $images,
			'templates' => $templates,
			'pages' => $pages,
			'posts' => $posts,
			'runtime' => $runtime,
			'log' => $this->log_lines,
		);
	}

	private function load_site_json() {
		$path = SM_PAYLOAD . 'site.json';
		if (!is_readable($path)) {
			$this->log('ERROR: falta payload/site.json');
			return false;
		}
		$raw = file_get_contents($path);
		if ($raw !== false && substr($raw, 0, 3) === "\xEF\xBB\xBF") {
			$raw = substr($raw, 3);
		}
		$data = json_decode((string) $raw, true);
		if (!is_array($data)) {
			$this->log('ERROR: site.json inválido — ' . json_last_error_msg());
			return false;
		}
		$this->site = $data;
		if (!empty($data['media_token'])) {
			$this->media_token = (string) $data['media_token'];
		}
		return true;
	}

	private function bootstrap_wp_files() {
		if (!function_exists('wp_generate_attachment_metadata')) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
	}

	private function ensure_elementor_ready() {
		foreach (array('container', 'nested-elements', 'container_grid') as $feature) {
			update_option('elementor_experiment-' . $feature, 'active');
		}
		update_option('elementor_element_cache_ttl', 'disable');
	}

	private function import_images() {
		$dirs = array(
			SM_PAYLOAD . 'media/brand',
			SM_PAYLOAD . 'media/stock',
		);
		$count = 0;
		foreach ($dirs as $dir) {
			if (!is_dir($dir)) {
				continue;
			}
			$files = array_merge(
				glob($dir . '/*.{jpg,jpeg,png,webp,gif,svg}', GLOB_BRACE) ?: array()
			);
			// Fallback if GLOB_BRACE unsupported
			if (!$files) {
				foreach (array('jpg', 'jpeg', 'png', 'webp', 'gif', 'svg') as $ext) {
					$files = array_merge($files, glob($dir . '/*.' . $ext) ?: array());
				}
			}
			foreach ($files as $file) {
				$filename = basename($file);
				$existing = $this->find_attachment_by_filename($filename);
				if ($existing) {
					$this->media_map[$filename] = $existing;
					$this->url_map[$filename] = wp_get_attachment_url($existing);
					$this->log('IMG reuse #' . $existing . ' ' . $filename);
					continue;
				}
				$id = $this->sideload_local_file($file, $filename);
				if (is_wp_error($id)) {
					$this->log('IMG ERROR ' . $filename . ': ' . $id->get_error_message());
					continue;
				}
				$this->media_map[$filename] = (int) $id;
				$this->url_map[$filename] = wp_get_attachment_url($id);
				$this->log('IMG new #' . $id . ' ' . $filename);
				$count++;
			}
		}
		$this->log('Medios: ' . count($this->url_map));
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
			'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/jpeg',
			'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
			'post_content' => '',
			'post_status' => 'inherit',
		), $dest);
		if (is_wp_error($attach_id)) {
			return $attach_id;
		}
		wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $dest));
		update_post_meta($attach_id, '_sm_source', 'site-maestro');
		return $attach_id;
	}

	private function replace_media_tokens($raw) {
		$token = $this->media_token;
		return preg_replace_callback(
			'/' . preg_quote($token, '/') . '\/([A-Za-z0-9._\-]+)/',
			function ($m) {
				$file = $m[1];
				if (!empty($this->url_map[$file])) {
					return $this->url_map[$file];
				}
				return $m[0];
			},
			$raw
		);
	}

	private function hydrate_elementor_media($elements) {
		if (!is_array($elements)) {
			return $elements;
		}
		foreach ($elements as &$el) {
			if (!empty($el['settings']) && is_array($el['settings'])) {
				foreach ($el['settings'] as $key => $val) {
					if (is_array($val) && isset($val['url']) && is_string($val['url'])) {
						foreach ($this->url_map as $file => $url) {
							if (strpos($val['url'], $file) !== false || strpos($val['url'], $this->media_token) !== false) {
								$el['settings'][$key]['url'] = $url;
								if (isset($this->media_map[$file])) {
									$el['settings'][$key]['id'] = $this->media_map[$file];
								}
							}
						}
					}
				}
			}
			if (!empty($el['elements'])) {
				$el['elements'] = $this->hydrate_elementor_media($el['elements']);
			}
		}
		return $elements;
	}

	private function import_templates() {
		$dir = SM_PAYLOAD . 'templates';
		if (!is_dir($dir)) {
			$this->log('WARN: sin carpeta templates/');
			return 0;
		}
		$files = glob($dir . '/*.json') ?: array();
		$count = 0;
		$has_pro = class_exists('\\ElementorPro\\Plugin');

		foreach ($files as $path) {
			$filename = basename($path);
			$raw = file_get_contents($path);
			if ($raw === false) {
				continue;
			}
			if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
				$raw = substr($raw, 3);
			}
			$raw = $this->replace_media_tokens($raw);
			$data = json_decode($raw, true);
			if (!is_array($data) || empty($data['content']) || !is_array($data['content'])) {
				$this->log('JSON inválido: ' . $filename);
				continue;
			}
			$title = !empty($data['title']) ? (string) $data['title'] : pathinfo($filename, PATHINFO_FILENAME);
			$type = !empty($data['type']) ? (string) $data['type'] : 'page';
			$content = $this->hydrate_elementor_media($data['content']);
			if (!$has_pro) {
				$content = $this->strip_theme_builder_only($content);
			}
			$this->template_content[$title] = $content;
			$this->template_content[$filename] = $content;
			$post_id = $this->upsert_elementor_library($title, $type, $content);
			if ($post_id) {
				$this->template_ids[$title] = $post_id;
				$this->template_ids[$filename] = $post_id;
				$this->log("Plantilla OK '$title' #$post_id ($filename)");
				$count++;
			}
		}
		return $count;
	}

	private function strip_theme_builder_only($content) {
		return $content;
	}

	private function create_pages() {
		$pages = isset($this->site['pages']) && is_array($this->site['pages']) ? $this->site['pages'] : array();
		$embed = !class_exists('\\ElementorPro\\Plugin');
		$count = 0;

		foreach ($pages as $cfg) {
			$title = isset($cfg['title']) ? (string) $cfg['title'] : '';
			$slug = isset($cfg['slug']) ? (string) $cfg['slug'] : sanitize_title($title);
			if ($title === '') {
				continue;
			}
			$page_id = $this->upsert_page($title, $slug);
			$this->page_ids[$title] = $page_id;
			$count++;
			$this->log("Página '$title' #$page_id /$slug");

			if (!empty($cfg['is_blog'])) {
				continue;
			}

			$content = null;
			$tpl = isset($cfg['template']) ? $cfg['template'] : null;
			if ($tpl) {
				if (!empty($this->template_content[$tpl])) {
					$content = $this->template_content[$tpl];
				} else {
					$base = basename((string) $tpl);
					if (!empty($this->template_content[$base])) {
						$content = $this->template_content[$base];
					}
				}
			}

			if (!is_array($content) || empty($content)) {
				$content = $this->build_placeholder_page($title);
				$this->log("WARN '$title': sin template → placeholder editable");
			}

			if ($embed) {
				$content = $this->embed_header_footer($content);
			}

			$this->apply_elementor_to_post($page_id, $content, 'wp-page');
		}
		return $count;
	}

	private function build_placeholder_page($title) {
		$prefix = !empty($this->site['brand']['css_prefix']) ? $this->site['brand']['css_prefix'] : 's-site';
		return array(
			array(
				'id' => uniqid(),
				'elType' => 'container',
				'settings' => array(
					'flex_direction' => 'column',
					'padding' => array('unit' => 'px', 'top' => '80', 'right' => '24', 'bottom' => '80', 'left' => '24', 'isLinked' => false),
					'_css_classes' => $prefix . '-section',
				),
				'elements' => array(
					array(
						'id' => uniqid(),
						'elType' => 'widget',
						'widgetType' => 'heading',
						'settings' => array(
							'title' => $title,
							'header_size' => 'h1',
						),
						'elements' => array(),
					),
					array(
						'id' => uniqid(),
						'elType' => 'widget',
						'widgetType' => 'text-editor',
						'settings' => array(
							'editor' => '<p>Contenido pendiente. Editar con Elementor.</p>',
						),
						'elements' => array(),
					),
				),
			),
		);
	}

	private function embed_header_footer($content) {
		$header_file = !empty($this->site['header_template']) ? $this->site['header_template'] : null;
		$footer_file = !empty($this->site['footer_template']) ? $this->site['footer_template'] : null;
		$header = $header_file && !empty($this->template_content[$header_file]) ? $this->template_content[$header_file] : array();
		$footer = $footer_file && !empty($this->template_content[$footer_file]) ? $this->template_content[$footer_file] : array();
		if (!$header && !$footer) {
			return $content;
		}
		return array_merge($header, $content, $footer);
	}

	private function upsert_page($title, $slug) {
		$existing = get_page_by_path($slug);
		if (!$existing) {
			foreach (get_posts(array('post_type' => 'page', 'post_status' => 'any', 'numberposts' => 200)) as $p) {
				if ($p->post_title === $title) {
					$existing = $p;
					break;
				}
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

	private function write_elementor_meta($post_id, $content, $type = 'wp-page') {
		$json = wp_json_encode($content);
		if ($json === false) {
			return false;
		}
		update_post_meta($post_id, '_elementor_edit_mode', 'builder');
		update_post_meta($post_id, '_elementor_template_type', $type);
		update_post_meta($post_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.21.0');
		update_post_meta($post_id, '_elementor_data', wp_slash($json));
		if (get_post_type($post_id) === 'page') {
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
		$ok = $this->write_elementor_meta($post_id, $content, $type);
		if (class_exists('\\Elementor\\Core\\Files\\CSS\\Post')) {
			try {
				$css_file = new \Elementor\Core\Files\CSS\Post($post_id);
				$css_file->update();
			} catch (Exception $e) { // phpcs:ignore
			} catch (Throwable $e) { // phpcs:ignore
			}
		}
		return $ok;
	}

	private function upsert_elementor_library($title, $type, $content) {
		$existing = null;
		foreach (get_posts(array('post_type' => 'elementor_library', 'post_status' => 'any', 'numberposts' => 200)) as $p) {
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
		$allowed = array('page', 'section', 'header', 'footer', 'single', 'archive', 'popup', 'container');
		$elementor_type = in_array(strtolower($type), $allowed, true) ? strtolower($type) : 'page';
		if (taxonomy_exists('elementor_library_type')) {
			wp_set_object_terms($post_id, $elementor_type, 'elementor_library_type');
		}
		$this->write_elementor_meta((int) $post_id, $content, $elementor_type);
		return (int) $post_id;
	}

	private function create_posts() {
		$posts = isset($this->site['posts']) && is_array($this->site['posts']) ? $this->site['posts'] : array();
		$count = 0;
		foreach ($posts as $p) {
			$title = isset($p['title']) ? (string) $p['title'] : '';
			if ($title === '') {
				continue;
			}
			$existing_id = 0;
			foreach (get_posts(array('post_type' => 'post', 'post_status' => 'any', 'numberposts' => 100)) as $existing) {
				if ($existing->post_title === $title) {
					$existing_id = (int) $existing->ID;
					break;
				}
			}
			if ($existing_id) {
				continue;
			}
			$id = wp_insert_post(array(
				'post_title' => $title,
				'post_content' => isset($p['content']) ? $p['content'] : '',
				'post_status' => 'publish',
				'post_type' => 'post',
			));
			if ($id && !is_wp_error($id)) {
				if (!empty($p['image']) && !empty($this->media_map[$p['image']])) {
					set_post_thumbnail($id, $this->media_map[$p['image']]);
				}
				if (!empty($p['category'])) {
					$term = term_exists($p['category'], 'category');
					if (!$term) {
						$term = wp_insert_term($p['category'], 'category');
					}
					if (!is_wp_error($term)) {
						$tid = (int) (is_array($term) ? $term['term_id'] : $term);
						wp_set_post_categories($id, array($tid), false);
					}
				}
				$count++;
			}
		}
		$this->log('Posts nuevos: ' . $count);
		return $count;
	}

	private function create_menus() {
		$menus = isset($this->site['menus']) && is_array($this->site['menus']) ? $this->site['menus'] : array();
		$project = !empty($this->site['project_id']) ? $this->site['project_id'] : 'site';
		$primary_name = $project . ' Principal';
		$legal_name = $project . ' Legal';

		$primary_id = $this->rebuild_menu($primary_name, isset($menus['primary']) ? $menus['primary'] : array());
		$legal_id = $this->rebuild_menu($legal_name, isset($menus['legal']) ? $menus['legal'] : array());

		$locations = get_theme_mod('nav_menu_locations', array());
		if (!is_array($locations)) {
			$locations = array();
		}
		foreach (array('primary', 'menu-1', 'main_menu', 'header') as $loc) {
			$locations[$loc] = $primary_id;
		}
		$locations['footer'] = $legal_id ? $legal_id : $primary_id;
		set_theme_mod('nav_menu_locations', $locations);
		$this->log('Menús OK');
		return true;
	}

	private function rebuild_menu($name, $titles) {
		$menu = wp_get_nav_menu_object($name);
		$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
		if ($menu) {
			foreach (wp_get_nav_menu_items($menu_id) ?: array() as $item) {
				wp_delete_post($item->ID, true);
			}
		}
		$i = 1;
		foreach ($titles as $title) {
			if (empty($this->page_ids[$title])) {
				continue;
			}
			wp_update_nav_menu_item($menu_id, 0, array(
				'menu-item-title' => $title,
				'menu-item-object' => 'page',
				'menu-item-object-id' => $this->page_ids[$title],
				'menu-item-type' => 'post_type',
				'menu-item-status' => 'publish',
				'menu-item-position' => $i++,
			));
		}
		return $menu_id;
	}

	private function configure_reading() {
		$pages = isset($this->site['pages']) ? $this->site['pages'] : array();
		foreach ($pages as $cfg) {
			$title = isset($cfg['title']) ? $cfg['title'] : '';
			if (!empty($cfg['is_front']) && !empty($this->page_ids[$title])) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $this->page_ids[$title]);
			}
			if (!empty($cfg['is_blog']) && !empty($this->page_ids[$title])) {
				update_option('page_for_posts', $this->page_ids[$title]);
			}
		}
		update_option('permalink_structure', '/%postname%/');
	}

	private function configure_site_identity() {
		$brand = isset($this->site['brand']) ? $this->site['brand'] : array();
		if (!empty($brand['name'])) {
			update_option('blogname', $brand['name']);
		}
		if (!empty($brand['tagline'])) {
			update_option('blogdescription', $brand['tagline']);
		}
		$logo = !empty($brand['logo']) ? $brand['logo'] : '';
		if ($logo && !empty($this->media_map[$logo])) {
			set_theme_mod('custom_logo', $this->media_map[$logo]);
		}
		$favicon = !empty($brand['favicon']) ? $brand['favicon'] : $logo;
		if ($favicon && !empty($this->media_map[$favicon])) {
			update_option('site_icon', $this->media_map[$favicon]);
		}
		update_option('sm_site_meta', array(
			'project_id' => $this->site['project_id'] ?? '',
			'body_class' => $brand['body_class'] ?? '',
			'css_prefix' => $brand['css_prefix'] ?? '',
			'archetype' => $this->site['experience']['archetype'] ?? '',
		), false);
	}

	private function assign_header_footer() {
		if (!class_exists('\\ElementorPro\\Plugin')) {
			$this->log('Header/Footer: Free → embed');
			return;
		}
		$header_file = !empty($this->site['header_template']) ? $this->site['header_template'] : '';
		$footer_file = !empty($this->site['footer_template']) ? $this->site['footer_template'] : '';
		$header_id = $header_file && !empty($this->template_ids[$header_file]) ? $this->template_ids[$header_file] : 0;
		$footer_id = $footer_file && !empty($this->template_ids[$footer_file]) ? $this->template_ids[$footer_file] : 0;
		if ($header_id) {
			update_post_meta($header_id, '_elementor_conditions', array('include/general'));
		}
		if ($footer_id) {
			update_post_meta($footer_id, '_elementor_conditions', array('include/general'));
		}
		update_option('sm_pro_chrome_ids', array('header' => $header_id, 'footer' => $footer_id), false);
		$this->log('Pro chrome header#' . $header_id . ' footer#' . $footer_id);
	}

	private function flush_elementor_css() {
		if (class_exists('\\Elementor\\Plugin')) {
			try {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			} catch (Exception $e) { // phpcs:ignore
			} catch (Throwable $e) { // phpcs:ignore
			}
		}
	}

	/**
	 * Copia CSS/JS/frontend a mu-plugins para sobrevivir al borrar el instalador.
	 */
	public function deploy_runtime() {
		if (empty($this->site) && !$this->load_site_json()) {
			return false;
		}

		$mu_dir = WP_CONTENT_DIR . '/mu-plugins';
		$dest = $mu_dir . '/site-maestro-runtime';
		$loader_dest = $mu_dir . '/site-maestro-runtime.php';

		if (!wp_mkdir_p($mu_dir) || !wp_mkdir_p($dest . '/includes') || !wp_mkdir_p($dest . '/assets')) {
			$this->log('RUNTIME ERROR: mkdir');
			return false;
		}

		$css_name = !empty($this->site['assets']['css']) ? $this->site['assets']['css'] : 'branding.css';
		$js_name = !empty($this->site['assets']['js']) ? $this->site['assets']['js'] : 'frontend.js';

		$copies = array(
			array(SM_PATH . 'includes/sm-frontend.php', $dest . '/includes/sm-frontend.php'),
			array(SM_PAYLOAD . $css_name, $dest . '/assets/branding.css'),
			array(SM_PAYLOAD . $js_name, $dest . '/assets/frontend.js'),
			array(SM_PAYLOAD . 'site.json', $dest . '/assets/site.json'),
		);

		$ok = 0;
		foreach ($copies as $pair) {
			list($from, $to) = $pair;
			if (!is_readable($from)) {
				$this->log('RUNTIME WARN: falta ' . $from);
				continue;
			}
			if (!@copy($from, $to)) {
				$this->log('RUNTIME ERROR: copy ' . $to);
				continue;
			}
			$ok++;
		}

		$loader_src = SM_PATH . 'runtime/mu-loader.php';
		if (!is_readable($loader_src)) {
			$this->log('RUNTIME ERROR: falta mu-loader.php');
			return false;
		}
		$loader = file_get_contents($loader_src);
		$loader = preg_replace("/Version: [0-9.]+/", 'Version: ' . SM_VERSION, $loader, 1);
		$brand = !empty($this->site['brand']['name']) ? $this->site['brand']['name'] : 'Site';
		$loader = str_replace('{{BRAND}}', $brand, $loader);
		if (false === file_put_contents($loader_dest, $loader)) {
			$this->log('RUNTIME ERROR: write loader');
			return false;
		}

		update_option('sm_runtime_deployed', array(
			'version' => SM_VERSION,
			'project_id' => $this->site['project_id'] ?? '',
			'time' => time(),
		), false);

		$this->log('RUNTIME OK: ' . $ok . ' archivos');
		return $ok > 0 && is_readable($loader_dest);
	}
}
