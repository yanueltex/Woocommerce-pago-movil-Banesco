<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Pago_Movil_Banesco extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'pago_movil_banesco';
        $this->icon = '/img/logobanesco.jpg';
        $this->has_fields = true;
        $this->method_title = 'Pago Móvil Banesco';
        $this->method_description = 'Pago mediante transferencia Pago Móvil con Banesco.';

        // Cargar la API
        require_once plugin_dir_path(__FILE__) . 'api/class-pago-movil-banesco-api.php';
        $this->api = new Pago_Movil_Banesco_API($this);

        // Configuración del método de pago
        $this->init_form_fields();
        $this->init_settings();

        // Mostrar los datos de configuración
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Guardar los ajustes del método de pago
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

        // Validar y procesar el pago
        add_action('woocommerce_checkout_process', array($this, 'validate_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_fields'));
        // Probar la conexión con la API
        add_action('admin_notices', array($this, 'test_api_connection'));
    }

    // Campos de configuración del método de pago
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Activar/Desactivar',
                'label'       => 'Activar Pago Móvil Banesco',
                'type'        => 'checkbox',
                'default'     => 'yes'
            ),
            'title' => array(
                'title'       => 'Título',
                'type'        => 'text',
                'description' => 'Título que ve el cliente durante el checkout.',
                'default'     => 'Pago Móvil Banesco'
            ),
            'description' => array(
                'title'       => 'Descripción',
                'type'        => 'textarea',
                'description' => 'Descripción que ve el cliente en el checkout.',
                'default'     => 'Realiza el pago a través de Pago Móvil Banesco. Por favor, ingresa los datos de la transferencia a continuación.'
            ),
            'account_info' => array(
                'title'       => 'Datos de cuenta',
                'type'        => 'textarea',
                'description' => 'Proporcione los datos para la transferencia Pago Móvil.',
                'default'     => 'Banco: Banesco, RIF: J-12345678-9, Cuenta: 0123456789'
            ),
            'client_id' => array(
                'title'       => 'Client ID',
                'type'        => 'text',
                'description' => 'Ingrese el Client ID proporcionado por Banesco.',
                'default'     => ''
            ),
            'client_secret' => array(
                'title'       => 'Client Secret',
                'type'        => 'password',
                'description' => 'Ingrese el Client Secret proporcionado por Banesco.',
                'default'     => ''
            ),
            'username' => array(
                'title'       => 'Username',
                'type'        => 'text',
                'description' => 'Ingrese el Username proporcionado por Banesco.',
                'default'     => ''
            ),
            'password' => array(
                'title'       => 'Password',
                'type'        => 'password',
                'description' => 'Ingrese el Password proporcionado por Banesco.',
                'default'     => ''
            ),
            'test_connection' => array(
                'title'       => 'Probar Conexión',
                'type'        => 'button',
                'description' => 'Haga clic para probar la conexión con la API de Banesco.',
                'default'     => 'Probar Conexión'
            )
        );
    }

    // Mostrar campos en el checkout
    public function payment_fields() {
        // Mostrar la descripción del método de pago
        echo wpautop(wptexturize($this->description));

        // Mostrar los datos de la cuenta
        $account_info = $this->get_option('account_info');
        if (!empty($account_info)) {
            echo wpautop(wptexturize($account_info));
        }

        // Mostrar el campo para el número de referencia
        echo '<p><label for="pago_movil_reference">Número de Referencia: <span class="required">*</span></label>';
        echo '<input type="text" name="pago_movil_reference" id="pago_movil_reference" class="input-text" placeholder="Ingresa el número de referencia" required></p>';

        // Mostrar el campo para la fecha de la transferencia
        echo '<p><label for="pago_movil_startDt">Fecha de la Transferencia: <span class="required">*</span></label>';
        echo '<input type="date" name="pago_movil_startDt" id="pago_movil_startDt" class="input-text" placeholder="YYYY-MM-DD" required></p>';

        // Mostrar el campo para el banco emisor
        echo '<p><label for="pago_movil_bankId">Banco Emisor: <span class="required">*</span></label>';
        echo '<select name="pago_movil_bankId" id="pago_movil_bankId" class="input-text" required>';
        echo '<option value="">Selecciona un banco</option>';
        echo '<option value="0102">Banco de Venezuela</option>';
        echo '<option value="0104">Venezolano de Crédito</option>';
        echo '<option value="0105">Mercantil</option>';
        echo '<option value="0114">Bancaribe</option>';
        echo '<option value="0134">Banesco</option>';
        echo '<option value="0108">Provincial</option>';
        echo '<option value="0172">Bancamiga</option>';
        echo '<option value="0151">BFC Banco Fondo Común</option>';
        echo '<option value="0156">100% Banco</option>';
        echo '<option value="0157">DelSur</option>';
        echo '<option value="0163">Banco del Tesoro</option>';
        echo '<option value="0166">Banco Agrícola de Venezuela</option>';
        echo '<option value="0168">Bancrecer</option>';
        echo '<option value="0169">R4, Banco Microfinanciero</option>';
        echo '<option value="0171">Banco Activo</option>';
        echo '<option value="0172">Bancamiga</option>';
        echo '<option value="0174">Banplus</option>';
        echo '<option value="0175">Banco Digital de los Trabajadores</option>';
        echo '<option value="0177">Banco de la FANB</option>';
        echo '<option value="0191">BNC Banco Nacional de Crédito</option>';
        echo '<option value="0128">Banco Caroní</option>';
        echo '<option value="0175">Banco Digital de los Trabajadores</option>';
        echo '<option value="0115">Banco Exterior</option>';
        echo '<option value="0138">Banco Plaza</option>';
        echo '<option value="0137">Banco SOFITASA</option>';
        echo '</select></p>';

        // Mostrar el campo para el número de teléfono
        echo '<p><label for="pago_movil_phoneNum">Número de Teléfono: <span class="required">*</span></label>';
        echo '<div style="display: flex; gap: 10px;">';
        echo '<select name="pago_movil_phone_operator" id="pago_movil_phone_operator" class="input-text" required>';
        echo '<option value="">Operadora</option>';
        echo '<option value="0414">0414</option>';
        echo '<option value="0424">0424</option>';
        echo '<option value="0412">0412</option>';
        echo '<option value="0416">0416</option>';
        echo '<option value="0426">0426</option>';
        echo '</select>';
        echo '<input type="text" name="pago_movil_phone_number" id="pago_movil_phone_number" class="input-text" placeholder="1234567" maxlength="7" required>';
        echo '</div></p>';
        
        // Mostrar el campo para el monto
        echo '<p><label for="pago_movil_amount">Monto: <span class="required">*</span></label>';
        echo '<input type="number" name="pago_movil_amount" id="pago_movil_amount" class="input-text" placeholder="Ej: 100.00" step="0.01" min="0.01" required></p>';
    }

    // Validar los campos
    public function validate_fields() {
        if (empty($_POST['pago_movil_reference'])) {
            wc_add_notice('Por favor ingresa el número de referencia de pago.', 'error');
        } elseif ($this->is_reference_duplicated(sanitize_text_field($_POST['pago_movil_reference']))) {
            wc_add_notice('Este número de referencia ya fue registrado y aprobado anteriormente.', 'error');
        }
        if (empty($_POST['pago_movil_startDt'])) {
            wc_add_notice('Por favor ingresa la fecha de la transferencia.', 'error');
        }
        if (empty($_POST['pago_movil_bankId'])) {
            wc_add_notice('Por favor selecciona el banco emisor.', 'error');
        }
        if (empty($_POST['pago_movil_phone_operator']) || empty($_POST['pago_movil_phone_number'])) {
            wc_add_notice('Por favor ingresa el número de teléfono completo.', 'error');
        } elseif (!preg_match('/^\d{7}$/', $_POST['pago_movil_phone_number'])) {
            wc_add_notice('El número de teléfono debe tener exactamente 7 dígitos.', 'error');
        }
        if (empty($_POST['pago_movil_amount']) || !is_numeric($_POST['pago_movil_amount']) || $_POST['pago_movil_amount'] <= 0) {
            wc_add_notice('Por favor ingresa un monto válido.', 'error');
        }
    }

    // Guardar los campos en los metadatos del pedido
    public function save_fields($order_id) {
        if (!empty($_POST['pago_movil_reference'])) {
            update_post_meta($order_id, '_pago_movil_reference', sanitize_text_field($_POST['pago_movil_reference']));
        }
        if (!empty($_POST['pago_movil_startDt'])) {
            update_post_meta($order_id, '_pago_movil_startDt', sanitize_text_field($_POST['pago_movil_startDt']));
        }
        if (!empty($_POST['pago_movil_bankId'])) {
            update_post_meta($order_id, '_pago_movil_bankId', sanitize_text_field($_POST['pago_movil_bankId']));
        }
        if (!empty($_POST['pago_movil_phone_operator']) && !empty($_POST['pago_movil_phone_number'])) {
            $phone_operator = sanitize_text_field($_POST['pago_movil_phone_operator']);
            $phone_number = sanitize_text_field($_POST['pago_movil_phone_number']);
            $full_phone_number = '58' . substr($phone_operator, 1) . $phone_number; // Elimina el "0" y concatena
            update_post_meta($order_id, '_pago_movil_phoneNum', $full_phone_number);
        }
        if (!empty($_POST['pago_movil_amount'])) {
            update_post_meta($order_id, '_pago_movil_amount', sanitize_text_field($_POST['pago_movil_amount']));
        }
    }

    // Procesar el pago
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
    
        if (!$order) {
            wc_add_notice('Error: No se pudo crear el pedido.', 'error');
            return;
        }
    
        // Obtener datos del pago
        $reference_number = sanitize_text_field($_POST['pago_movil_reference']);
        $startDt = sanitize_text_field($_POST['pago_movil_startDt']);
        $bankId = sanitize_text_field($_POST['pago_movil_bankId']);
        $phone_operator = sanitize_text_field($_POST['pago_movil_phone_operator']);
        $phone_number = sanitize_text_field($_POST['pago_movil_phone_number']);
        $phoneNum = '58' . substr($phone_operator, 1) . $phone_number;
        $amount = floatval($_POST['pago_movil_amount']);
        $order_total = floatval($order->get_total());
    
        // Guardar metadatos básicos
        update_post_meta($order_id, '_tipo_pago', 'Pago Móvil');
        update_post_meta($order_id, '_pago_movil_amount', $amount);
        update_post_meta($order_id, '_pago_movil_reference', $reference_number);
        update_post_meta($order_id, '_pago_movil_reference_status', '0');
    
        // Validar con la API
        $response = $this->api->validate_transaction($reference_number, $startDt, $bankId, $phoneNum, $amount);
        
        // Manejo de errores de conexión
        if ($response === false) {
            $order->update_status('failed', 'Error al conectar con la API de Banesco');
            wc_add_notice('Error temporal al procesar tu pago. Por favor intenta nuevamente.', 'error');
            return array(
                'result' => 'failure',
                'redirect' => wc_get_checkout_url(),
            );
        }
    
        // Manejo de errores específicos de la API
        if (isset($response['httpStatus']['statusCode'])) {
            // Caso especial para error 70001 (Consulta sin resultados)
            if ($response['httpStatus']['statusCode'] == '70001') {
                $order->update_status('pending', 'Pago no verificado por Banesco: ' . $response['httpStatus']['statusDesc']);
                wc_add_notice('No pudimos verificar tu pago automáticamente. Por favor verifica sus datos de pago móvil.', 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url(),
                );
            }
            
            // Otros errores de la API
            if ($response['httpStatus']['statusCode'] != '200') {
                $order->update_status('pending', 'Error en validación: ' . $response['httpStatus']['statusDesc']);
                wc_add_notice('Error al verificar tu pago: ' . $response['httpStatus']['statusDesc'], 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => wc_get_checkout_url(),
                );
            }
        }
    
        // Procesar respuesta exitosa
        $transaction_valid = $this->api->process_transaction_response($order_id, $response);
        $monto_insuficiente = ($amount < $order_total);
    
        if ($monto_insuficiente) {
            $order->update_status('pago-incompleto', 'Monto inferior al total. API validada');
            update_post_meta($order_id, '_pago_movil_monto_faltante', $order_total - $amount);
            wc_add_notice('Hemos recibido tu pago, pero el monto es inferior al total. Por favor contacta al soporte.', 'notice');
        } else {
            $order->reduce_order_stock();
            $order->update_status('completed', 'Pago validado por API Banesco');
        }
    
        WC()->cart->empty_cart();
    
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    // Mostrar los datos en la página de agradecimiento
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        $reference = get_post_meta($order_id, '_pago_movil_reference', true);
        $startDt = get_post_meta($order_id, '_pago_movil_startDt', true);
        $bankId = get_post_meta($order_id, '_pago_movil_bankId', true);
        $phoneNum = get_post_meta($order_id, '_pago_movil_phoneNum', true);
        $amount = get_post_meta($order_id, '_pago_movil_amount', true);
        $order_total = $order->get_total();
    
        // Mostrar los detalles del pago
        if ($reference) {
            echo '<p><strong>Número de Referencia:</strong> ' . esc_html($reference) . '</p>';
        }
        if ($startDt) {
            echo '<p><strong>Fecha de la Transferencia:</strong> ' . esc_html($startDt) . '</p>';
        }
        if ($bankId) {
            echo '<p><strong>Banco Emisor:</strong> ' . esc_html($this->get_bank_name($bankId)) . '</p>';
        }
        if ($phoneNum) {
            echo '<p><strong>Número de Teléfono:</strong> ' . esc_html($phoneNum) . '</p>';
        }
        if ($amount) {
            echo '<p><strong>Monto Transferido:</strong> ' . esc_html($amount) . '</p>';
        }
    
        // Mostrar mensaje personalizado si el monto es inferior
        if ($amount < $order_total) {
            echo '<div style="border: 1px solid #ffcc00; padding: 15px; margin: 20px 0; background-color: #fff8e1; border-radius: 5px;">';
            echo '<p style="color: #d32f2f; font-weight: bold;">Has transferido un monto inferior al costo total de tu factura.</p>';
            echo '<p>Por favor, contacta con uno de nuestros asesores de ventas para completar tu compra.</p>';
            echo '<a href="https://wa.me/584244346395" target="_blank" style="background-color: #25d366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;">';
            echo 'Contactar por WhatsApp';
            echo '</a>';
            echo '</div>';
        }
    }

    // Obtener el nombre del banco
    private function get_bank_name($bankId) {
        $banks = array(
            '0102' => 'Banco de Venezuela',
            '0104' => 'Venezolano de Crédito',
            '0105' => 'Mercantil',
            '0114' => 'Bancaribe',
            '0134' => 'Banesco',
            '0108' => 'Provincial',
            '0134' => 'Bancamiga',
            '0151' => 'BFC Banco Fondo Común',
            '0156' => '100% Banco',
            '0157' => 'DelSur',
            '0163' => 'Banco del Tesoro',
            '0166' => 'Banco Agrícola de Venezuela',
            '0168' => 'Bancrecer',
            '0169' => 'R4, Banco Microfinanciero',
            '0171' => 'Banco Activo',
            '0172' => 'Bancamiga',
            '0174' => 'Banplus',
            '0175' => 'Bicentenario del Pueblo',
            '0177' => 'Banco de la FANB',
            '0191' => 'BNC Banco Nacional de Crédito',
            '0128' => 'Banco Caroní',
            '0175' => 'Banco Digital de los Trabajadores',
            '0115' => 'Banco Exterior',
            '0138' => 'Banco Plaza',
            '0137' => 'Banco SOFITASA',
        );
        return isset($banks[$bankId]) ? $banks[$bankId] : 'Desconocido';
    }

    // Probar la conexión con la API
    public function test_api_connection() {
        if (isset($_POST['test_connection'])) {
            $client_id = $this->get_option('client_id');
            $client_secret = $this->get_option('client_secret');
            $username = $this->get_option('username');
            $password = $this->get_option('password');

            if (empty($client_id) || empty($client_secret) || empty($username) || empty($password)) {
                echo '<div class="notice notice-error"><p>Por favor, complete todas las credenciales de la API.</p></div>';
                return;
            }

            $token = $this->api->get_access_token($client_id, $client_secret, $username, $password);

            if ($token) {
                echo '<div class="notice notice-success"><p>Conectado con API Banesco.</p></div>';
                echo '<div class="notice notice-info"><p>Token obtenido: ' . esc_html($token) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Imposible conectar con API Banesco. Verifique las credenciales.</p></div>';
            }
        }
    }

    // Mostrar el botón de prueba de conexión
    public function generate_button_html($key, $data) {
        $field = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class' => 'button-secondary',
            'css' => '',
            'custom_attributes' => array(),
            'desc_tip' => false,
            'description' => '',
            'title' => '',
        );

        $data = wp_parse_args($data, $defaults);

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field); ?>"><?php echo wp_kses_post($data['title']); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                    <button class="<?php echo esc_attr($data['class']); ?>" type="submit" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($field); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php echo $this->get_custom_attribute_html($data); ?>><?php echo wp_kses_post($data['default']); ?></button>
                    <?php echo $this->get_description_html($data); ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
    
    private function is_reference_duplicated($reference_number) {
        global $wpdb;
    
        // Buscar el número de referencia CON ESTATUS 1 (aprobado)
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_pago_movil_reference' 
            AND meta_value = %s
            AND post_id IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_pago_movil_reference_status' 
                AND meta_value = '1'
            )",
            $reference_number
        );
    
        return $wpdb->get_var($query) > 0;
    }
}