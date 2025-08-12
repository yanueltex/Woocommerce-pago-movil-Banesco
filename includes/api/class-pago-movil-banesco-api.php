<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pago_Movil_Banesco_API {

private $gateway;
    private $log_enabled = true;
    private $log_file;
    private $log_path;

    public function __construct($gateway) {
        $this->gateway = $gateway;
        $this->log_file = 'pago-movil-api.log';
        $this->log_path = plugin_dir_path(__FILE__) . $this->log_file;
        $this->initialize_log();
    }

    private function initialize_log() {
        if ($this->log_enabled && !file_exists($this->log_path)) {
            file_put_contents($this->log_path, "=== Inicio del Log Pago Móvil Banesco ===\n");
        }
    }

    private function log($message, $data = null) {
        if (!$this->log_enabled) return;
        
        $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        
        if ($data) {
            $log_entry .= 'Data: ' . print_r($data, true) . "\n";
        }
        
        $log_entry .= "-------------------------\n";
        
        file_put_contents($this->log_path, $log_entry, FILE_APPEND);
    }

    public function get_access_token($client_id, $client_secret, $username, $password) {
        $this->log("Iniciando solicitud de token de acceso");
        
        $token_url = 'https://sso-sso-project.apps.proplakur.banesco.com/auth/realms/realm-api-prd/protocol/openid-connect/token';

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
            ),
            'body' => http_build_query(array(
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
                'scope' => 'CONSULTA DE TRANSACCIONES',
            )),
        );

        $this->log("Enviando solicitud a API de autenticación", [
            'url' => $token_url,
            'headers' => $args['headers'],
            'body' => [
                'grant_type' => 'password',
                'username' => $username,
                'password' => '*******',
                'scope' => 'CONSULTA DE TRANSACCIONES'
            ]
        ]);

        $response = wp_remote_post($token_url, $args);

        if (is_wp_error($response)) {
            $this->log("Error en solicitud de token", [
                'error' => $response->get_error_message()
            ]);
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        $this->log("Respuesta de API de autenticación", [
            'status_code' => $response_code,
            'body' => $response_body
        ]);

        return isset($response_body['access_token']) ? $response_body['access_token'] : false;
    }

    public function validate_transaction($reference_number, $startDt, $bankId, $phoneNum, $amount) {
        $access_token = $this->get_access_token(
            $this->gateway->get_option('client_id'),
            $this->gateway->get_option('client_secret'),
            $this->gateway->get_option('username'),
            $this->gateway->get_option('password')
        );
    
        if (!$access_token) {
            $this->log('No se pudo obtener token de acceso para validar transacción');
            return false;
        }
    
        $api_url = 'https://sid-validador-consulta-de-transacciones-3scale-apicast-61e25ec.apps.proplakur.banesco.com/financial-account/transactions';
    
        $request_body = array(
            'dataRequest' => array(
                'device' => array(
                    'type' => 'Notebook',
                    'description' => $_SERVER['HTTP_USER_AGENT'],
                    'ipAddress' => $_SERVER['REMOTE_ADDR'],
                ),
                'securityAuth' => array(
                    'sessionId' => '',
                ),
                'transaction' => array(
                    'referenceNumber' => $reference_number,
                    'startDt' => $startDt,
                    'endDt' => $startDt,
                    'phoneNum' => $phoneNum,
                    'bankId' => $bankId,
                    'amount' => $amount,
                ),
            ),
        );
    
        $this->log('Validando transacción', [
            'api_url' => $api_url,
            'request_body' => $request_body,
            'token' => substr($access_token, 0, 10) . '...'
        ]);
    
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => json_encode($request_body),
            'timeout' => 15 // Timeout extendido a 15 segundos
        );
    
        $response = wp_remote_post($api_url, $args);
    
        if (is_wp_error($response)) {
            $this->log('Error en la validación de transacción', [
                'error' => $response->get_error_message()
            ]);
            return false;
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->log('Respuesta de validación', [
            'status_code' => wp_remote_retrieve_response_code($response),
            'body' => $body
        ]);
    
        return $body;
    }

    public function process_transaction_response($order_id, $response) {
        $this->log('Procesando respuesta para orden ' . $order_id, $response);
        
        if (!$response || !isset($response['httpStatus'])) {
            $this->log('Respuesta inválida o faltante', $response);
            return false;
        }
    
        $order = wc_get_order($order_id);
        $status = $response['httpStatus']['statusCode'];
    
        if ($status === '200') {
            $this->log('Transacción exitosa', [
                'order_id' => $order_id,
                'status' => $status,
                'response' => $response
            ]);
            
            update_post_meta($order_id, '_pago_movil_reference_status', '1');
            $order->update_status('completed', 'Pago confirmado mediante Pago Móvil Banesco.');
            return true;
        } else {
            $this->log('Transacción fallida', [
                'order_id' => $order_id,
                'status' => $status,
                'response' => $response
            ]);
            
            $order->update_status('pending', 'Pendiente de pago. Validación de Pago Móvil fallida.');
            return false;
        }
    }
}