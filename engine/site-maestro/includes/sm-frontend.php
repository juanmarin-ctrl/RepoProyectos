<?php
/**
 * Frontend runtime genérico. Lee meta del sitio (body_class) desde options o site.json del runtime.
 */
if (!defined('ABSPATH')) {
	exit;
}

if (defined('SM_FRONTEND_LOADED')) {
	return;
}
define('SM_FRONTEND_LOADED', true);

if (!defined('SM_RUNTIME_VERSION')) {
	define('SM_RUNTIME_VERSION', defined('SM_VERSION') ? SM_VERSION : '1.0.0');
}

function sm_runtime_base_path() {
	if (defined('SM_RUNTIME_PATH') && SM_RUNTIME_PATH) {
		return SM_RUNTIME_PATH;
	}
	if (defined('SM_PATH')) {
		return SM_PATH;
	}
	return '';
}

function sm_runtime_base_url() {
	if (defined('SM_RUNTIME_URL') && SM_RUNTIME_URL) {
		return SM_RUNTIME_URL;
	}
	if (defined('SM_URL')) {
		return SM_URL;
	}
	return '';
}

function sm_site_meta() {
	$meta = get_option('sm_site_meta');
	if (is_array($meta) && !empty($meta['body_class'])) {
		return $meta;
	}
	// Fallback: site.json en runtime o payload
	$candidates = array(
		sm_runtime_base_path() . 'assets/site.json',
		(defined('SM_PAYLOAD') ? SM_PAYLOAD : '') . 'site.json',
	);
	foreach ($candidates as $path) {
		if ($path && is_readable($path)) {
			$data = json_decode((string) file_get_contents($path), true);
			if (is_array($data) && !empty($data['brand'])) {
				return array(
					'project_id' => $data['project_id'] ?? '',
					'body_class' => $data['brand']['body_class'] ?? 'site-maestro',
					'css_prefix' => $data['brand']['css_prefix'] ?? 's-site',
					'archetype' => $data['experience']['archetype'] ?? '',
				);
			}
		}
	}
	return array(
		'body_class' => 'site-maestro',
		'css_prefix' => 's-site',
	);
}

add_filter('body_class', function ($classes) {
	$meta = sm_site_meta();
	if (!empty($meta['body_class'])) {
		$classes[] = sanitize_html_class($meta['body_class']);
	}
	$classes[] = 'sm-site';
	if (!empty($meta['archetype'])) {
		$classes[] = 'sm-arch-' . sanitize_html_class($meta['archetype']);
	}
	return $classes;
});

add_action('wp_enqueue_scripts', function () {
	$base_path = sm_runtime_base_path();
	$base_url = sm_runtime_base_url();
	if (!$base_path || !$base_url) {
		return;
	}

	$css = '';
	$js = '';
	// Runtime layout
	if (is_readable($base_path . 'assets/branding.css')) {
		$css = $base_url . 'assets/branding.css';
	} elseif (defined('SM_PAYLOAD') && is_readable(SM_PAYLOAD . 'branding.css')) {
		$css = SM_URL . 'payload/branding.css';
	}
	if (is_readable($base_path . 'assets/frontend.js')) {
		$js = $base_url . 'assets/frontend.js';
	} elseif (defined('SM_PAYLOAD') && is_readable(SM_PAYLOAD . 'frontend.js')) {
		$js = SM_URL . 'payload/frontend.js';
	}

	$ver = SM_RUNTIME_VERSION;
	if ($css) {
		$css_file = (strpos($css, 'payload/') !== false && defined('SM_PAYLOAD'))
			? SM_PAYLOAD . 'branding.css'
			: $base_path . 'assets/branding.css';
		if (is_readable($css_file)) {
			$ver = (string) filemtime($css_file);
		}
		wp_enqueue_style('sm-branding', $css, array(), $ver);
	}
	if ($js) {
		wp_enqueue_script('sm-frontend', $js, array(), $ver, true);
	}
}, 20);
