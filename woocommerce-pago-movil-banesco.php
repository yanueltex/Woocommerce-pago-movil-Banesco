<?php
/*
Plugin Name: WooCommerce Pago Móvil Banesco
Description: Método de pago personalizado para Pago Móvil Banesco en WooCommerce.
Version: 1.1
Author: Alejandro
*/

if (!defined('ABSPATH')) {
    exit;
}

// Cargar la clase del método de pago
add_action('plugins_loaded', 'pago_movil_banesco_init_gateway_class');
function pago_movil_banesco_init_gateway_class() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Incluir la clase del gateway
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-pago-movil-banesco.php';

    // Registrar el método de pago
    add_filter('woocommerce_payment_gateways', 'pago_movil_banesco_add_gateway_class');
    function pago_movil_banesco_add_gateway_class($gateways) {
        $gateways[] = 'WC_Gateway_Pago_Movil_Banesco';
        return $gateways;
    }
}

// Mostrar detalles del pago en el área de administración
add_action('woocommerce_admin_order_data_after_billing_address', 'mostrar_detalles_pago_movil_admin', 10, 1);
function mostrar_detalles_pago_movil_admin($order) {
    $tipo_pago = get_post_meta($order->get_id(), '_tipo_pago', true);
    $monto_transferido = get_post_meta($order->get_id(), '_pago_movil_amount', true);
    $order_total = $order->get_total();
    $reference = get_post_meta($order->get_id(), '_pago_movil_reference', true);
    if ($reference) {
        echo '<div class="address">';
        echo '<p><strong>Número de Referencia:</strong> ' . esc_html($reference) . '</p>';
        echo '</div>';
    }

    if ($tipo_pago === 'Pago Móvil') {
        echo '<div class="address">';
        echo '<p><strong>Tipo de Pago:</strong> ' . esc_html($tipo_pago) . '</p>';
        echo '<p><strong>Monto Transferido:</strong> ' . wc_price($monto_transferido) . '</p>';

        // Mostrar el monto faltante si el estado es "Pago Incompleto"
        if ($order->get_status() === 'pago-incompleto') {
            $monto_faltante = $order_total - $monto_transferido;
            echo '<p><strong>Monto Faltante:</strong> ' . wc_price($monto_faltante) . '</p>';
        }
        echo '</div>';
    }
}

// Mostrar el número de referencia en el área de cliente
add_action('woocommerce_order_details_after_order_table', 'mostrar_referencia_pago_movil_cliente', 10, 1);
function mostrar_referencia_pago_movil_cliente($order) {
    $reference = get_post_meta($order->get_id(), '_pago_movil_reference', true);
    if ($reference) {
        echo '<p><strong>Número de Referencia:</strong> ' . esc_html($reference) . '</p>';
    }
}

// Registrar el nuevo estado personalizado "Pago Incompleto"
add_action('init', 'registrar_estado_pago_incompleto');
function registrar_estado_pago_incompleto() {
    register_post_status('wc-pago-incompleto', array(
        'label'                     => 'Pago Incompleto',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Pago Incompleto <span class="count">(%s)</span>', 'Pago Incompleto <span class="count">(%s)</span>'),
    ));
}

// Agregar el nuevo estado a la lista de estados de pedidos en WooCommerce
add_filter('wc_order_statuses', 'agregar_estado_pago_incompleto');
function agregar_estado_pago_incompleto($order_statuses) {
    $order_statuses['wc-pago-incompleto'] = 'Pago Incompleto';
    return $order_statuses;
}

// Agregar CSS para el color naranja del estado
add_action('admin_head', 'estilo_estado_pago_incompleto');
function estilo_estado_pago_incompleto() {
    echo '<style>
        .order-status.status-pago-incompleto {
            background-color: #ffa500;
            color: #000;
        }
    </style>';
}

// Cargar archivo de depuración (solo en modo de desarrollo)
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once plugin_dir_path(__FILE__) . 'includes/debug/debug.php';
}