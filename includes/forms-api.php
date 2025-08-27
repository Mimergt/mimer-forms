<?php
if (!defined('ABSPATH')) exit;

class MimerFormsVDI {
    public static function send_submission_to_vdi($fields) {
        // Verificar si está en modo de pruebas
        $test_mode = get_option('mimer_test_mode_enabled', 0);
        
        // Log simplificado de recepción
        $debug_log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 MODO PRODUCCIÓN") . " - Formulario recibido (" . count($fields) . " campos)\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // 📝 SOLO GUARDAR INFO - SIN REDIRECCIONES AUTOMÁTICAS
        $case_injury = isset($fields['case_injury']) ? trim($fields['case_injury']) : '';
        
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 📝 CASE_INJURY: '" . $case_injury . "' - Solo guardando info para shortcode\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        // Asegurar que el código postal se envíe como string
        $zip_code = (string) $fields['lead_zip_code'];
        $attorney = strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No';

        // Mapear trustedform_token, y si no existe, usar trustedform_cert
        $trustedform = '';
        if (isset($fields['trustedform_token']) && !empty($fields['trustedform_token'])) {
            $trustedform = sanitize_text_field($fields['trustedform_token']);
        } elseif (isset($fields['trustedform_cert']) && !empty($fields['trustedform_cert'])) {
            $trustedform = sanitize_text_field($fields['trustedform_cert']);
        }

        // Mapear los campos del formulario de Elementor a los campos requeridos por VDI
$data = [
  "lead-first-name"             => $fields['lead_first_name'],
  "lead-last-name"              => $fields['lead_last_name'],
  "lead-email-address"          => $fields['lead_email'],
  "lead-phone"                  => $lead_phone,
  "case-depo-provera-taken"     => $fields['case_depo_provera_taken'],
  "case-depo-provera-use"       => $fields['case_depo_provera_use'],
  "case-injury"                 => $fields['case_injury'],
  "case-diagnosis"              => '',
  "case-description"            => isset($fields['case_description']) ? $fields['case_description'] : '',
  "case-attorney"               => $attorney,
  "lead-trusted-form-cert-id"   => $trustedform,
  "lead-ip-address"             => $_SERVER['REMOTE_ADDR'],
  "lead-zip-code"               => $zip_code,
  // 🆕 CAMPOS ACTUALIZADOS
  "other-injections"            => isset($fields['other_injections']) ? $fields['other_injections'] : '',
  "case-depo-provera-ba03"      => isset($fields['case_depo_provera_ba03']) ? $fields['case_depo_provera_ba03'] : '',
];
        
        // 🔍 DEBUG: Log de TrustedForm capturado
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔍 TrustedForm capturado: " . $trustedform . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // 🚨 URL DEL API COMENTADA POR SEGURIDAD - MODO PRUEBAS EXTREMAS 🚨
        // RECORDATORIO: Descomentar cuando se confirme que el modo de pruebas funciona correctamente
        $url = 'https://api-vdi.luchtech.dev/api/submissions?form=depo-provera-injury-resolve&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=f6bed0c57b7e6745e427faf65796f2fef47e8fb8ea1c01566ee4ba576f34e0ed';
        // $url = ''; // URL INTENCIONALMENTE VACÍA PARA PREVENIR ENVÍOS ACCIDENTALES

        // Logging simplificado
        $log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 ENVÍO A VDI") . " - Preparando envío\n";
        
        // 📋 AGREGAR JSON PARA VALIDACIÓN EXTERNA
        $json_payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $log .= "📋 JSON QUE SE ENVIARÍA AL API:\n" . $json_payload . "\n";
        $log .= "🔗 URL DESTINO: " . ($url ?: 'URL_COMENTADA') . "\n";

        // Si está en modo de pruebas, simular respuesta
        if ($test_mode) {
            $log .= "🧪 MODO PRUEBAS ACTIVADO - NO se envía al API real\n";
            $log .= "Simulando respuesta exitosa...\n";
            
            // Simular respuesta exitosa del API con parámetro de prueba
            $json = [
                'success' => true,
                'redirect_url' => 'https://injuryresolve.com/dp-thankyou/',
                'data' => [
                    'api_lead_id' => 'TEST_' . time(),
                    'api_response_message' => 'Test submission successful',
                    'api_validation_errors' => '',
                    'api_redirect_url' => 'https://injuryresolve.com/dp-thankyou/'
                ]
            ];
            
            $redirect_url = $json['redirect_url'];
            $log .= "✅ Respuesta simulada exitosa - Redirect URL: " . $json['data']['api_redirect_url'] . "\n";
            
        } else {
            // Envío real al API
            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($data),
                'timeout' => 10, // <-- 10s timeout
            ]);

