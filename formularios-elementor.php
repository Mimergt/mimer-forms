<?php
/*
Plugin Name: Mimer forms VDI
Description: Validaciones, conexion con API y otros.
Version: 1.3
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

    // Enviar al API (sin manejar redirección aquí)
    MimerFormsVDI::send_submission_to_vdi($flat_fields);
  
}

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
    // NO limpiar la sesión aquí para que el auto redirect pueda usarla
    return esc_url($val);
}
add_shortcode('mimer_api_redirect_url', 'mimer_api_redirect_url_shortcode');

// Shortcode para redirección automática (versión simple y funcional)
function mimer_auto_redirect_shortcode() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Leer la URL de la sesión
    $redirect_url = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    
    // Debug: agregar logging para ver qué pasa
    $debug_info = "URL en sesión: " . $redirect_url;
    
    // Solo redirigir si hay una URL válida y diferente a la por defecto
    if (!empty($redirect_url) && $redirect_url !== 'https://injuryresolve.com/dp-thankyou/') {
        // Limpiar la sesión DESPUÉS de obtener la URL
        unset($_SESSION['mimer_api_redirect_url']);
        
        return '<span id="redirect-message">You will be redirected in 3 seconds...</span>
        <span style="display:none;" id="debug-info">' . esc_html($debug_info) . '</span>
        <script>
            console.log("Auto redirect: URL encontrada = ' . esc_js($redirect_url) . '");
            let count = 3;
            const msg = document.getElementById("redirect-message");
            
            const timer = setInterval(function() {
                count--;
                if (msg) msg.textContent = "You will be redirected in " + count + " seconds...";
                
                if (count <= 0) {
                    clearInterval(timer);
                    if (msg) msg.textContent = "Redirecting now...";
                    console.log("Redirigiendo a: ' . esc_js($redirect_url) . '");
                    window.location.href = "' . esc_js($redirect_url) . '";
                }
            }, 1000);
        </script>';
    }
    
    // Si no hay URL, mostrar debug
    return '<span style="color: red;">Auto redirect: No URL found. Debug: ' . esc_html($debug_info) . '</span>';
}
add_shortcode('mimer_auto_redirect', 'mimer_auto_redirect_shortcode');
