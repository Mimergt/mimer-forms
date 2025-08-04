<?php
/*
Plugin Name: Mimer forms VDI
Description: Valida campos de teléfono y conecta con API.
Version: 1.2.2
Author: Mimer
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'admin/back-end.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-numverify.php';
require_once plugin_dir_path(__FILE__) . 'includes/forms-api.php';

add_action('wp_enqueue_scripts', 'mimer_enqueue_custom_script');
function mimer_enqueue_custom_script() {
    wp_enqueue_script(
        'mimer-form-validation',
        plugin_dir_url(__FILE__) . 'includes/some_magic.js',
        array('jquery'),
        null,
        true
    );
}


add_action('elementor_pro/forms/validation', 'env_validate_phone_number', 10, 2);



function env_validate_phone_number($record, $ajax_handler) {
    $fields = $record->get('fields');

    if (isset($fields['lead_zip_code'])) {
        $zip = $fields['lead_zip_code']['value'];
        if (!preg_match('/^\d{5}$/', $zip)) {
            $ajax_handler->add_error('lead_zip_code', 'Enter a valid 5-digit US ZIP code.');
        }
    }

    $select_fields = ['case_depo_provera_taken', 'case_depo_provera_use', 'case_diagnosis', 'case_attorney'];
    foreach ($select_fields as $field_key) {
        if (isset($fields[$field_key])) {
            $value = $fields[$field_key]['value'];
            if ($value === '--select--' || $value === '') {
                $ajax_handler->add_error($field_key, 'Please select a valid option.');
            }
        }
    }

    if (get_option('mimer_phone_validator_enabled') && isset($fields['lead_phone'])) {
        $phone = $fields['lead_phone']['value'];

        $result = ENV_Numverify::validate($phone);

        if (!$result['valid']) {
            $ajax_handler->add_error('lead_phone', 'Invalid phone number or outside the US.');
            return;
        }
    }

    // Extraer solo los valores planos de los campos
    $flat_fields = [];
    foreach ($fields as $key => $f) {
        $flat_fields[$key] = $f['value'];
    }

    // Enviar al API y obtener la URL de redirección
    $redirect_url = MimerFormsVDI::send_submission_to_vdi($flat_fields);
    
    // LOGGING DETALLADO DE REDIRECCIÓN
    $debug_log = "[" . date('Y-m-d H:i:s') . "] REDIRECCIÓN EN ELEMENTOR\n";
    $debug_log .= "URL recibida del API: " . $redirect_url . "\n";
    file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', $debug_log, FILE_APPEND);
    
    // Si obtenemos una URL de redirección, usarla en Elementor
    if (!empty($redirect_url)) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] CONFIGURANDO REDIRECCIÓN EN ELEMENTOR\n";
        $debug_log .= "Llamando add_response_data('redirect_url', '$redirect_url')\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', $debug_log, FILE_APPEND);
        
        // Método 1: add_response_data (puede no funcionar en todas las versiones)
        $ajax_handler->add_response_data('redirect_url', $redirect_url);
        
        // Método 2: Forzar redirección directa para AJAX
        if (wp_doing_ajax()) {
            $debug_log = "[" . date('Y-m-d H:i:s') . "] REDIRECCIÓN AJAX DIRECTA\n";
            file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', $debug_log, FILE_APPEND);
            
            wp_send_json_success(array(
                'message' => 'Form submitted successfully',
                'data' => array(
                    'redirect_url' => $redirect_url
                )
            ));
        }
        
        $debug_log = "[" . date('Y-m-d H:i:s') . "] REDIRECCIÓN CONFIGURADA\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', $debug_log, FILE_APPEND);
    } else {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] ERROR: URL DE REDIRECCIÓN VACÍA\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', $debug_log, FILE_APPEND);
    }
}

// Puedes poner esto en el archivo principal del plugin o en un archivo de shortcodes
function mimer_dynamic_redirect_shortcode() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $url = isset($_SESSION['mimer_last_redirect_url']) ? $_SESSION['mimer_last_redirect_url'] : 'https://injuryresolve.com/dp-thankyou/';
    // Limpiar la sesión después de usarla (opcional)
    unset($_SESSION['mimer_last_redirect_url']);
    return esc_url($url);
}
add_shortcode('mimer_dynamic_redirect', 'mimer_dynamic_redirect_shortcode');

function mimer_api_lead_id_shortcode() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_lead_id']) ? $_SESSION['mimer_api_lead_id'] : '';
    unset($_SESSION['mimer_api_lead_id']);
    return esc_html($val);
}
add_shortcode('mimer_api_lead_id', 'mimer_api_lead_id_shortcode');

function mimer_api_response_message_shortcode() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_response_message']) ? $_SESSION['mimer_api_response_message'] : '';
    unset($_SESSION['mimer_api_response_message']);
    return esc_html($val);
}
add_shortcode('mimer_api_response_message', 'mimer_api_response_message_shortcode');

function mimer_api_validation_errors_shortcode() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_validation_errors']) ? $_SESSION['mimer_api_validation_errors'] : '';
    unset($_SESSION['mimer_api_validation_errors']);
    return esc_html($val);
}
add_shortcode('mimer_api_validation_errors', 'mimer_api_validation_errors_shortcode');

function mimer_api_redirect_url_shortcode() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    unset($_SESSION['mimer_api_redirect_url']);
    return esc_url($val);
}
add_shortcode('mimer_api_redirect_url', 'mimer_api_redirect_url_shortcode');
