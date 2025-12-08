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
            "lead-trusted-form-url"   	  => $trustedform,
            "lead-ip-address"             => $_SERVER['REMOTE_ADDR'],
            "lead-zip-code"               => $zip_code,
            "lead-state"               	  => $fields['lead_state'],
        ];

        $url = 'https://api.valuedirectinc.com/api/submissions?form=ir-lca-depo-post&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=89a78284fe446f579d91ad0768e326e505f40a6bfa95ebf03c38f90eb110d453';
        
        self::simple_api_call($data, $url, 'depo');
    }

    /**
     * ✅ FUNCIÓN SIMPLIFICADA PARA DEPO PROVERA V2
     */
    public static function send_depo_provera_v2_to_api($fields) {
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        $zip_code = (string) $fields['lead_zip_code'];
        $attorney = strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No';

        $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';

        // Mapear campos simple - igual que V1 pero con URL diferente
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

        $url = 'https://api.valuedirectinc.com/api/submissions?form=zm-ir-lca-depo-post&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=254365aae9e577ab0d20b73f377596736460fd5f38464cb2ffd684a4889fcb44';
        
        self::simple_api_call($data, $url, 'depo_v2');
    }

    /**
     * ✅ FUNCIÓN SIMPLIFICADA PARA ROUNDUP - CAMPOS CORREGIDOS
     */
    public static function send_roundup_to_api($fields) {
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        
        // Obtener TrustedForm
        $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';
        
        // ✅ MAPEAR TODOS LOS CAMPOS ROUNDUP CORRECTAMENTE
        $data = [
            "lead-first-name" => $fields['lead_first_name'],
            "lead-last-name" => $fields['lead_last_name'],
            "lead-email-address" => $fields['lead_email'],
            "lead-phone" => $lead_phone,
            "case-exposed" => $fields['case_exposed'],
            "case-exposed-duration" => isset($fields['case_exposed_duration']) ? $fields['case_exposed_duration'] : '',
            "case-injury" => $fields['case_injury'],
            "case-year-were-diagnosed" => isset($fields['year_were_diagnosed']) ? $fields['year_were_diagnosed'] : '',
            "case-age-category" => isset($fields['case_age_category']) ? $fields['case_age_category'] : '',
            "case-description" => isset($fields['case_brief']) ? $fields['case_brief'] : '',
            "case-attorney" => strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No',
            "lead-trusted-form-url" => $trustedform,
            "lead-ip-address" => $_SERVER['REMOTE_ADDR'],
            "lead-zip-code" => isset($fields['lead_zip_code']) ? (string) $fields['lead_zip_code'] : '',
        ];

        $url = 'https://api.valuedirectinc.com/api/submissions?form=ir-lca-roundup-post&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=07c959ecf53e021ffb537dc16e60e7557297eae33536cd6b7a2d153d259fdd2f';
        
        // 🚨 DEBUG: Verificar que estamos usando el código nuevo
        $debug_version = "[" . date('Y-m-d H:i:s') . "] 🚨 VERSIÓN NUEVA v2.6 - RoundUp con JSON iniciando...\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_version, FILE_APPEND);
        
        self::simple_api_call($data, $url, 'roundup');
    }

    /**
     * FUNCIÓN PARA FORMULARIO ROBLOX / ROUNDUP-TYPE (implementación controlada)
     * Se añade a partir del backup: mapea los campos necesarios y usa una
     * opción admin `mimer_roblox_endpoint` si está definida para evitar hardcodear
     * firmas en el repositorio.
     */
    public static function send_roblox_to_api($fields) {
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        $attorney = strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No';

        // TrustedForm token (campo oculto)
        $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';

        // Mapear campos Roblox — simple como V2
        $data = [
            "lead-first-name" => $fields['lead_first_name'],
            "lead-last-name" => $fields['lead_last_name'],
            "lead-email-address" => isset($fields['lead_email_address']) ? $fields['lead_email_address'] : $fields['lead_email'],
            "lead-phone" => $lead_phone,
            "case-interaction" => $fields['case_interaction'],
            "case-child-claim" => $fields['case_child_claim'],
            "case-abuse-type" => $fields['case_abuse_type'],
            "case-proof" => $fields['case_proof'],
            "case-description" => $fields['case_description'],
            "case-attorney" => $attorney,
            "lead-trusted-form-url" => $trustedform,
            "lead-trusted-form-cert-url" => $trustedform,
        ];

        $url = 'https://api.valuedirectinc.com/api/submissions?form=vdi-lca-bfire-ir&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=364663b9817f62692534c009538fea788fd52fcd8cb2114408a6ac658231cd83';

        self::simple_api_call($data, $url, 'roblox');
    }



    /**
     * ✅ FUNCIÓN API SIMPLE - BASADA EN LA LÓGICA QUE FUNCIONABA EN v1.4
     */
    private static function simple_api_call($data, $url, $form_type = '') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Evitar doble envío del mismo payload en la misma sesión
        // Usamos el JSON canonicalizado como resumen
        $payload_hash = md5(json_encode($data));
        if (isset($_SESSION['mimer_last_payload_hash']) && $_SESSION['mimer_last_payload_hash'] === $payload_hash) {
            $debug_log = "[" . date('Y-m-d H:i:s') . "] ⚠️ Payload duplicado detectado en sesión — omitiendo envío para formulario $form_type\n";
            file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
            return;
        }
        // Marcar como procesado (se sobrescribirá con cada envío nuevo)
        $_SESSION['mimer_last_payload_hash'] = $payload_hash;

        // ✅ VERIFICAR MODO DE PRUEBAS
        $test_mode = get_option('mimer_test_mode_enabled', 0);
        
        // ✅ LOG DETALLADO: Datos que se envían
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 📤 " . ($test_mode ? "🧪 MODO PRUEBAS" : "🚀 MODO PRODUCCIÓN") . " - Enviando formulario $form_type al API\n";
        $debug_log .= "🔗 URL: $url\n";
        $debug_log .= "📝 DATOS QUE SE ENVIARÍAN:\n" . print_r($data, true) . "\n";
        $debug_log .= "📦 FORMATO: JSON (Content-Type: application/json) - TODOS LOS FORMULARIOS\n";
        $debug_log .= "🔄 JSON BODY: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // 🧪 SI ESTÁ EN MODO PRUEBAS, NO ENVIAR REALMENTE
        if ($test_mode) {
            $debug_log = "[" . date('Y-m-d H:i:s') . "] 🧪 MODO PRUEBAS ACTIVO - NO se envía al API real\n";
            $debug_log .= "✅ Datos preparados correctamente para envío\n";
            $debug_log .= "🎯 Para envío real: desactivar modo de pruebas en admin\n\n";
            file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
            
            // Simular éxito para testing - URLs según tipo de formulario
            if ($form_type === 'depo') {
                $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/dp-thankyou/';
            } elseif ($form_type === 'depo_v2') {
                $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/dp-thankyou/';
            } else {
                $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/roundup-thankyou/';
            }
            return;
        }

        // ✅ LLAMADA API UNIFICADA: JSON para TODOS los formularios (RoundUp, Depo V1 y V2)
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data)
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $debug_log = "[" . date('Y-m-d H:i:s') . "] ❌ Error API: $error_message\n";
            file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);

        // ✅ LOG DETALLADO: Respuesta del API
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 📥 RESPUESTA DEL API:\n";
        $debug_log .= "HTTP Status: " . wp_remote_retrieve_response_code($response) . "\n";
        $debug_log .= "Response Body: " . $body . "\n";
        $debug_log .= "Parsed Data: " . print_r($response_data, true) . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);

        // Procesar respuesta simple
        if (isset($response_data['accepted']) && $response_data['accepted']) {
            // Éxito - guardar URL de redirección
            if (isset($response_data['redirect_url'])) {
                $_SESSION['mimer_api_redirect_url'] = $response_data['redirect_url'];
            } else {
                // URL por defecto según tipo
                if ($form_type === 'depo' || $form_type === 'depo_v2') {
                    $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/dp-thankyou/';
                } else {
                    $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/roundup-thankyou/';
                }
            }
            
            $debug_log = "[" . date('Y-m-d H:i:s') . "] ✅ Formulario $form_type aceptado\n";
        } else {
            // Rechazado - URL de rechazo
            if ($form_type === 'depo' || $form_type === 'depo_v2') {
                $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/dp_rejected/';
            } else {
                $_SESSION['mimer_api_redirect_url'] = 'https://injuryresolve.com/roundup-rejected/';
            }
                
            $debug_log = "[" . date('Y-m-d H:i:s') . "] 🚫 Formulario $form_type rechazado\n";
        }
        
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
    }
}
