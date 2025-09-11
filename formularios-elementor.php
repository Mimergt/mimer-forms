<?php
/**
 * Plugin Name: Mimer Forms VDI
 * Plugin URI: https://github.com/Mimergt/mimer-forms
 * Description: Sistema unificado multi-formulario con detección automática y Select2 integrado. Soporta Depo Provera, RoundUp y futuros formularios con selectores modernos.
 * Version: 2.5.8-fixed-header
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

// Enqueue scripts
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
    
    // Script para manejar respuestas AJAX especiales
    wp_enqueue_script(
        'mimer-ajax-handler',
        plugin_dir_url(__FILE__) . 'includes/ajax-handler.js',
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

// Control de procesamiento AJAX para evitar doble procesamiento
add_action('wp_ajax_elementor_pro_forms_send_form', 'mimer_control_ajax_processing', 1);
add_action('wp_ajax_nopriv_elementor_pro_forms_send_form', 'mimer_control_ajax_processing', 1);

function mimer_control_ajax_processing() {
    // Verificar si es nuestro formulario
    if (isset($_POST['form_fields']) && (isset($_POST['form_fields']['case_exposed']) || isset($_POST['form_fields']['case_depo_provera_taken']))) {
        
        // Verificar si ya procesamos este formulario
        if (isset($_SESSION['mimer_form_processed']) && $_SESSION['mimer_form_processed'] === true) {
            $debug_log = "[" . date('Y-m-d H:i:s') . "] ✅ AJAX SKIP - Formulario ya procesado por hook\n";
            file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
            
            // Devolver éxito simulado
            wp_send_json_success([
                'message' => 'Form already processed via validation hook',
                'mimer_processed' => true
            ]);
        } else {
            // Marcar que vamos a procesar por AJAX
            $_SESSION['mimer_ajax_processing'] = true;
            $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔄 AJAX PROCESSING - Iniciando procesamiento AJAX\n";
            file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
            
            // PROCESAR FORMULARIO POR AJAX
            try {
                // Extraer campos del POST
                $flat_fields = [];
                if (isset($_POST['form_fields'])) {
                    foreach ($_POST['form_fields'] as $key => $value) {
                        $flat_fields[$key] = sanitize_text_field($value);
                    }
                }
                
                // Obtener form_id si está disponible
                $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : null;
                
                $debug_log = "[" . date('Y-m-d H:i:s') . "] 📤 AJAX - Enviando " . count($flat_fields) . " campos al API (form_id: " . ($form_id ?: 'N/A') . ")\n";
                file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
                
                // Enviar al API
                MimerFormsVDI::send_submission_to_vdi($flat_fields, $form_id);
                
                // Marcar como procesado
                $_SESSION['mimer_form_processed'] = true;
                unset($_SESSION['mimer_ajax_processing']);
                
                $debug_log = "[" . date('Y-m-d H:i:s') . "] ✅ AJAX - Procesamiento completado exitosamente\n";
                file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
                
                // Continuar con el procesamiento normal de Elementor (no hacer wp_send_json)
                return;
                
            } catch (Exception $e) {
                $debug_log = "[" . date('Y-m-d H:i:s') . "] ❌ AJAX ERROR - " . $e->getMessage() . "\n";
                file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
                
                // Limpiar flags
                unset($_SESSION['mimer_form_processed']);
                unset($_SESSION['mimer_ajax_processing']);
            }
        }
    }
}

// Hook principal de validación de Elementor Pro
add_action('elementor_pro/forms/validation', 'env_validate_phone_number', 10, 2);

function env_validate_phone_number($record, $ajax_handler) {
    $fields = $record->get('fields');

    // Verificar si es uno de nuestros formularios objetivo
    $our_form = false;
    foreach ($fields as $field) {
        if (isset($field['id']) && (strpos($field['id'], 'case_exposed') !== false || strpos($field['id'], 'case_depo_provera_taken') !== false)) {
            $our_form = true;
            break;
        }
    }
    
    if (!$our_form) {
        // No es nuestro formulario, no procesar
        return;
    }

    // Verificar si ya se está procesando por AJAX
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['mimer_ajax_processing']) && $_SESSION['mimer_ajax_processing'] === true) {
        // Ya se está procesando por AJAX, saltear este hook
        $debug_log = "[" . date('Y-m-d H:i:s') . "] ⏭️ HOOK SKIP - Ya procesando por AJAX\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        unset($_SESSION['mimer_ajax_processing']);
        return;
    }

    // Marcar que procesamos por hook
    $_SESSION['mimer_form_processed'] = true;

    // Log de procesamiento para debugging
    $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔄 ELEMENTOR HOOK - Procesando nuestro formulario con " . count($fields) . " campos\n";
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
    try {
        // Limpiar cualquier output buffer antes del procesamiento
        if (ob_get_level()) {
            ob_clean();
        }
        
        MimerFormsVDI::send_submission_to_vdi($flat_fields, $form_id);
        
        $debug_log = "[" . date('Y-m-d H:i:s') . "] ✅ API PROCESSING - Completado exitosamente\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        
        // Limpiar flags de sesión
        unset($_SESSION['mimer_form_processed']);
        unset($_SESSION['mimer_ajax_processing']);
        
    } catch (Exception $e) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] ❌ API ERROR - " . $e->getMessage() . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        
        // Limpiar flags de sesión en caso de error también
        unset($_SESSION['mimer_form_processed']);
        unset($_SESSION['mimer_ajax_processing']);
        
        // Añadir error a Elementor
        $ajax_handler->add_error_message('Ocurrió un error procesando el formulario. Por favor intente nuevamente.');
    }
}

// Hook adicional para limpiar respuesta después del procesamiento
add_action('elementor_pro/forms/process', 'mimer_clean_response_after_processing', 999, 2);

function mimer_clean_response_after_processing($record, $ajax_handler) {
    // Verificar si es uno de nuestros formularios
    $fields = $record->get('fields');
    $our_form = false;
    foreach ($fields as $field) {
        if (isset($field['id']) && (strpos($field['id'], 'case_exposed') !== false || strpos($field['id'], 'case_depo_provera_taken') !== false)) {
            $our_form = true;
            break;
        }
    }
    
    if ($our_form) {
        // Limpiar cualquier output buffer residual
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🧹 CLEAN RESPONSE - Output buffer limpiado\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
    }
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
