<?php
/*
Plugin Name: Mimer forms VDI
Description: Validaciones, conexion con API y otros.
Version: 1.5
Author: Mimer
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'admin/back-end.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-numverify.php';
require_once plugin_dir_path(__FILE__) . 'includes/forms-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-validation.php';

add_action('wp_enqueue_scripts', 'mimer_enqueue_custom_script');
function mimer_enqueue_custom_script() {
    // Script de validaciones organizadas - LIMPIO Y FUNCIONANDO
    wp_enqueue_script('form-validation', plugin_dir_url(__FILE__) . 'includes/form-validation.js', array('jquery'), '2.6.final.' . time(), true);
    
    // Script principal (utilitarios) - DEPENDENCIA CORREGIDA
    wp_enqueue_script(
        'mimer-form-validation',
        plugin_dir_url(__FILE__) . 'includes/some_magic.js',
        array('jquery'),
        '1.0.' . time(),
        true
    );
}


add_action('elementor_pro/forms/validation', 'env_validate_phone_number', 10, 2);



function env_validate_phone_number($record, $ajax_handler) {
    $fields = $record->get('fields');

    // Usar la nueva clase de validación
    $validation_passed = MimerFormValidation::validate_form($fields, $ajax_handler);
    
    // Log de validación (solo en modo pruebas)
    MimerFormValidation::log_validation($fields);
    
    // Si hay errores de validación, no continuar
    if (!$validation_passed) {
        return;
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

// Shortcode para redirección automática - Lógica simplificada
function mimer_auto_redirect_shortcode() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Obtener URL del API de la sesión
    $api_redirect_url = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    
    // Si no hay en sesión, revisar cookie backup
    if (empty($api_redirect_url) && isset($_COOKIE['mimer_redirect_backup'])) {
        $api_redirect_url = $_COOKIE['mimer_redirect_backup'];
        // Limpiar cookie INMEDIATAMENTE para evitar bucles
        setcookie('mimer_redirect_backup', '', time() - 3600, '/');
    }
    
    // 🎯 LÓGICA SIMPLIFICADA: URL del API o dp-not-qualified
    $final_redirect_url = '';
    
    if (!empty($api_redirect_url)) {
        // Si hay URL del API, usarla
        $final_redirect_url = $api_redirect_url;
    } else {
        // Si no hay URL del API, ir a dp-not-qualified
        $final_redirect_url = 'https://injuryresolve.com/dp-not-qualified/';
    }
    
    // Limpiar sesión después de usar
    if (!empty($final_redirect_url)) {
        unset($_SESSION['mimer_api_redirect_url']);
        unset($_SESSION['mimer_case_injury']);
        unset($_SESSION['mimer_last_redirect_url']);
        
        return '<span id="redirect-message">You will be redirected in 3 seconds...</span>
        <script>
            let count = 3;
            const msg = document.getElementById("redirect-message");
            
            const timer = setInterval(function() {
                count--;
                if (msg) msg.textContent = "Redirecting in " + count + " seconds...";
                
                if (count <= 0) {
                    clearInterval(timer);
                    if (msg) msg.textContent = "Redirecting now...";
                    window.location.href = "' . esc_js($final_redirect_url) . '";
                }
            }, 1000);
        </script>';
    }
    
    // Si no hay URL, no mostrar nada (silencioso)
    return '';
}
add_shortcode('mimer_auto_redirect', 'mimer_auto_redirect_shortcode');
