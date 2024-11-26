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

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

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
        ];
    }

    public function payment_fields() {
        ?>
        <div class="form-row form-row-wide">
            <label>Cédula <span class="required">*</span></label>
            <input type="text" class="input-text" name="bt_cedula" required>
        </div>
        <div class="form-row form-row-wide">
            <label>Banco <span class="required">*</span></label>
            <input type="text" class="input-text" name="bt_banco" value="0163" readonly required>
        </div>
        <div class="form-row form-row-wide">
            <label>Teléfono <span class="required">*</span></label>
            <input type="text" class="input-text" name="bt_telefono" required>
        </div>
        <div class="form-row form-row-wide">
            <label>Clave C2P <span class="required">*</span></label>
            <input type="password" class="input-text" name="bt_clave" required>
        </div>
        <?php
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        $payload = array(
            'canal' => '06',
            'celular' => sanitize_text_field($_POST['bt_telefono']),
            'banco' => sanitize_text_field($_POST['bt_banco']),
            'cedula' => sanitize_text_field($_POST['bt_cedula']),
            'monto' => number_format($order->get_total(), 2, '.', ''),
            'token' => sanitize_text_field($_POST['bt_clave']),
            'concepto' => 'Orden #' . $order_id,
            'codAfiliado' => '004036',
            'comercio' => ''
        );

        $response = wp_remote_post('http://190.202.9.207:8080/RestTesoro_C2P/com/services/botonDePago/pago', array(
            'body' => json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 90
        ));

        if (is_wp_error($response)) {
            wc_add_notice('Error en la conexión con el banco.', 'error');
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if ($body && isset($body->status) && $body->status === 'success') {
            $order->payment_complete();
            $order->add_order_note('Pago C2P procesado exitosamente.');
            
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            wc_add_notice('Error en el pago: ' . ($body->message ?? 'Error desconocido'), 'error');
            return;
        }
    }
}
