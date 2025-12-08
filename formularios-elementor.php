<?php
/**
 * Plugin Name: Mimer Forms VDI
 * Plugin URI: https://github.com/Mimergt/mimer-forms
 * Description: Sistema multi-formulario con detección automática y Select2 integrado. Soporta Depo Provera, RoundUp y futuros formularios con selectores modernos.
 * Version: 2.8.2
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

/**
 * Fallback handler: some Elementor setups don't trigger PHP hooks.
 * Detect POST submissions that look like our Elementor form and process them server-side.
 */
add_action('init', 'mimer_handle_fallback_post', 20);
function mimer_handle_fallback_post() {
    if (session_status() == PHP_SESSION_NONE) session_start();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    // Elementor posts fields under form_fields[] by default
    if (!isset($_POST['form_fields']) || !is_array($_POST['form_fields'])) return;

    $posted = $_POST['form_fields'];

    // Quick detection: look for Roblox/RoundUp specific keys
    $is_roblox = false;
    foreach ($posted as $k => $v) {
        if (strpos($k, 'case_abuse_type') !== false || strpos($k, 'case_interaction') !== false) {
            $is_roblox = true;
            break;
        }
    }

    if (!$is_roblox) return;

    // Prevent double-processing within the same session/request by checksuming payload
    $payload_hash = md5(serialize($posted));
    if (isset($_SESSION['mimer_last_processed']) && $_SESSION['mimer_last_processed'] === $payload_hash) {
        return;
    }
    $_SESSION['mimer_last_processed'] = $payload_hash;

    $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔁 FALLBACK POST handler: detected Roblox/RoundUp POST - processing...\n";
    file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);

    // Call the same mapping function used by the normal flow
    MimerFormsVDI::send_roblox_to_api($posted);
}

// ✅ VERSIÓN SIMPLIFICADA - SIN BLOQUEO AJAX

// Hook principal de validación de Elementor Pro
add_action('elementor_pro/forms/validation', 'env_validate_phone_number', 10, 2);

function env_validate_phone_number($record, $ajax_handler) {
    $fields = $record->get('fields');

    // ✅ DETECCIÓN MEJORADA: Verificar si es uno de nuestros formularios
    $is_depo_form = false;
    $is_depo_v2_form = false;
    $is_roundup_form = false;
    $is_roblox_form = false;
    
    // También obtener el ID del formulario si está disponible
    $form_id = $record->get('form_settings')['id'] ?? '';
    
    foreach ($fields as $field) {
        if (isset($field['id'])) {
            if (strpos($field['id'], 'case_depo_provera_taken') !== false) {
                // Verificar si es la versión V2 por el ID del formulario
                if ($form_id === 'dp_formv2') {
                    $is_depo_v2_form = true;
                } else {
                    $is_depo_form = true;
                }
                break;
            }
            if (strpos($field['id'], 'case_exposed') !== false) {
                $is_roundup_form = true;
                break;
            }
            if (strpos($field['id'], 'case_abuse_type') !== false || strpos($field['id'], 'case_interaction') !== false) {
                $is_roblox_form = true;
                break;
            }
        }
    }
    
    // Si no es nuestro formulario, salir silenciosamente
    if (!$is_depo_form && !$is_depo_v2_form && !$is_roundup_form && !$is_roblox_form) {
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

    // ✅ ENVIAR AL API - LÓGICA MEJORADA CON SOPORTE PARA DEPO V2
    if ($is_depo_form) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🎯 Detectado formulario DEPO PROVERA V1 - enviando...\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        MimerFormsVDI::send_depo_provera_to_api($flat_fields);
    } elseif ($is_depo_v2_form) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🎯 Detectado formulario DEPO PROVERA V2 (ID: dp_formv2) - enviando...\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        MimerFormsVDI::send_depo_provera_v2_to_api($flat_fields);
    } elseif ($is_roundup_form) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🎯 Detectado formulario ROUNDUP - enviando...\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
        MimerFormsVDI::send_roundup_to_api($flat_fields);
    } elseif ($is_roblox_form) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🎯 Detectado formulario ROBLOX/ROUNDUP-TYPE - enviando...\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);

        // Dump flat fields in test mode to inspect mapping
        if (get_option('mimer_test_mode_enabled', 0)) {
            $dump = "[" . date('Y-m-d H:i:s') . "] 🐛 FLAT_FIELDS DUMP: " . print_r($flat_fields, true) . "\n";
            file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $dump, FILE_APPEND);
        }

        MimerFormsVDI::send_roblox_to_api($flat_fields);
    } else {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] ❓ NO SE DETECTÓ TIPO DE FORMULARIO\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
    }
}

// ✅ VERSIÓN SIMPLIFICADA - SIN HOOKS ADICIONALES COMPLEJOS

// � DEBUG: Log para verificar si el plugin se está cargando en thankyou pages
add_action('wp_head', function() {
    if (strpos($_SERVER['REQUEST_URI'], 'thankyou') !== false || strpos($_SERVER['REQUEST_URI'], 'thank') !== false) {
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔍 PLUGIN CARGADO en página: " . $_SERVER['REQUEST_URI'] . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $debug_log, FILE_APPEND);
    }
});

// �📝 Shortcodes para mostrar datos del API (solo si redirecciones están activadas)
function mimer_api_lead_id_shortcode() {
    try {
        $redirections_enabled = get_option('mimer_redirections_enabled', 1);
        if (!$redirections_enabled) return '';
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        $val = isset($_SESSION['mimer_api_lead_id']) ? $_SESSION['mimer_api_lead_id'] : '';
        unset($_SESSION['mimer_api_lead_id']);
        return esc_html($val);
    } catch (Exception $e) {
        error_log('Mimer shortcode error: ' . $e->getMessage());
        return '';
    }
}
add_shortcode('mimer_api_lead_id', 'mimer_api_lead_id_shortcode');