            if (is_wp_error($response)) {
                $log .= "❌ Error en petición: " . $response->get_error_message() . "\n";
                $redirect_url = 'https://injuryresolve.com/dp_rejected/';
            } else {
                $body = wp_remote_retrieve_body($response);
                $log .= "📥 Respuesta recibida: " . $body . "\n";
                $json = json_decode($body, true);
                
                // Verificar si el API devuelve una URL de redirección válida
                if (isset($json['redirect_url']) && !empty($json['redirect_url'])) {
                    $redirect_url = $json['redirect_url'];
                    $log .= "✅ API devolvió redirect_url: " . $redirect_url . "\n";
                } else if (isset($json['data']['api_redirect_url']) && !empty($json['data']['api_redirect_url'])) {
                    $redirect_url = $json['data']['api_redirect_url'];
                    $log .= "✅ API devolvió api_redirect_url: " . $redirect_url . "\n";
                } else {
                    // Solo usar dp_rejected cuando el API NO devuelva redirección
                    $redirect_url = 'https://injuryresolve.com/dp_rejected/';
                    $log .= "⚠️ API no devolvió URL de redirección - usando dp_rejected\n";
                }
            }
        }

        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // Guardar la URL en la sesión Y en cookie como backup
        // 📥 GUARDAR RESPUESTA EN SESIÓN - El shortcode decidirá qué hacer
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Guardar toda la info del API en sesión
        $_SESSION['mimer_case_injury'] = $case_injury;
        $_SESSION['mimer_api_lead_id'] = isset($json['data']['api_lead_id']) ? $json['data']['api_lead_id'] : '';
        $_SESSION['mimer_api_response_message'] = isset($json['data']['api_response_message']) ? $json['data']['api_response_message'] : '';
        $_SESSION['mimer_api_validation_errors'] = isset($json['data']['api_validation_errors']) ? $json['data']['api_validation_errors'] : '';
        
        // Guardar URL del API (si existe)
        $api_redirect_url = '';
        if (isset($json['data']['api_redirect_url']) && !empty($json['data']['api_redirect_url'])) {
            $api_redirect_url = $json['data']['api_redirect_url'];
            $log .= "✅ API devolvió api_redirect_url: " . $api_redirect_url . "\n";
        } else if (isset($json['redirect_url']) && !empty($json['redirect_url'])) {
            $api_redirect_url = $json['redirect_url'];
            $log .= "✅ API devolvió redirect_url: " . $api_redirect_url . "\n";
        }
        
        $_SESSION['mimer_api_redirect_url'] = $api_redirect_url;
        $_SESSION['mimer_last_redirect_url'] = $api_redirect_url; // Backward compatibility
        
        // BACKUP: También guardar en cookie por si falla la sesión
        if (!empty($api_redirect_url)) {
            setcookie('mimer_redirect_backup', $api_redirect_url, time() + 300, '/'); // 5 minutos
            $log .= "🍪 Cookie backup guardada: " . $api_redirect_url . "\n";
        }
        
        $log .= "📝 Info guardada en sesión - El shortcode manejará la redirección\n";

        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // Solo guardar en sesión, no devolver nada para redirección
    }
}