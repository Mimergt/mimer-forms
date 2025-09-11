<?php
/**
 * Plugin Name: Mimer Forms VDI
 * Plugin URI: https://github.com/Mimergt/mimer-forms
 * Description: Sistema unificado multi-formulario con detección automática y Select2 integrado. Soporta Depo Provera, RoundUp y futuros formularios con selectores modernos.
 * Version: 2.5.7-ajax-redirect-fix
 * Author: Mimer
 * Author URI: https://github.com/Mimergt
 * Text Domain: mimer-forms-vdi
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

// Prevenir conflictos con admin-ajax.php
add_action('wp_ajax_elementor_pro_forms_send_form', 'mimer_prevent_ajax_conflict', 1);
add_action('wp_ajax_nopriv_elementor_pro_forms_send_form', 'mimer_prevent_ajax_conflict', 1);

function mimer_prevent_ajax_conflict() {
    // Verificar si es nuestro formulario
    if (isset($_POST['form_fields']) && (isset($_POST['form_fields']['case_exposed']) || isset($_POST['form_fields']['case_depo_provera_taken']))) {
        // Detener el procesamiento AJAX y usar nuestro hook normal
        wp_die('Mimer Forms: Using validation hook instead of AJAX', 'Mimer Forms', array('response' => 200));
    }
}

function env_validate_phone_number($record, $ajax_handler) {
    $fields = $record->get('fields');

    // Log de procesamiento para debugging
    $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔄 ELEMENTOR HOOK - Procesando formulario con " . count($fields) . " campos\n";
    file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);

    // Usar la nueva clase de validación
    $validation_passed = MimerFormValidation::validate_form($fields, $ajax_handler);
    
    // Log de validación (solo en modo pruebas)
    MimerFormValidation::log_validation($fields);
    
    // Si hay errores de validación, no continuar
    if (!$validation_passed) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] ❌ VALIDACIÓN FALLÓ - Deteniendo procesamiento\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        return;
    }

    // Extraer solo los valores planos de los campos
    $flat_fields = [];
    foreach ($fields as $key => $f) {
        $flat_fields[$key] = $f['value'];
    }

    // 🆔 OBTENER ID DEL FORMULARIO PARA DETECCIÓN MEJORADA
    $form_id = null;
    if (method_exists($record, 'get_form_settings')) {
        $form_settings = $record->get_form_settings();
        $form_id = isset($form_settings['form_id']) ? $form_settings['form_id'] : null;
    }
    
    // Si no se encuentra por el método anterior, intentar obtenerlo del HTML/DOM
    if (!$form_id && isset($_POST['form_id'])) {
        $form_id = sanitize_text_field($_POST['form_id']);
    }

    // Enviar al API con ID de formulario para mejor detección
    MimerFormsVDI::send_submission_to_vdi($flat_fields, $form_id);
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
add_shortcode('mimer_auto_redirect', 'mimer_auto_redirect_shortcode');

// 🔄 AJAX Handler removido - Permitir que Elementor maneje completamente el envío
// El procesamiento se hace únicamente a través del hook elementor_pro/forms/validation

// 🎯 Solo mostrar Admin page si el usuario puede gestionar opciones
add_action('init', function() {
    if (is_admin() && current_user_can('manage_options')) {
        new MimerPhoneValidatorAdmin();
    }
});
