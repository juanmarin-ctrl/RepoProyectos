<?php
/**
 * Plugin Name: Site Maestro Runtime ({{BRAND}})
 * Description: Estilos y scripts del sitio. No lo borres si quieres conservar el diseño. El plugin Site Maestro se puede eliminar tras instalar.
 * Version: 1.0.0
 * Author: TEDUCAS
 *
 * Vive en wp-content/mu-plugins/site-maestro-runtime.php
 */
if (!defined('ABSPATH')) {
	exit;
}

define('SM_RUNTIME_VERSION', '1.0.0');
define('SM_RUNTIME_PATH', WP_CONTENT_DIR . '/mu-plugins/site-maestro-runtime/');
define('SM_RUNTIME_URL', content_url('mu-plugins/site-maestro-runtime/'));
define('SM_RUNTIME_ACTIVE', true);

$sm_frontend = SM_RUNTIME_PATH . 'includes/sm-frontend.php';
if (is_readable($sm_frontend)) {
	require_once $sm_frontend;
}
