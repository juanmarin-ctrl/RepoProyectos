<?php
/**
 * Plugin Name: AV Studio Maestro
 * Description: Instala el sitio completo de AV Studio Academy con un solo clic (fotos, páginas Elementor, menús, inicio, CSS). Tras instalar, despliega un runtime en mu-plugins para que puedas borrar este plugin sin perder estilos.
 * Version: 2.7.9
 * Author: TEDUCAS / AV Studio Academy
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: av-studio-importer
 */

if (!defined('ABSPATH')) {
	exit;
}

define('AVSI_VERSION', '2.7.9');
define('AVSI_PATH', plugin_dir_path(__FILE__));
define('AVSI_URL', plugin_dir_url(__FILE__));

require_once AVSI_PATH . 'includes/class-avsi-importer.php';

// Frontend: solo si el MU-plugin runtime aún no lo cargó (evita hooks duplicados).
if (!defined('AVSI_FRONTEND_LOADED')) {
	require_once AVSI_PATH . 'includes/avsi-frontend.php';
}

add_action('admin_menu', function () {
	add_menu_page(
		'AV Studio Maestro',
		'AV Studio',
		'manage_options',
		'av-studio-importer',
		'avsi_render_admin_page',
		'dashicons-art',
		3
	);
});

add_action('admin_post_avsi_run_import', function () {
	if (!current_user_can('manage_options')) {
		wp_die('Forbidden');
	}
	check_admin_referer('avsi_run_import');

	@set_time_limit(300);
	wp_raise_memory_limit('admin');

	$importer = new AVSI_Importer();
	$result = $importer->run_full_site();

	set_transient('avsi_last_result', $result, 2 * HOUR_IN_SECONDS);
	update_option('avsi_last_result', $result, false);
	wp_safe_redirect(admin_url('admin.php?page=av-studio-importer&done=1'));
	exit;
});

add_action('admin_post_avsi_deploy_runtime', function () {
	if (!current_user_can('manage_options')) {
		wp_die('Forbidden');
	}
	check_admin_referer('avsi_deploy_runtime');

	$importer = new AVSI_Importer();
	$ok = $importer->deploy_runtime();
	$log = $importer->get_log_lines();
	$result = array(
		'runtime_only' => true,
		'runtime' => $ok,
		'images' => 0,
		'templates' => 0,
		'pages' => 0,
		'posts' => 0,
		'log' => $log,
	);
	set_transient('avsi_last_result', $result, 2 * HOUR_IN_SECONDS);
	update_option('avsi_last_result', $result, false);
	update_option('avsi_last_log', $log, false);
	$upload = wp_upload_dir();
	if (empty($upload['error'])) {
		file_put_contents(
			trailingslashit($upload['basedir']) . 'avsi-import-log.txt',
			implode("\n", $log) . "\n"
		);
	}
	wp_safe_redirect(admin_url('admin.php?page=av-studio-importer&runtime=1'));
	exit;
});

/**
 * ¿Está el runtime MU-plugin instalado y legible?
 */
function avsi_runtime_is_deployed() {
	$loader = WP_CONTENT_DIR . '/mu-plugins/av-studio-runtime.php';
	$frontend = WP_CONTENT_DIR . '/mu-plugins/av-studio-runtime/includes/avsi-frontend.php';
	$css = WP_CONTENT_DIR . '/mu-plugins/av-studio-runtime/assets/av-frontend.css';
	return is_readable($loader) && is_readable($frontend) && is_readable($css);
}

