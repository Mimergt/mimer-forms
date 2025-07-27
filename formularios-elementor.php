<?php
/*
Plugin Name: Mimer forms VDI
Description: Valida campos de teléfono y conecta con API.
Version: 1.2
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

    MimerFormsVDI::send_submission_to_vdi($flat_fields);
}


?>
