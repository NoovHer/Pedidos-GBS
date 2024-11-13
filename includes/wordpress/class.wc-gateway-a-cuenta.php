<?php

if (! defined('ABSPATH')) {
	exit;
}

// Clase para el método de pago "A Cuenta"
class WC_Gateway_A_Cuenta extends WC_Payment_Gateway
{

	public function __construct()
	{
		$this->id = 'a_cuenta';
		$this->icon = ''; // Puedes añadir aquí la URL de un icono para el método de pago
		$this->has_fields = false;
		$this->method_title = 'A Cuenta';
		$this->method_description = 'Permite a las empresas con convenio pagar a crédito.';

		// Cargar los campos de configuración
		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');

		// Guardar los ajustes
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	// Define los campos de configuración
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' => 'Habilitar/Deshabilitar',
				'type' => 'checkbox',
				'label' => 'Habilitar método de pago A Cuenta',
				'default' => 'yes'
			),
			'title' => array(
				'title' => 'Título',
				'type' => 'text',
				'description' => 'Título mostrado al usuario durante el pago.',
				'default' => 'Pago A Cuenta',
				'desc_tip' => true,
			),
			'description' => array(
				'title' => 'Descripción',
				'type' => 'textarea',
				'description' => 'Descripción que el cliente verá al seleccionar el método de pago.',
				'default' => 'Pago a crédito para empresas con convenio.',
			),
		);
	}

	// Procesar el pago
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);

		// Marcar el pedido como "on-hold" (en espera)
		$order->update_status('on-hold', 'Esperando pago a crédito.');

		// Reducir inventario
		wc_reduce_stock_levels($order_id);

		// Vaciar el carrito
		WC()->cart->empty_cart();

		// Retornar éxito y redirigir al cliente
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url($order)
		);
	}
}
