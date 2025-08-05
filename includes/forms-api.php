<?php
if (!defined('ABSPATH')) exit;

class MimerFormsVDI {
    public static function send_submission_to_vdi($fields) {
        // Verificar si está en modo de pruebas
        $test_mode = get_option('mimer_test_mode_enabled', 0);
        
        // Log de todos los campos recibidos
        $debug_log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 MODO PRODUCCIÓN") . " - CAMPOS ORIGINALES DEL FORMULARIO\n";
        $debug_log .= print_r($fields, true) . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        // Limpiar número de teléfono
        $lead_phone = preg_replace('/[^0-9]/', '', $fields['lead_phone']);
        // Asegurar que el código postal se envíe como string
        $zip_code = (string) $fields['lead_zip_code'];
        $attorney = strtolower(trim($fields['case_attorney'])) === 'yes' ? 'Yes' : 'No';

        $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';

        // Mapear los campos del formulario de Elementor a los campos requeridos por VDI
$data = [
  "lead-first-name"             => $fields['lead_first_name'],
  "lead-last-name"              => $fields['lead_last_name'],
  "lead-email-address"          => $fields['lead_email'],
  "lead-phone"                  => $lead_phone,
  "case-depo-provera-taken"     => $fields['case_depo_provera_taken'],
  "case-depo-provera-use"       => $fields['case_depo_provera_use'],
  "case-injury"                 => $fields['case_injury'], // <--- NUEVO CAMPO
  "case-diagnosis"              => $fields['case_diagnosis'],
  "case-description"            => $fields['case_description'],
  "case-attorney"               => $attorney,
  "lead-trusted-form-cert-id"   => $trustedform,
  "lead-ip-address"             => $_SERVER['REMOTE_ADDR'],
  "lead-zip-code"               => $zip_code,
];
        $url = 'https://api-vdi.luchtech.dev/api/submissions?form=depo-provera-injury-resolve&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=f6bed0c57b7e6745e427faf65796f2fef47e8fb8ea1c01566ee4ba576f34e0ed';

        // Logging
        $log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 ENVÍO A VDI") . "\n";
        $log .= "Payload: " . print_r($data, true) . "\n";

        // Si está en modo de pruebas, simular respuesta
        if ($test_mode) {
            $log .= "🧪 MODO PRUEBAS ACTIVADO - NO se envía al API real\n";
            $log .= "Simulando respuesta exitosa...\n";
            
            // Simular respuesta exitosa del API
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
            $log .= "Respuesta simulada: " . print_r($json, true) . "\n";
            
        } else {
            // Envío real al API
            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($data),
                'timeout' => 30, // <-- 30s timeout
            ]);

            if (is_wp_error($response)) {
                $log .= "Error: " . $response->get_error_message() . "\n";
            } else {
                $log .= "Respuesta: " . wp_remote_retrieve_body($response) . "\n";
            }
        }

        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // Procesar respuesta del API, pero SIN redirección ni AJAX handler
        if ($test_mode) {
            // En modo de pruebas, ya tenemos la respuesta simulada
            // $json ya está definida arriba
        } else {
            // Procesar respuesta real
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $log .= "Cuerpo crudo de respuesta: " . $body . "\n";
                $json = json_decode($body, true);
                $log .= "Respuesta decodificada: " . print_r($json, true) . "\n";
                // Obtener la URL de redirección
                if (isset($json['redirect_url']) && !empty($json['redirect_url'])) {
                    $redirect_url = $json['redirect_url'];
                } else {
                    $redirect_url = 'https://injuryresolve.com/dp-thankyou/';
                }
            } else {
                $log .= "Error en la petición a la API.\n";
                $redirect_url = 'https://injuryresolve.com/dp-thankyou/';
            }
        }
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // Guardar la URL en la sesión
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['mimer_last_redirect_url'] = $redirect_url;
        $_SESSION['mimer_api_lead_id'] = isset($json['data']['api_lead_id']) ? $json['data']['api_lead_id'] : '';
        $_SESSION['mimer_api_response_message'] = isset($json['data']['api_response_message']) ? $json['data']['api_response_message'] : '';
        $_SESSION['mimer_api_validation_errors'] = isset($json['data']['api_validation_errors']) ? $json['data']['api_validation_errors'] : '';
        $_SESSION['mimer_api_redirect_url'] = isset($json['data']['api_redirect_url']) ? $json['data']['api_redirect_url'] : '';

        // Solo guardar en sesión, no devolver nada para redirección
    }
}