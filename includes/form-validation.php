<?php
/**
 * Mimer Forms VDI - Server-side Form Validation
 * Validaciones del lado del servidor para seguridad adicional
 * Versión: 1.0
 */

if (!defined('ABSPATH')) exit;

class MimerFormValidation {
    
    /**
     * Mensajes de validación en inglés
     */
    const VALIDATION_MESSAGES = [
        'zip_code' => 'Enter a valid 5-digit US ZIP code.',
        'select_required' => 'Please select a valid option.',
        'phone_invalid' => 'Invalid phone number or outside the US.',
        'radio_required' => 'Please select one option.',
        'field_required' => 'Please complete this field.'
    ];
    
    /**
     * Campos de select que requieren validación especial
     */
    const SELECT_FIELDS = [
        'case_depo_provera_taken',
        'case_depo_provera_use', 
        'case_diagnosis', 
        'case_attorney'
    ];
    
    /**
     * Validar código postal
     */
    public static function validate_zip_code($zip) {
        return preg_match('/^\d{5}$/', $zip);
    }
    
    /**
     * Validar select fields
     */
    public static function validate_select_field($value) {
        return !($value === '--select--' || $value === '' || empty($value));
    }
    
    /**
     * Validar campos requeridos
     */
    public static function validate_required_field($value) {
        return !empty(trim($value));
    }
    
    /**
     * Agregar error al handler de Elementor
     */
    public static function add_validation_error($ajax_handler, $field_id, $message_key) {
        $message = self::VALIDATION_MESSAGES[$message_key] ?? 'Invalid field value.';
        $ajax_handler->add_error($field_id, $message);
    }
    
    /**
     * Validación principal del formulario
     */
    public static function validate_form($fields, $ajax_handler) {
        $errors = [];
        
        // Validar código postal
        if (isset($fields['lead_zip_code'])) {
            $zip = $fields['lead_zip_code']['value'];
            if (!self::validate_zip_code($zip)) {
                self::add_validation_error($ajax_handler, 'lead_zip_code', 'zip_code');
                $errors[] = 'lead_zip_code';
            }
        }
        
        // Validar campos de select
        foreach (self::SELECT_FIELDS as $field_key) {
            if (isset($fields[$field_key])) {
                $value = $fields[$field_key]['value'];
                if (!self::validate_select_field($value)) {
                    self::add_validation_error($ajax_handler, $field_key, 'select_required');
                    $errors[] = $field_key;
                }
            }
        }
        
        // Validar teléfono (si está habilitado)
        if (get_option('mimer_phone_validator_enabled') && isset($fields['lead_phone'])) {
            $phone = $fields['lead_phone']['value'];
            
            $result = ENV_Numverify::validate($phone);
            
            if (!$result['valid']) {
                self::add_validation_error($ajax_handler, 'lead_phone', 'phone_invalid');
                $errors[] = 'lead_phone';
            }
        }
        
        return empty($errors);
    }
    
    /**
     * Log de validación para debugging
     */
    public static function log_validation($fields, $errors = []) {
        if (get_option('mimer_test_mode_enabled')) {
            $log = "[" . date('Y-m-d H:i:s') . "] 🔍 VALIDACIÓN DEL SERVIDOR\n";
            $log .= "Campos validados: " . count($fields) . "\n";
            $log .= "Errores encontrados: " . count($errors) . "\n";
            if (!empty($errors)) {
                $log .= "Campos con error: " . implode(', ', $errors) . "\n";
            }
            $log .= "---\n";
            
            file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);
        }
    }
}
