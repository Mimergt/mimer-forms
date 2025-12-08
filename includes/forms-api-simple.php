<?php
if (!defined('ABSPATH')) exit;

/**
 * ✅ VERSIÓN SIMPLIFICADA - BASADA EN v1.4 QUE FUNCIONABA
 * Sin over-engineering, sin sistemas complejos
 */
class MimerFormsVDI {
    
    /**
     * ✅ FUNCIÓN SIMPLIFICADA PARA DEPO PROVERA
     */
    public static function send_depo_provera_to_api($fields) {
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        $zip_code = (string) $fields['lead_zip_code'];
        $attorney = strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No';

        $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';

        // Mapear campos simple
        $data = [
            "lead-first-name"             => $fields['lead_first_name'],
            "lead-last-name"              => $fields['lead_last_name'],
            "lead-email-address"          => $fields['lead_email'],
            "lead-phone"                  => $lead_phone,
            "case-depo-provera-taken"     => $fields['case_depo_provera_taken'],
            "case-depo-provera-use"       => $fields['case_depo_provera_use'],
            "case-injury"                 => $fields['case_injury'],
            "case-description"            => $fields['case_description'],
            "case-attorney"               => $attorney,
            "lead-trusted-form-cert-id"   => $trustedform,
            "lead-ip-address"             => $_SERVER['REMOTE_ADDR'],
            "lead-zip-code"               => $zip_code,
        ];

        $url = 'https://api.valuedirectinc.com/api/submissions?form=ir-lca-depo-post&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=89a78284fe446f579d91ad0768e326e505f40a6bfa95ebf03c38f90eb110d453';
        
        self::simple_api_call($data, $url, 'depo');
    }

    /**
     * ✅ FUNCIÓN SIMPLIFICADA PARA ROUNDUP
     */
    public static function send_roundup_to_api($fields) {
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        
        // Mapear campos RoundUp
        $data = [
            "lead-first-name" => $fields['lead_first_name'],
            "lead-last-name" => $fields['lead_last_name'],
            "lead-email-address" => $fields['lead_email'],
            "lead-phone" => $lead_phone,
            "case-exposed" => $fields['case_exposed'],
            "case-injury" => $fields['case_injury'],
            "case-description" => $fields['case_description'],
            "case-attorney" => strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No',
            "lead-ip-address" => $_SERVER['REMOTE_ADDR'],
            "lead-zip-code" => (string) $fields['lead_zip_code'],
        ];

        $url = 'https://api.valuedirectinc.com/api/submissions?form=ir-lca-roundup-post&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=07c959ecf53e021ffb537dc16e60e7557297eae33536cd6b7a2d153d259fdd2f';
        
        self::simple_api_call($data, $url, 'roundup');
    }

    /**
     * ✅ FUNCIÓN API SIMPLE - BASADA EN LA LÓGICA QUE FUNCIONABA EN v1.4
     */
    private static function simple_api_call($data, $url, $form_type = '') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Log simple
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 📤 Enviando formulario $form_type al API\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);

        // Llamada API simple
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => http_build_query($data)
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $debug_log = "[" . date('Y-m-d H:i:s') . "] ❌ Error API: $error_message\n";
            file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);

        // Procesar respuesta simple
        if (isset($response_data['accepted']) && $response_data['accepted']) {
            // Éxito - guardar URL de redirección
            if (isset($response_data['redirect_url'])) {
                $_SESSION['mimer_api_redirect_url'] = $response_data['redirect_url'];
            } else {
                // URL por defecto según tipo
                $_SESSION['mimer_api_redirect_url'] = $form_type === 'depo' 
                    ? 'https://injuryresolve.com/dp-thankyou/' 
                    : 'https://injuryresolve.com/roundup-thankyou/';
            }
            
            $debug_log = "[" . date('Y-m-d H:i:s') . "] ✅ Formulario $form_type aceptado\n";
        } else {
            // Rechazado - URL de rechazo
            $_SESSION['mimer_api_redirect_url'] = $form_type === 'depo' 
                ? 'https://injuryresolve.com/dp_rejected/' 
                : 'https://injuryresolve.com/roundup-rejected/';
                
            $debug_log = "[" . date('Y-m-d H:i:s') . "] 🚫 Formulario $form_type rechazado\n";
        }
        
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
    }
}
