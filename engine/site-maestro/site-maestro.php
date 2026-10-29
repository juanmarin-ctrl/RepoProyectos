<?php
/**
 * Plugin Name: Site Maestro
 * Description: Instalador genérico TEDUCAS. Lee payload/ (site.json + media + templates Elementor + CSS/JS) e instala el sitio con un clic. El diseño lo genera la IA por cliente; este plugin solo importa.
 * Version: 1.0.0
 * Author: TEDUCAS
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: site-maestro
 */

if (!defined('ABSPATH')) {
	exit;
}

define('SM_VERSION', '1.0.0');
define('SM_PATH', plugin_dir_path(__FILE__));
define('SM_URL', plugin_dir_url(__FILE__));
define('SM_PAYLOAD', SM_PATH . 'payload/');

require_once SM_PATH . 'includes/class-sm-importer.php';

if (!defined('SM_FRONTEND_LOADED')) {
	require_once SM_PATH . 'includes/sm-frontend.php';
}

add_action('admin_menu', function () {
	$brand = sm_payload_brand_name();
	add_menu_page(
		'Site Maestro',
		$brand ? $brand : 'Site Maestro',
		'manage_options',
		'site-maestro',
		'sm_render_admin_page',
		'dashicons-art',
		3
	);
});

add_action('admin_post_sm_run_import', function () {
	if (!current_user_can('manage_options')) {
		wp_die('Forbidden');
	}
	check_admin_referer('sm_run_import');
	@set_time_limit(300);
	wp_raise_memory_limit('admin');

	$importer = new SM_Importer();
	$result = $importer->run_full_site();
	set_transient('sm_last_result', $result, 2 * HOUR_IN_SECONDS);
	update_option('sm_last_result', $result, false);
	wp_safe_redirect(admin_url('admin.php?page=site-maestro&done=1'));
	exit;
});

add_action('admin_post_sm_deploy_runtime', function () {
	if (!current_user_can('manage_options')) {
		wp_die('Forbidden');
	}
	check_admin_referer('sm_deploy_runtime');

	$importer = new SM_Importer();
	$ok = $importer->deploy_runtime();
	$result = array(
		'runtime_only' => true,
		'runtime' => $ok,
		'log' => $importer->get_log_lines(),
	);
	set_transient('sm_last_result', $result, 2 * HOUR_IN_SECONDS);
	update_option('sm_last_result', $result, false);
	wp_safe_redirect(admin_url('admin.php?page=site-maestro&runtime=1'));
	exit;
});

/**
 * Lee nombre de marca desde payload/site.json (si existe).
 */
function sm_payload_brand_name() {
	$path = SM_PAYLOAD . 'site.json';
	if (!is_readable($path)) {
		return '';
	}
	$data = json_decode((string) file_get_contents($path), true);
	if (!is_array($data) || empty($data['brand']['name'])) {
		return '';
	}
	return (string) $data['brand']['name'];
}

function sm_runtime_is_deployed() {
	$loader = WP_CONTENT_DIR . '/mu-plugins/site-maestro-runtime.php';
	$frontend = WP_CONTENT_DIR . '/mu-plugins/site-maestro-runtime/includes/sm-frontend.php';
	$css = WP_CONTENT_DIR . '/mu-plugins/site-maestro-runtime/assets/branding.css';
	return is_readable($loader) && is_readable($frontend) && is_readable($css);
}

