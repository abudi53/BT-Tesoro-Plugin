<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_BT_Tesoro extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'bt_tesoro';
        $this->has_fields = true;
        $this->method_title = 'Pago C2P Banco del Tesoro';
        $this->method_description = 'Permite pagos a través de C2P del Banco del Tesoro';

        $this->init_form_fields();
        $this->init_settings();

        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->exchange_rate_api_url = $this->get_option('exchange_rate_api_url');
        $this->bank_api_url         = $this->get_option('bank_api_url');
        $this->canal                = $this->get_option('canal');
        $this->RIF                  = $this->get_option('RIF');
        $this->concepto             = $this->get_option('concepto');
        $this->codAfiliado          = $this->get_option('codAfiliado');
        $this->comercio             = $this->get_option('comercio');

        // Set the logo URL to the internal asset
        $this->logo_url = plugin_dir_url(__FILE__) . '../assets/logo.png';

        $this->supports = [
            'products',
            'refunds',
            'woocommerce_pay',
            'woocommerce_order_tracking',
            'default_credit_card_form',
            'checkout_blocks',
        ];
        // Save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable Banco del Tesoro Payment',
                'default' => 'yes'
            ],
            'title' => [
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Pago C2P Banco del Tesoro',
                'desc_tip'    => true
            ],
            'description' => [
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pague usando C2P del Banco del Tesoro'
            ],
            // Add new fields below
            'exchange_rate_api_url' => [
                'title'       => 'Exchange Rate API URL',
                'type'        => 'text',
                'description' => 'URL of the exchange rate API.',
                'default'     => 'https://pydolarve.org/api/v1/dollar?page=bcv',
                'desc_tip'    => true
            ],
            'bank_api_url' => [
                'title'       => 'Banco API URL',
                'type'        => 'text',
                'description' => 'URL de la API del Banco del Tesoro.',
                'desc_tip'    => true
            ],
            'canal' => [
                'title'       => 'Canal',
                'type'        => 'text',
                'description' => 'Canal value used in API requests.',
                'desc_tip'    => true
            ],
            'RIF' => [
                'title'       => 'RIF',
                'type'        => 'text',
                'description' => 'RIF del comercio value used in API requests.',
                'desc_tip'    => true
            ],
            'concepto' => [
                'title'       => 'Concepto',
                'type'        => 'text',
                'description' => 'Concepto value used in API requests.',
                'default'     => 'prueba',
                'desc_tip'    => true
            ],
            'codAfiliado' => [
                'title'       => 'Código de Afiliado',
                'type'        => 'text',
                'description' => 'Código de Afiliado used in API requests.',
                'desc_tip'    => true
            ],
            'comercio' => [
                'title'       => 'Comercio',
                'type'        => 'text',
                'description' => 'Nombre del Comercio value used in API requests.',
                'desc_tip'    => true
            ],
        ];
    }
