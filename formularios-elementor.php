<?php
/*
Plugin Name: Mimer forms VDI
Description: Sistema unificado multi-formulario con detección automática y Select2 integrado. Soporta Depo Provera, RoundUp y futuros formularios con selectores modernos.
Version: 2.2-roundup-field-mapping
Author: Mimer
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'admin/back-end.php';

require_once plugin_dir_path(__FILE__) . 'includes/class-numverify.php';
require_once plugin_dir_path(__FILE__) . 'includes/forms-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-validation.php';
require_once plugin_dir_path(__FILE__) . 'includes/select2-handler.php';

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

// 📝 Shortcodes para mostrar datos del API (solo si redirecciones están activadas)
function mimer_api_lead_id_shortcode() {
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    if (!$redirections_enabled) return '';
    
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_lead_id']) ? $_SESSION['mimer_api_lead_id'] : '';
    unset($_SESSION['mimer_api_lead_id']);
    return esc_html($val);
}
add_shortcode('mimer_api_lead_id', 'mimer_api_lead_id_shortcode');

function mimer_api_response_message_shortcode() {
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    if (!$redirections_enabled) return '';
    
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_response_message']) ? $_SESSION['mimer_api_response_message'] : '';
    unset($_SESSION['mimer_api_response_message']);
    return esc_html($val);
}
add_shortcode('mimer_api_response_message', 'mimer_api_response_message_shortcode');

function mimer_api_validation_errors_shortcode() {
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    if (!$redirections_enabled) return '';
    
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_validation_errors']) ? $_SESSION['mimer_api_validation_errors'] : '';
    unset($_SESSION['mimer_api_validation_errors']);
    return esc_html($val);
}
add_shortcode('mimer_api_validation_errors', 'mimer_api_validation_errors_shortcode');

function mimer_api_redirect_url_shortcode() {
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    if (!$redirections_enabled) return '';
    
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    // NO limpiar la sesión aquí para que el auto redirect pueda usarla
    return esc_url($val);
}
add_shortcode('mimer_api_redirect_url', 'mimer_api_redirect_url_shortcode');

// 🎯 Shortcode condicional para redirecciones basado en configuración admin
function mimer_auto_redirect_shortcode($atts) {
    // Verificar si las redirecciones están habilitadas
    $redirections_enabled = get_option('mimer_redirections_enabled', 1); // Por defecto activadas
    
    $atts = shortcode_atts([
        'default_url' => 'https://injuryresolve.com/dp_rejected/', // URL por defecto si no hay otra
        'timeout' => '3', // Segundos para redirección
        'wait_timeout' => '5', // Segundos máximo esperando sesión
        'check_session' => 'true', // Si verificar sesión o usar default directo
        'show_fallback' => 'true', // Si mostrar enlace manual como backup
        'message' => 'Procesando...', // Mensaje mientras espera
        'class' => 'mimer-message',
    ], $atts);

    // Si las redirecciones están desactivadas, solo mostrar mensaje
    if (!$redirections_enabled) {
        return '<div class="' . esc_attr($atts['class']) . '">' . esc_html($atts['message']) . ' (Elementor maneja redirecciones)</div>';
    }

    // Log para seguimiento
    $log = "\n🎯 [" . date('Y-m-d H:i:s') . "] Shortcode ejecutándose (redirecciones ACTIVADAS)...\n";
    
    // 🔍 Verificar sesión si está habilitado
    if ($atts['check_session'] === 'true') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Ver si tenemos URL del API en sesión
        $api_redirect_url = '';
        if (!empty($_SESSION['mimer_api_redirect_url'])) {
            $api_redirect_url = $_SESSION['mimer_api_redirect_url'];
            $log .= "✅ URL encontrada en sesión: " . $api_redirect_url . "\n";
        } else if (!empty($_SESSION['mimer_last_redirect_url'])) {
            // Backward compatibility
            $api_redirect_url = $_SESSION['mimer_last_redirect_url'];
            $log .= "✅ URL encontrada en sesión (legacy): " . $api_redirect_url . "\n";
        } else if (!empty($_COOKIE['mimer_redirect_backup'])) {
            // Fallback a cookie
            $api_redirect_url = $_COOKIE['mimer_redirect_backup'];
            $log .= "🍪 URL encontrada en cookie backup: " . $api_redirect_url . "\n";
        }
        
        // Usar URL del API si existe, sino default
        $redirect_url = !empty($api_redirect_url) ? $api_redirect_url : $atts['default_url'];
        $log .= "🎯 URL final seleccionada: " . $redirect_url . "\n";
    } else {
        // Si no verificar sesión, usar directamente la URL por defecto
        $redirect_url = $atts['default_url'];
        $log .= "⚡ Usando URL por defecto directamente: " . $redirect_url . "\n";
    }
    
    file_put_contents(plugin_dir_path(__FILE__) . '/log.txt', $log, FILE_APPEND);
    
    // 🚀 Redirección JavaScript inmediata
    $timeout_ms = intval($atts['timeout']) * 1000;
    $fallback_link = $atts['show_fallback'] === 'true' ? 
        '<p><a href="' . esc_url($redirect_url) . '">Si no eres redirigido automáticamente, haz clic aquí</a></p>' : '';
    
    return '
    <div class="mimer-redirect-container">
        <p>' . esc_html($atts['message']) . '</p>
        ' . $fallback_link . '
        <script>
        console.log("🎯 Redirigiendo a: ' . esc_js($redirect_url) . '");
        setTimeout(function() {
            window.location.href = "' . esc_js($redirect_url) . '";
        }, ' . $timeout_ms . ');
        </script>
    </div>';
}