function avsi_render_admin_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	$result = get_transient('avsi_last_result');
	if (!is_array($result)) {
		$result = get_option('avsi_last_result');
	}
	$log = array();
	if (is_array($result) && !empty($result['log'])) {
		$log = $result['log'];
	} else {
		$saved = get_option('avsi_last_log');
		if (is_array($saved)) {
			$log = $saved;
		}
	}

	$elementor_ok = did_action('elementor/loaded') || class_exists('\\Elementor\\Plugin');
	$pro_ok = class_exists('\\ElementorPro\\Plugin');
	$runtime_ok = avsi_runtime_is_deployed() || defined('AVSI_RUNTIME_ACTIVE');
	$upload = wp_upload_dir();
	$log_file = !empty($upload['basedir']) ? trailingslashit($upload['basedir']) . 'avsi-import-log.txt' : '';
	?>
	<div class="wrap">
		<h1>AV Studio Maestro <small style="font-size:13px;color:#666;">v<?php echo esc_html(AVSI_VERSION); ?></small></h1>
		<p style="font-size:15px;max-width:760px;">Un botón instala el sitio. Después verás el <strong>log completo</strong> de lo que hizo (fotos, plantillas, elementos por página).</p>

		<?php if (!$elementor_ok) : ?>
			<div class="notice notice-error"><p><strong>Activa Elementor primero</strong> y vuelve aquí.</p></div>
		<?php endif; ?>

		<?php if ($elementor_ok && !$pro_ok) : ?>
			<div class="notice notice-info"><p>Elementor Free: header/footer se embebe en cada página.</p></div>
		<?php endif; ?>

		<?php if ($runtime_ok) : ?>
			<div class="notice notice-success" style="padding:12px 16px;">
				<p style="margin:0;font-size:15px;"><strong>Runtime instalado.</strong> Los estilos, tipografías, JS y plantillas del blog viven en <code>wp-content/mu-plugins/av-studio-runtime/</code>. Ya puedes <strong>desactivar y eliminar</strong> este plugin (AV Studio Maestro) sin dañar el diseño. No borres la carpeta <code>mu-plugins</code> ni el archivo <code>av-studio-runtime.php</code>.</p>
			</div>
		<?php else : ?>
			<div class="notice notice-warning" style="padding:12px 16px;">
				<p style="margin:0;font-size:15px;"><strong>Aún no hay runtime.</strong> Si desinstalas este plugin ahora, se romperán los estilos. Pulsa <em>REPARAR / INSTALAR</em> o el botón de solo runtime abajo.</p>
			</div>
		<?php endif; ?>

		<?php if (isset($_GET['runtime']) && is_array($result)) : ?>
			<div class="notice notice-<?php echo !empty($result['runtime']) ? 'success' : 'error'; ?>" style="padding:12px 16px;">
				<p style="margin:0;font-size:15px;">
					<?php if (!empty($result['runtime'])) : ?>
						<strong>Runtime desplegado.</strong> Revisa el log. Ya puedes eliminar AV Studio Maestro.
					<?php else : ?>
						<strong>No se pudo desplegar el runtime.</strong> Revisa permisos de escritura en <code>wp-content/mu-plugins/</code>.
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if (isset($_GET['done']) && is_array($result)) : ?>
			<div class="notice notice-success" style="padding:12px 16px;">
				<p style="font-size:16px;margin:0 0 8px;"><strong>Proceso terminado.</strong> Revisa el log abajo: si alguna página dice <code>FALLBACK-generic</code> o <code>verify_els=0</code>, cópialo y mándalo.</p>
				<ul style="margin:0;">
					<li>Imágenes nuevas: <?php echo esc_html((string) ($result['images'] ?? 0)); ?></li>
					<li>Plantillas: <?php echo esc_html((string) ($result['templates'] ?? 0)); ?></li>
					<li>Páginas: <?php echo esc_html((string) ($result['pages'] ?? 0)); ?></li>
					<li>Posts: <?php echo esc_html((string) ($result['posts'] ?? 0)); ?></li>
					<li>Runtime (para poder borrar el plugin): <?php echo !empty($result['runtime']) ? 'OK' : 'FALLO'; ?></li>
				</ul>
				<p style="margin-top:12px;">
					<a class="button button-primary" href="<?php echo esc_url(home_url('/')); ?>" target="_blank">Ver sitio</a>
					<a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">Páginas</a>
					<a class="button" href="<?php echo esc_url(admin_url('post.php?post=' . (int) get_option('page_on_front') . '&action=elementor')); ?>">Editar Inicio con Elementor</a>
				</p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:760px;padding:1.5rem;border:1px solid #c4a35a;box-shadow:0 10px 30px rgba(0,0,0,.06);">
			<h2 style="margin-top:0;">Instalar / reparar</h2>
			<p>Sube el ZIP <strong>2.7.9</strong>, actívalo y pulsa el botón. Incluye skin AV para <strong>Tutor LMS</strong> (curso individual), runtime MU-plugin, fotos y datos de empresa.</p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="this.querySelector('input[type=submit]').disabled=true;this.querySelector('input[type=submit]').value='Instalando… espera';">
				<input type="hidden" name="action" value="avsi_run_import">
				<?php wp_nonce_field('avsi_run_import'); ?>
				<?php
				submit_button(
					'REPARAR / INSTALAR SITIO COMPLETO',
					'primary large',
					'submit',
					false,
					$elementor_ok ? array('style' => 'background:#c4a35a;border-color:#9a7a3c;font-size:16px;padding:8px 22px;') : array('disabled' => 'disabled')
				);
				?>
			</form>
		</div>

		<div class="card" style="max-width:760px;margin-top:20px;padding:1.5rem;">
			<h2 style="margin-top:0;">Solo runtime (preparar desinstalación)</h2>
			<p>Si el sitio ya está instalado y solo quieres poder <strong>borrar este plugin</strong> sin romper CSS/JS/blog, usa este botón. No vuelve a crear páginas.</p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="avsi_deploy_runtime">
				<?php wp_nonce_field('avsi_deploy_runtime'); ?>
				<?php
				submit_button(
					'INSTALAR RUNTIME Y PODER BORRAR ESTE PLUGIN',
					'secondary large',
					'submit',
					false
				);
				?>
			</form>
			<p style="color:#666;font-size:13px;margin-bottom:0;">Después: Plugins → AV Studio Maestro → Desactivar → Eliminar. El diseño queda en el runtime (mu-plugins).</p>
		</div>

		<?php if (!empty($log)) : ?>
			<div class="card" style="max-width:960px;margin-top:20px;padding:1.25rem;">
				<h2 style="margin-top:0;">Log de instalación</h2>
				<p style="margin-top:0;color:#666;">También se guarda en: <code><?php echo esc_html($log_file); ?></code></p>
				<textarea readonly rows="22" style="width:100%;font-family:Consolas,Monaco,monospace;font-size:12px;background:#111;color:#d6ffd6;padding:12px;border-radius:8px;"><?php echo esc_textarea(implode("\n", $log)); ?></textarea>
			</div>
		<?php else : ?>
			<div class="card" style="max-width:760px;margin-top:20px;padding:1.25rem;">
				<h2 style="margin-top:0;">Log</h2>
				<p>Aún no hay log. Ejecuta la instalación y aparecerá aquí línea por línea.</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