function sm_render_admin_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$result = get_transient('sm_last_result');
	if (!is_array($result)) {
		$result = get_option('sm_last_result');
	}
	$log = array();
	if (is_array($result) && !empty($result['log'])) {
		$log = $result['log'];
	}

	$elementor_ok = did_action('elementor/loaded') || class_exists('\\Elementor\\Plugin');
	$pro_ok = class_exists('\\ElementorPro\\Plugin');
	$runtime_ok = sm_runtime_is_deployed() || defined('SM_RUNTIME_ACTIVE');
	$payload_ok = is_readable(SM_PAYLOAD . 'site.json');
	$site = $payload_ok ? json_decode((string) file_get_contents(SM_PAYLOAD . 'site.json'), true) : null;
	$brand = is_array($site) && !empty($site['brand']['name']) ? $site['brand']['name'] : '—';
	$archetype = is_array($site) && !empty($site['experience']['archetype']) ? $site['experience']['archetype'] : '—';
	?>
	<div class="wrap">
		<h1>Site Maestro <small style="font-size:13px;color:#666;">v<?php echo esc_html(SM_VERSION); ?></small></h1>
		<p style="max-width:720px;">Importa el <strong>payload</strong> generado para este cliente. El diseño no vive en el PHP: vive en <code>payload/</code>.</p>

		<div class="card" style="max-width:720px;padding:1rem 1.25rem;margin-top:1rem;">
			<p style="margin:0;"><strong>Marca:</strong> <?php echo esc_html($brand); ?> &nbsp;|&nbsp; <strong>Experiencia:</strong> <?php echo esc_html($archetype); ?></p>
			<p style="margin:.5rem 0 0;color:#666;font-size:13px;">Payload: <?php echo $payload_ok ? 'site.json OK' : 'FALTA site.json'; ?></p>
		</div>

		<?php if (!$payload_ok) : ?>
			<div class="notice notice-error"><p>No hay <code>payload/site.json</code>. Genera el proyecto con el Site Kit (IA) antes de instalar.</p></div>
		<?php endif; ?>

		<?php if (!$elementor_ok) : ?>
			<div class="notice notice-error"><p><strong>Activa Elementor</strong> y vuelve aquí.</p></div>
		<?php endif; ?>

		<?php if ($elementor_ok && !$pro_ok) : ?>
			<div class="notice notice-info"><p>Elementor Free: header/footer se embebe en cada página.</p></div>
		<?php endif; ?>

		<?php if ($runtime_ok) : ?>
			<div class="notice notice-success"><p><strong>Runtime instalado.</strong> Puedes desactivar y eliminar este plugin sin perder estilos.</p></div>
		<?php else : ?>
			<div class="notice notice-warning"><p><strong>Sin runtime.</strong> Si borras el plugin ahora, se rompen los estilos.</p></div>
		<?php endif; ?>

		<?php if (isset($_GET['done']) && is_array($result)) : ?>
			<div class="notice notice-success">
				<p><strong>Proceso terminado.</strong></p>
				<ul>
					<li>Imágenes: <?php echo esc_html((string) ($result['images'] ?? 0)); ?></li>
					<li>Plantillas: <?php echo esc_html((string) ($result['templates'] ?? 0)); ?></li>
					<li>Páginas: <?php echo esc_html((string) ($result['pages'] ?? 0)); ?></li>
					<li>Posts: <?php echo esc_html((string) ($result['posts'] ?? 0)); ?></li>
					<li>Runtime: <?php echo !empty($result['runtime']) ? 'OK' : 'FALLO'; ?></li>
				</ul>
				<p><a class="button button-primary" href="<?php echo esc_url(home_url('/')); ?>" target="_blank">Ver sitio</a></p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:720px;padding:1.5rem;margin-top:1rem;">
			<h2 style="margin-top:0;">Instalar / reparar</h2>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="this.querySelector('input[type=submit]').disabled=true;this.querySelector('input[type=submit]').value='Instalando…';">
				<input type="hidden" name="action" value="sm_run_import">
				<?php wp_nonce_field('sm_run_import'); ?>
				<?php
				submit_button(
					'INSTALAR SITIO COMPLETO',
					'primary large',
					'submit',
					false,
					($elementor_ok && $payload_ok) ? array() : array('disabled' => 'disabled')
				);
				?>
			</form>
		</div>

		<div class="card" style="max-width:720px;padding:1.5rem;margin-top:1rem;">
			<h2 style="margin-top:0;">Solo runtime</h2>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="sm_deploy_runtime">
				<?php wp_nonce_field('sm_deploy_runtime'); ?>
				<?php submit_button('INSTALAR RUNTIME', 'secondary large', 'submit', false); ?>
			</form>
		</div>

		<?php if (!empty($log)) : ?>
			<div class="card" style="max-width:960px;padding:1.25rem;margin-top:1rem;">
				<h2 style="margin-top:0;">Log</h2>
				<textarea readonly rows="20" style="width:100%;font-family:Consolas,Monaco,monospace;font-size:12px;background:#111;color:#d6ffd6;padding:12px;border-radius:8px;"><?php echo esc_textarea(implode("\n", $log)); ?></textarea>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
