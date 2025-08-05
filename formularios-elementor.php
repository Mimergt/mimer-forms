<?php
/*
Plugin Name: Mimer forms VDI
Description: Validaciones, conexion con API y otros.
Version: 1.4
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
    
    // Script principal (utilitarios)
    wp_enqueue_script(
        'mimer-form-validation',
        plugin_dir_url(__FILE__) . 'includes/some_magic.js',
        array('jquery', 'mimer-form-validation-rules'),
        null,
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

// Shortcode para redirección automática (versión simple y funcional)
function mimer_auto_redirect_shortcode() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Debug: agregar logging de toda la sesión
    $session_debug = "TODA LA SESIÓN: " . print_r($_SESSION, true);
    file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', 
        "[" . date('Y-m-d H:i:s') . "] 🔍 SHORTCODE DEBUG - " . $session_debug . "\n", 
        FILE_APPEND);
    
    // Leer la URL de la sesión PRIMERO, luego de cookie como backup
    $redirect_url = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    
    // Si no hay en sesión, revisar cookie backup
    if (empty($redirect_url) && isset($_COOKIE['mimer_redirect_backup'])) {
        $redirect_url = $_COOKIE['mimer_redirect_backup'];
        // Limpiar cookie INMEDIATAMENTE para evitar bucles
        setcookie('mimer_redirect_backup', '', time() - 3600, '/');
        file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', 
            "[" . date('Y-m-d H:i:s') . "] 🍪 URL recuperada de cookie backup y cookie limpiada: '" . $redirect_url . "'\n", 
            FILE_APPEND);
    }
    
    // Debug: agregar logging para ver qué pasa
    $debug_info = "Sesión: " . (isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : 'VACÍA') . " | Cookie: " . (isset($_COOKIE['mimer_redirect_backup']) ? $_COOKIE['mimer_redirect_backup'] : 'VACÍA') . " | Final: " . $redirect_url;
    
    // Log específico del shortcode
    file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', 
        "[" . date('Y-m-d H:i:s') . "] 🎯 SHORTCODE - URL encontrada: '" . $redirect_url . "'\n", 
        FILE_APPEND);
    
    // Condición más clara: redirigir si hay URL y no está vacía
    if (!empty($redirect_url)) {
        // Limpiar solo la sesión (cookie ya se limpió arriba)
        unset($_SESSION['mimer_api_redirect_url']);
        
        return '<span id="redirect-message">✅ URL found! You will be redirected in 3 seconds...</span>
        <span style="display:block; color:green; font-size:12px;" id="debug-info">🔍 Debug: ' . esc_html($debug_info) . '</span>
        <script>
            console.log("🚀 Auto redirect: URL encontrada = ' . esc_js($redirect_url) . '");
            let count = 3;
            const msg = document.getElementById("redirect-message");
            
            const timer = setInterval(function() {
                count--;
                if (msg) msg.textContent = "✅ Redirecting in " + count + " seconds to: ' . esc_js($redirect_url) . '";
                
                if (count <= 0) {
                    clearInterval(timer);
                    if (msg) msg.textContent = "🚀 Redirecting now to: ' . esc_js($redirect_url) . '";
                    console.log("🔄 Ejecutando redirección a: ' . esc_js($redirect_url) . '");
                    window.location.href = "' . esc_js($redirect_url) . '";
                }
            }, 1000);
        </script>';
    }
    
    // Si no hay URL, mostrar debug más detallado
    return '<div style="padding:10px; border:1px solid red; background:#ffe6e6;">
        <span style="color: red;">❌ Auto redirect: No URL found</span><br>
        <span style="font-size:12px;">🔍 Debug: ' . esc_html($debug_info) . '</span><br>
        <span style="font-size:10px; color:#666;">💡 Tip: Submit a form first to generate redirect URL</span>
    </div>';
}
add_shortcode('mimer_auto_redirect', 'mimer_auto_redirect_shortcode');
