<?php
/**
 * Plugin Name: Mimer Forms VDI
 * Plugin URI: https://github.com/Mimergt/mimer-forms
 * Description: Sistema unificado multi-formulario con detección automática y Select2 integrado. Soporta Depo Provera, RoundUp y futuros formularios con selectores modernos.
 * Version: 2.6-simplified
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

// Incluir archivos necesarios
require_once plugin_dir_path(__FILE__) . 'admin/back-end.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-numverify.php';
require_once plugin_dir_path(__FILE__) . 'includes/forms-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-validation.php';
require_once plugin_dir_path(__FILE__) . 'includes/select2-handler.php';

// ✅ SCRIPTS SIMPLIFICADOS - SIN INTERCEPTORES AJAX COMPLEJOS
add_action('wp_enqueue_scripts', 'mimer_enqueue_custom_script');
function mimer_enqueue_custom_script() {
    // Select2 CSS y JS desde CDN
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
    
    // Nuestro handler de Select2
    wp_enqueue_script('select2-handler', plugin_dir_url(__FILE__) . 'includes/select2-handler.php', array('jquery', 'select2-js'), '1.0.' . time(), true);
    
    // Script de validaciones principales
    wp_enqueue_script('form-validation', plugin_dir_url(__FILE__) . 'includes/form-validation.js', array('jquery'), '2.6.simple.' . time(), true);
    
    // Script principal básico
    wp_enqueue_script(
        'mimer-form-validation',
        plugin_dir_url(__FILE__) . 'includes/some_magic.js',
        array('jquery'),
        '1.0.' . time(),
        true
    );
}

// Inicializar sesiones
add_action('init', 'mimer_init_session_flag');
function mimer_init_session_flag() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// ✅ VERSIÓN SIMPLIFICADA - SIN BLOQUEO AJAX

// Hook principal de validación de Elementor Pro
add_action('elementor_pro/forms/validation', 'env_validate_phone_number', 10, 2);

function env_validate_phone_number($record, $ajax_handler) {
    $fields = $record->get('fields');

    // ✅ DETECCIÓN SIMPLE: Verificar si es uno de nuestros formularios
    $is_depo_form = false;
    $is_roundup_form = false;
    
    foreach ($fields as $field) {
        if (isset($field['id'])) {
            if (strpos($field['id'], 'case_depo_provera_taken') !== false) {
                $is_depo_form = true;
                break;
            }
            if (strpos($field['id'], 'case_exposed') !== false) {
                $is_roundup_form = true;
                break;
            }
        }
    }
    
    // Si no es nuestro formulario, salir silenciosamente
    if (!$is_depo_form && !$is_roundup_form) {
        return;
    }

    // ✅ LÓGICA SIMPLE: Validar → Procesar → Enviar
    $validation_passed = MimerFormValidation::validate_form($fields, $ajax_handler);
    
    if (!$validation_passed) {
        return; // Salir si hay errores de validación
    }

    // ✅ PREPARAR CAMPOS PARA API
    $flat_fields = [];
    foreach ($fields as $key => $f) {
        $flat_fields[$key] = $f['value'];
    }

    // ✅ ENVIAR AL API - LÓGICA SIMPLE COMO EN v1.4
    if ($is_depo_form) {
        MimerFormsVDI::send_depo_provera_to_api($flat_fields);
    } elseif ($is_roundup_form) {
        MimerFormsVDI::send_roundup_to_api($flat_fields);
    }
}

// ✅ VERSIÓN SIMPLIFICADA - SIN HOOKS ADICIONALES COMPLEJOS

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

function mimer_case_injury_shortcode() {
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    if (!$redirections_enabled) return '';
    
    if (session_status() == PHP_SESSION_NONE) session_start();
    $val = isset($_SESSION['mimer_case_injury']) ? $_SESSION['mimer_case_injury'] : '';
    unset($_SESSION['mimer_case_injury']);
    return esc_html($val);
}
add_shortcode('mimer_case_injury', 'mimer_case_injury_shortcode');

function mimer_api_redirect_url_shortcode() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    
    // Obtener URL de redirección de múltiples fuentes
    $redirect_url = '';
    
    // 1. Desde sesión (método preferido)
    if (isset($_SESSION['mimer_api_redirect_url'])) {
        $redirect_url = $_SESSION['mimer_api_redirect_url'];
        unset($_SESSION['mimer_api_redirect_url']);
    }
    // 2. Backup desde cookie
    else if (isset($_COOKIE['mimer_redirect_backup'])) {
        $redirect_url = $_COOKIE['mimer_redirect_backup'];
        // Limpiar cookie
        setcookie('mimer_redirect_backup', '', time() - 3600, '/');
    }
    // 3. Backward compatibility
    else if (isset($_SESSION['mimer_last_redirect_url'])) {
        $redirect_url = $_SESSION['mimer_last_redirect_url'];
        unset($_SESSION['mimer_last_redirect_url']);
    }
    
    if (!empty($redirect_url)) {
        // Limpiar y ejecutar redirección
        wp_redirect($redirect_url);
        exit;
    }
    
    return '';
}
add_shortcode('mimer_api_redirect_url', 'mimer_api_redirect_url_shortcode');

// Crear una sola instancia del admin
if (is_admin()) {
    new MimerPhoneValidatorAdmin();
}
