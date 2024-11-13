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
