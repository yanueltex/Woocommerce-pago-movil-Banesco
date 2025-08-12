<?php
if (!defined('ABSPATH')) {
    exit;
}

// Función para mostrar mensajes de depuración
function pago_movil_banesco_debug($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<pre>';
        print_r($message);
        echo '</pre>';
    }
}

// Ejemplo de uso
add_action('init', 'pago_movil_banesco_test_api');
function pago_movil_banesco_test_api() {
    if (isset($_GET['test_pago_movil_api'])) {
        require_once plugin_dir_path(__FILE__) . '../api/class-pago-movil-banesco-api.php';
        $api = new Pago_Movil_Banesco_API();

        // Obtener token de acceso
        $token = $api->get_access_token();
        pago_movil_banesco_debug($token);

        // Validar una transacción de prueba
        $response = $api->validate_transaction('1234567890', 100.00, '01340950160002538514', '04141234567', '0134');
        pago_movil_banesco_debug($response);
    }
}