<?php
/**
 * Plugin Name: AV Studio Runtime
 * Description: Estilos, tipografías, blog y efectos de AV Studio Academy. Se instala solo; no lo borres si quieres conservar el diseño. El plugin «AV Studio Maestro» se puede eliminar tras instalar el sitio.
 * Version: 2.7.9
 * Author: TEDUCAS / AV Studio Academy
 *
 * Este archivo vive en wp-content/mu-plugins/av-studio-runtime.php (cargado siempre).
 */
if (!defined('ABSPATH')) {
	exit;
}

define('AVSI_RUNTIME_VERSION', '2.7.9');
define('AVSI_RUNTIME_PATH', WP_CONTENT_DIR . '/mu-plugins/av-studio-runtime/');
define('AVSI_RUNTIME_URL', content_url('mu-plugins/av-studio-runtime/'));
define('AVSI_RUNTIME_ACTIVE', true);

$avsi_frontend = AVSI_RUNTIME_PATH . 'includes/avsi-frontend.php';
if (is_readable($avsi_frontend)) {
	require_once $avsi_frontend;
}