function mimer_api_response_message_shortcode() {
    try {
        $redirections_enabled = get_option('mimer_redirections_enabled', 1);
        if (!$redirections_enabled) return '';
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        $val = isset($_SESSION['mimer_api_response_message']) ? $_SESSION['mimer_api_response_message'] : '';
        unset($_SESSION['mimer_api_response_message']);
        return esc_html($val);
    } catch (Exception $e) {
        error_log('Mimer shortcode error: ' . $e->getMessage());
        return '';
    }
}
add_shortcode('mimer_api_response_message', 'mimer_api_response_message_shortcode');

function mimer_api_validation_errors_shortcode() {
    try {
        $redirections_enabled = get_option('mimer_redirections_enabled', 1);
        if (!$redirections_enabled) return '';
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        $val = isset($_SESSION['mimer_api_validation_errors']) ? $_SESSION['mimer_api_validation_errors'] : '';
        unset($_SESSION['mimer_api_validation_errors']);
        return esc_html($val);
    } catch (Exception $e) {
        error_log('Mimer shortcode error: ' . $e->getMessage());
        return '';
    }
}
add_shortcode('mimer_api_validation_errors', 'mimer_api_validation_errors_shortcode');

function mimer_case_injury_shortcode() {
    try {
        $redirections_enabled = get_option('mimer_redirections_enabled', 1);
        if (!$redirections_enabled) return '';
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        $val = isset($_SESSION['mimer_case_injury']) ? $_SESSION['mimer_case_injury'] : '';
        unset($_SESSION['mimer_case_injury']);
        return esc_html($val);
    } catch (Exception $e) {
        error_log('Mimer shortcode error: ' . $e->getMessage());
        return '';
    }
}
add_shortcode('mimer_case_injury', 'mimer_case_injury_shortcode');

function mimer_api_redirect_url_shortcode() {
    try {
        // 🔒 VERIFICAR SI LAS REDIRECCIONES ESTÁN HABILITADAS
        $redirections_enabled = get_option('mimer_redirections_enabled', 1);
        if (!$redirections_enabled) return '';
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        
        // Obtener URL de redirección de múltiples fuentes
        $redirect_url = '';
        
        // 1. Desde sesión (método preferido)
        if (isset($_SESSION['mimer_api_redirect_url'])) {
            $redirect_url = $_SESSION['mimer_api_redirect_url'];
            // NO limpiar aquí para que mimer_auto_redirect pueda usarla
        }
        // 2. Backup desde cookie
        else if (isset($_COOKIE['mimer_redirect_backup'])) {
            $redirect_url = $_COOKIE['mimer_redirect_backup'];
        }
        // 3. Backward compatibility
        else if (isset($_SESSION['mimer_last_redirect_url'])) {
            $redirect_url = $_SESSION['mimer_last_redirect_url'];
        }
        
        // ✅ SOLO MOSTRAR LA URL, NO REDIRIGIR AUTOMÁTICAMENTE
        // (Para redirección automática usar [mimer_auto_redirect])
        return esc_url($redirect_url);
        
    } catch (Exception $e) {
        error_log('Mimer shortcode error: ' . $e->getMessage());
        return '';
    }
}
add_shortcode('mimer_api_redirect_url', 'mimer_api_redirect_url_shortcode');

// ✅ SHORTCODE AUTO-REDIRECT QUE RESPETA CONFIGURACIÓN ADMIN
function mimer_auto_redirect_shortcode($atts = []) {
    // 🔒 VERIFICAR SI LAS REDIRECCIONES ESTÁN HABILITADAS
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    
    if (!$redirections_enabled) {
        // Si están desactivadas, no hacer nada (página normal)
        return '';
    }
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Solo redirigir si hay URL en sesión
    $redirect_url = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    
    if (!empty($redirect_url)) {
        // Limpiar la sesión
        unset($_SESSION['mimer_api_redirect_url']);
        
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
                    window.location.href = "' . esc_js($redirect_url) . '";
                }
            }, 1000);
        </script>';
    }
    
    // Si no hay URL, no mostrar nada (página normal)
    return '';
}
add_shortcode('mimer_auto_redirect', 'mimer_auto_redirect_shortcode');

// 🔍 SHORTCODE DE DEBUGGING PARA TROUBLESHOOT
function mimer_debug_shortcode($atts = []) {
    $redirections_enabled = get_option('mimer_redirections_enabled', 1);
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $redirect_url = isset($_SESSION['mimer_api_redirect_url']) ? $_SESSION['mimer_api_redirect_url'] : '';
    
    $debug_info = '<div style="background: #f9f9f9; padding: 15px; margin: 10px 0; border: 1px solid #ddd;">';
    $debug_info .= '<h4>🔍 Mimer Debug Info</h4>';
    $debug_info .= '<p><strong>Redirecciones habilitadas:</strong> ' . ($redirections_enabled ? '✅ SÍ' : '❌ NO') . '</p>';
    $debug_info .= '<p><strong>URL de redirección en sesión:</strong> ' . ($redirect_url ? $redirect_url : 'Ninguna') . '</p>';
    $debug_info .= '<p><strong>Shortcode funcionando:</strong> ✅ SÍ</p>';
    $debug_info .= '</div>';
    
    return $debug_info;
}
add_shortcode('mimer_debug', 'mimer_debug_shortcode');

// Crear una sola instancia del admin
if (is_admin()) {
    new MimerPhoneValidatorAdmin();
}