public function process_admin_options() {
    $post_data = $this->get_post_data();

    // Retrieve the settings values from the posted data
    $exchange_rate_api_url = isset($post_data[$this->get_field_key('exchange_rate_api_url')]) ? trim($post_data[$this->get_field_key('exchange_rate_api_url')]) : '';
    $bank_api_url          = isset($post_data[$this->get_field_key('bank_api_url')]) ? trim($post_data[$this->get_field_key('bank_api_url')]) : '';
    $canal                 = isset($post_data[$this->get_field_key('canal')]) ? trim($post_data[$this->get_field_key('canal')]) : '';
    $RIF                   = isset($post_data[$this->get_field_key('RIF')]) ? trim($post_data[$this->get_field_key('RIF')]) : '';
    $concepto              = isset($post_data[$this->get_field_key('concepto')]) ? trim($post_data[$this->get_field_key('concepto')]) : '';
    $codAfiliado           = isset($post_data[$this->get_field_key('codAfiliado')]) ? trim($post_data[$this->get_field_key('codAfiliado')]) : '';
    $comercio              = isset($post_data[$this->get_field_key('comercio')]) ? trim($post_data[$this->get_field_key('comercio')]) : '';

    $errors = array();

    // Validate Exchange Rate API URL
    if (empty($exchange_rate_api_url) || !filter_var($exchange_rate_api_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL de la API de tasa de cambio es obligatoria y debe ser una URL válida.';
    }

    // Validate Bank API URL
    if (empty($bank_api_url) || !filter_var($bank_api_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL de la API del banco es obligatoria y debe ser una URL válida.';
    }

    // Validate required fields
    if (empty($canal)) {
        $errors[] = 'El campo "Canal" es obligatorio.';
    }
    if (empty($RIF)) {
        $errors[] = 'El campo "RIF" es obligatorio.';
    }
    if (empty($concepto)) {
        $errors[] = 'El campo "Concepto" es obligatorio.';
    }
    if (empty($codAfiliado)) {
        $errors[] = 'El campo "Código de Afiliado" es obligatorio.';
    }

    // If there are validation errors, display them and do not save settings
    if (!empty($errors)) {
        foreach ($errors as $error) {
            WC_Admin_Settings::add_error($error);
        }
        // Reload settings to prevent saving invalid data
        $this->init_settings();
        return false;
    } else {
        // No validation errors, proceed to save settings
        return parent::process_admin_options();
    }
}

    public function payment_fields() {

        $exchange_rate = get_transient('bt_tesoro_exchange_rate');

        if (false === $exchange_rate) {
        // Fetch the exchange rate from the API
            $response_rate = wp_remote_get($this->exchange_rate_api_url);

        if (is_wp_error($response_rate)) {
            echo '<p><strong>Error al obtener la tasa de cambio.</strong></p>';
        } else {
            $body_rate = wp_remote_retrieve_body($response_rate);
            $data_rate = json_decode($body_rate, true);

            if (isset($data_rate['monitors']['usd']['price'])) {
                $exchange_rate = floatval($data_rate['monitors']['usd']['price']);
                set_transient('bt_tesoro_exchange_rate', $exchange_rate, 60 * 10); // Cache for 10 minutes

                // Get the order total in USD
                $order_total_usd = WC()->cart->get_total('numeric');

                // Convert the order total from USD to BS
                $order_total_bs = $order_total_usd * $exchange_rate;
                $order_total_bs_formatted = number_format($order_total_bs, 2, ',', '.');

                // Display the converted amount
                echo '<p><strong>Total a pagar en Bolívares (BS): </strong>' . esc_html($order_total_bs_formatted) . ' BS</p>';
            } else {
                echo '<p><strong>Error al obtener la tasa de cambio.</strong></p>';
            }
        }
    } else {

        // Get the order total in USD
        $order_total_usd = WC()->cart->get_total('numeric');

        // Convert the order total from USD to BS
        $order_total_bs = $order_total_usd * $exchange_rate;
        $order_total_bs_formatted = number_format($order_total_bs, 2, ',', '.');

        // Display the converted amount
        echo '<p><strong>Total a pagar en Bolívares (BS): </strong>' . esc_html($order_total_bs_formatted) . ' BS</p>';
    }
        // Display the logo and title
    if (!empty($this->logo_url)) {
        echo '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
        echo '<img src="' . esc_url($this->logo_url) . '" alt="Bank Logo" style="max-height: 50px; margin-right: 10px;">';
        echo '<h3 style="margin: 0;">' . esc_html($this->title) . '</h3>';
        echo '</div>';
    } else {
        echo '<h3>' . esc_html($this->title) . '</h3>';
    }
    
        ?>
        <div class="form-row form-row-wide">
             <label>Cédula de identidad <span class="required">*</span></label>
            <div style="display: flex; align-items: center;">
                <select name="bt_cedula_tipo" class="input-select" style="width: 60px; margin-right: 5px;" required>
                    <option value="V">V</option>
                    <option value="E">E</option>
                    <option value="J">J</option>
                </select>
                <input type="text" class="input-text" name="bt_cedula_numero" placeholder="Ej: 12345678" required>
            </div>
        </div>
        <div class="form-row form-row-wide">
            <label>Banco <span class="required">*</span></label>
            <select name="bt_banco" class="input-select" required>
                <?php
                $bancos = [
                    ["codigo" => "0163", "nombre" => "BANCO DEL TESORO, C.A BANCO U"],
                    ["codigo" => "0172", "nombre" => "BANCAMIGA BANCO MICROFINANCIE"],
                    ["codigo" => "0171", "nombre" => "BANCO ACTIVO"],
                    ["codigo" => "0166", "nombre" => "BANCO AGRÍCOLA DE VENEZUELA C"],
                    ["codigo" => "0128", "nombre" => "BANCO CARONÍ, C.A BANCO UNIVE"],
                    ["codigo" => "0102", "nombre" => "BANCO DE VENEZUELA, SACA BANC"],
                    ["codigo" => "0114", "nombre" => "BANCO DEL CARIBE, C.A. BANCO"],
                    ["codigo" => "0007", "nombre" => "BANCO DIGITAL DE LOS TRABAJAD"],
                    ["codigo" => "0115", "nombre" => "BANCO EXTERIOR, C.A. BANCO UN"],
                    ["codigo" => "0105", "nombre" => "BANCO MERCANTIL,C.A SACA BANC"],
                    ["codigo" => "0191", "nombre" => "BANCO NACIONAL DE CRÉDITO C.A"],
                    ["codigo" => "0116", "nombre" => "BANCO OCCIDENTAL DE DESCUENTO"],
                    ["codigo" => "0138", "nombre" => "BANCO PLAZA, C.A"],
                    ["codigo" => "0108", "nombre" => "BANCO PROVINCIAL,S.A BANCO UN"],
                    ["codigo" => "0137", "nombre" => "BANCO SOFITASA BANCO UNIVERSA"],
                    ["codigo" => "0104", "nombre" => "BANCO VENEZOLANO DE CRÉDITO,S"],
                    ["codigo" => "0168", "nombre" => "BANCRECER SA BANCO DE DESARRO"],
                    ["codigo" => "0134", "nombre" => "BANESCO BANCO UNIVERSAL, C.A"],
                    ["codigo" => "0177", "nombre" => "BANFANB"],
                    ["codigo" => "0174", "nombre" => "BANPLUS ENTIDAD DE AHORRO Y P"],
                    ["codigo" => "0157", "nombre" => "DEL SUR BANCO UNIVERSAL C.A."],
                    ["codigo" => "0151", "nombre" => "FONDO COMÚN BANCO UNIVERSAL,C"],
                    ["codigo" => "0169", "nombre" => "MI BANCO"],
                    ["codigo" => "0178", "nombre" => "N58 BANCO DIGITAL"],
                    ["codigo" => "0156", "nombre" => "100 % BANCO, BANCO COMERCIAL"]
                ];

                foreach ($bancos as $banco) {
                    echo '<option value="' . esc_attr($banco['codigo']) . '">' . esc_html($banco['nombre']) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-row form-row-wide">
            <label>Teléfono <span class="required">*</span></label>
            <div style="display: flex; align-items: center;">
                <select name="bt_telefono_codigo" class="input-select" style="width: 80px; margin-right: 5px;" required>
                    <option value="0412">0412</option>
                    <option value="0414">0414</option>
                    <option value="0424">0424</option>
                    <option value="0416">0416</option>
                    <option value="0426">0426</option>
                </select>
                <input type="text" class="input-text" name="bt_telefono_numero" placeholder="Ej: 1234567" required>
            </div>
        </div>
        <div class="form-row form-row-wide">
            <label>Clave C2P <span class="required">*</span></label>
            <input type="text" class="input-text" name="bt_clave" required>
        </div>
        <?php
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Fetch the exchange rate from the API
        $response_rate = wp_remote_get($this->exchange_rate_api_url);
        if (is_wp_error($response_rate)) {
            wc_add_notice('Error al obtener la tasa de cambio.', 'error');
            return;
        }

        $body_rate = wp_remote_retrieve_body($response_rate);
        $data_rate = json_decode($body_rate, true);

        if (isset($data_rate['monitors']['usd']['price'])) {
            $exchange_rate = floatval($data_rate['monitors']['usd']['price']);
        } else {
            wc_add_notice('Error al obtener la tasa de cambio.', 'error');
            return;
        }

        // Convert the order total from USD to BS
        $order_total_usd = $order->get_total();
        $order_total_bs = $order_total_usd * $exchange_rate;
        $order_total_bs_formatted = number_format($order_total_bs, 2, '.', '');

        $cedula_tipo = sanitize_text_field($_POST['bt_cedula_tipo']);
        $cedula_numero = sanitize_text_field($_POST['bt_cedula_numero']);
        
        // Validate that $cedula_numero contains only digits
        if (!ctype_digit($cedula_numero)) {
            wc_add_notice('El número de cédula debe contener solo dígitos.', 'error');
            return;
        }

        $cedula_completa = $cedula_tipo . $cedula_numero;

            // **Retrieve and combine 'Teléfono' values**
        $telefono_codigo = sanitize_text_field($_POST['bt_telefono_codigo']);
        $telefono_numero = sanitize_text_field($_POST['bt_telefono_numero']);

        // Validate that $telefono_numero contains only digits and is of the correct length
        if (!ctype_digit($telefono_numero)) {
            wc_add_notice('El número de teléfono debe contener solo dígitos.', 'error');
            return;
        }

        if (strlen($telefono_numero) !== 7) {
            wc_add_notice('El número de teléfono debe tener exactamente 7 dígitos.', 'error');
            return;
        }

        $telefono_completo = $telefono_codigo . $telefono_numero;


        // Prepare the payload with the converted amount
        $payload = array(
            'canal'       => $this->canal,
            'celular'     => $telefono_completo,
            'banco'       => sanitize_text_field($_POST['bt_banco']),
            'RIF'         => $this->RIF,
            'cedula'      => $cedula_completa,
            'monto'       => $order_total_bs_formatted,
            'token'       => sanitize_text_field($_POST['bt_clave']),
            'concepto'    => $this->concepto . $order_id,
            'codAfiliado' => $this->codAfiliado,
            'comercio'    => $this->comercio
        );

        // Send the request to the bank's API
        $response = wp_remote_post(
            $this->bank_api_url,
            array(
                'body'    => json_encode($payload),
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 90
            )
        );

        // Check for errors in the request
        if (is_wp_error($response)) {
            wc_add_notice('Error en la conexión con el banco.', 'error');
            return;
        }

        // Decode the response body
        $body = json_decode(wp_remote_retrieve_body($response));

        // Handle the API response
        if ($body && isset($body->codres) && $body->codres === 'C2P0000') {
            // Payment successful
            $order->payment_complete();
            $order->add_order_note('Pago C2P procesado exitosamente. Referencia: ' . $body->referencia);

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            // Payment failed
            $error_message = isset($body->descRes) ? $body->descRes : 'Error desconocido';
            wc_add_notice('Error en el pago: ' . $error_message, 'error');
            return;
        }
    }
}
