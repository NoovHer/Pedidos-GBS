<?php

/**
 * Plugin Name:     Plugin de prueba GBS (Pago a Cuenta)
 * Plugin URI:      https://gbs.mx
 * Description:     Este plugin es de prueba para GBS
 * Author:          Elioth Novoa
 * Author URI:      gbs.mx
 * Text Domain:     gbsPlugin
 * Domain Path:     /languages
 * Version:         0.0.2
 *
 * @package         GbsPlugin
 */

// Asegurarse de que el archivo se carga en WordPress
if (!defined('ABSPATH')) {
	exit;
}

define('PLUGIN_FILE', __FILE__);
define('PLUGIN_DIR', plugin_dir_path(PLUGIN_FILE));
define('PLUGIN_URL', plugin_dir_url(PLUGIN_FILE));

// Función de inicialización principal del plugin
function init()
{
	// Cargar archivos y dependencias solo si WooCommerce está activo
	if (class_exists('WooCommerce')) {
		// Cargar scripts
		function load_scripts()
		{
			wp_enqueue_style('gbsPlugin-style', PLUGIN_URL . 'assets/css/style.css');
			wp_enqueue_script('gbsPlugin-script', PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0', true);
		}
		add_action('wp_enqueue_scripts', 'load_scripts');

		// Cargar el archivo de métodos de pago
		require_once(PLUGIN_DIR . 'includes/class-wc-gateway-a-cuenta.php');

		// Registrar el método de pago
		add_filter('woocommerce_payment_gateways', 'gbs_add_gateway_class');
		function gbs_add_gateway_class($gateways)
		{
			$gateways[] = 'WC_Gateway_A_Cuenta';
			return $gateways;
		}
	}
}

add_action('plugins_loaded', 'init');

// Filtrar la visibilidad del método de pago "A Cuenta" basado en el rol
add_filter('woocommerce_available_payment_gateways', 'gbs_filter_gateways');
function gbs_filter_gateways($gateways)
{
	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		if (!in_array('empresa_convenio', (array) $user->roles)) {
			unset($gateways['a_cuenta']);
		}
	} else {
		unset($gateways['a_cuenta']);
	}
	return $gateways;
}

// Añadir un campo personalizado en el perfil de usuario
function agregar_campo_empresa($user)
{
	if (current_user_can('edit_user', $user->ID)) {
?>
		<h3>Información de la Empresa</h3>
		<table class="form-table">
			<tr>
				<th><label for="empresa_convenio">Nombre de la Empresa</label></th>
				<td><input type="text" name="empresa_convenio" value="<?php echo esc_attr(get_user_meta($user->ID, 'empresa_convenio', true)); ?>" class="regular-text" /></td>
			</tr>
		</table>
<?php
	}
}
add_action('show_user_profile', 'agregar_campo_empresa');
add_action('edit_user_profile', 'agregar_campo_empresa');

// Guardar el campo personalizado
function guardar_campo_empresa($user_id)
{
	if (current_user_can('edit_user', $user_id)) {
		update_user_meta($user_id, 'empresa_convenio', sanitize_text_field($_POST['empresa_convenio']));
	}
}
add_action('personal_options_update', 'guardar_campo_empresa');
add_action('edit_user_profile_update', 'guardar_campo_empresa');

// Función para obtener el saldo pendiente de una empresa específica
function obtener_saldo_pendiente_usuario($username)
{
	if (!class_exists('WooCommerce')) {
		return 0; // Retorna 0 si WooCommerce no está activo
	}

	$user = get_user_by('login', $username);
	if (!$user) {
		return 0; // Retorna 0 si el usuario no existe
	}

	$total_deuda = 0;

	// Argumentos para obtener pedidos pendientes o en espera del usuario específico
	$args = array(
		'customer' => $user->ID,
		'status' => array('on-hold', 'pending'),
		'meta_key' => '_payment_method',
		'meta_value' => 'a_cuenta',
		'limit' => -1
	);

	$pedidos = wc_get_orders($args);

	// Recorrer los pedidos y sumar el total de cada pedido
	foreach ($pedidos as $pedido) {
		$total_deuda += $pedido->get_total();
	}

	return $total_deuda;
}

// Crear un shortcode para mostrar el saldo pendiente de una empresa en cualquier página
function mostrar_saldo_pendiente_usuario_shortcode($atts)
{
	$atts = shortcode_atts(array(
		'username' => '',
	), $atts);

	$username = sanitize_text_field($atts['username']);
	$saldo_pendiente = obtener_saldo_pendiente_usuario($username);

	return "Tienes un saldo pendiente de $" . number_format($saldo_pendiente, 2);
}
add_shortcode('saldo_pendiente_usuario', 'mostrar_saldo_pendiente_usuario_shortcode');

// Cambiar el texto del botón "Realizar Pedido" si el usuario no tiene convenio
add_filter('woocommerce_order_button_text', 'cambiar_texto_boton_pedido_para_convenio');
function cambiar_texto_boton_pedido_para_convenio($button_text)
{
	$user_id = get_current_user_id();
	$tiene_convenio = get_user_meta($user_id, 'empresa_convenio', true);

	// Si el usuario no tiene convenio, cambia el texto del botón
	if (empty($tiene_convenio)) {
		return 'Solicitar Convenio';
	}

	return $button_text;
}

// Redireccionar a una página de solicitud de convenio si el usuario no tiene convenio y hace clic en el botón
add_action('woocommerce_checkout_process', 'redireccionar_solicitud_convenio');
function redireccionar_solicitud_convenio()
{
	$user_id = get_current_user_id();
	$tiene_convenio = get_user_meta($user_id, 'empresa_convenio', true);

	if (empty($tiene_convenio)) {
		// Cambia 'url_de_solicitud' por el enlace a tu página de solicitud de convenio
		wp_redirect(home_url('/solicitar-convenio'));
		exit;
	}
}
