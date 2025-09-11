<?php
if (!defined('ABSPATH')) exit;

class MimerFormsVDI {
    
    /**
     * ✅ FUNCIÓN SIMPLIFICADA PARA DEPO PROVERA - BASADA EN v1.4 QUE FUNCIONABA
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
    
    /**
     * Detecta automáticamente el tipo de formulario basado en ID o campos enviados
     */
    private static function detect_form_type($fields, $form_id = null) {
        // 🎯 DETECCIÓN POR ID DE FORMULARIO (PRIORIDAD)
        if ($form_id) {
            if ($form_id === 'roundup_form') {
                return 'roundup';
            }
            // Agregar más IDs aquí para otros formularios en el futuro
        }
        
        // 🔍 DETECCIÓN POR CAMPOS (FALLBACK)
        $form_configs = self::get_form_configs();
        foreach ($form_configs as $form_type => $config) {
            $all_detected = true;
            foreach ($config['detection_fields'] as $detection_field) {
                if (!isset($fields[$detection_field])) {
                    $all_detected = false;
                    break;
                }
            }
            if ($all_detected) {
                return $form_type;
            }
        }
        return 'depo_provera'; // Fallback al formulario original
    }
    
    public static function send_submission_to_vdi($fields, $form_id = null) {
        // Asegurar headers correctos para evitar parsererror
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        
        // � SISTEMA DE DETECCIÓN UNIFICADO
        $form_type = self::detect_form_type($fields, $form_id);
        $form_config = self::get_form_configs()[$form_type];
        
        $test_mode = get_option('mimer_test_mode_enabled', 0);
        
        // Log simplificado de recepción con método de detección
        $detection_method = $form_id ? "ID: $form_id" : "CAMPOS";
        $debug_log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 MODO PRODUCCIÓN") . " - Formulario detectado: " . strtoupper($form_type) . " (" . count($fields) . " campos) [Detección: $detection_method]\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // 📝 GUARDAR INFO ESPECÍFICA POR TIPO DE FORMULARIO
        $case_injury = '';
        if ($form_type === 'depo_provera') {
            $case_injury = isset($fields['case_injury']) ? trim($fields['case_injury']) : '';
        } else if ($form_type === 'roundup') {
            $case_injury = isset($fields['case_injury']) ? trim($fields['case_injury']) : '';
        }
        
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 📝 CASE_INJURY (" . $form_type . "): '" . $case_injury . "' - Solo guardando info para shortcode\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // Procesar campos comunes
        $lead_phone_field = isset($fields['lead_phone']) ? $fields['lead_phone'] : '';
        $lead_phone = preg_replace('/[^0-9]/', '', $lead_phone_field);
        
        // Obtener TrustedForm
        $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';
        
        // Procesar attorney field según el tipo de formulario
        $attorney_field = '';
        if ($form_type === 'depo_provera') {
            $attorney_field = isset($fields['case_attorney']) ? trim($fields['case_attorney']) : '';
        } else if ($form_type === 'roundup') {
            $attorney_field = isset($fields['case_attorney']) ? trim($fields['case_attorney']) : '';
        }
        $attorney = strtolower($attorney_field) === 'yes' ? 'Yes' : 'No';

        // 🔄 MAPEO DINÁMICO DE CAMPOS BASADO EN CONFIGURACIÓN
        $data = array();
        foreach ($form_config['field_mappings'] as $api_field => $form_field) {
            if (isset($fields[$form_field])) {
                $data[$api_field] = $fields[$form_field];
            } else {
                // Valores especiales calculados
                if ($api_field === "lead-phone") {
                    $data[$api_field] = $lead_phone;
                } else if ($api_field === "case-attorney") {
                    $data[$api_field] = $attorney;
                } else if ($api_field === "lead-trusted-form-url") {
                    $data[$api_field] = $trustedform;
                } else if ($api_field === "lead-zip-code" && isset($fields['lead_zip_code'])) {
                    $data[$api_field] = (string) $fields['lead_zip_code'];
                } else {
                    $data[$api_field] = '';
                }
            }
        }
        
        // Agregar campos comunes siempre presentes
        $data["lead-ip-address"] = $_SERVER['REMOTE_ADDR'];
        
        // 🔍 DEBUG: Log de TrustedForm capturado
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔍 TrustedForm capturado: " . $trustedform . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // 🔗 URL DEL API DINÁMICA SEGÚN EL TIPO DE FORMULARIO
        if ($form_type === 'roundup') {
            // Para RoundUp usar la nueva estructura
            $query_params = http_build_query($form_config['query_params']);
            $url = $form_config['url'] . '?' . $query_params;
        } else {
            // Para Depo Provera usar la estructura original
            $url = 'https://api.valuedirectinc.com/api/submissions?form=' . $form_config['api_form_id'] . '&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=' . $form_config['signature'];
        }

        // Logging detallado para validación
        $log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 ENVÍO A VDI") . " - Preparando envío para " . strtoupper($form_type) . "\n";
        
        // 📋 JSON COMPLETO PARA VALIDACIÓN EXTERNA
        $json_payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $log .= "═══════════════════════════════════════════════════════════════\n";
        $log .= "📋 JSON QUE SE ENVÍA AL API (" . strtoupper($form_type) . " - " . count($data) . " campos):\n";
        $log .= $json_payload . "\n";
        $log .= "═══════════════════════════════════════════════════════════════\n";
        $log .= "🔗 URL DESTINO: " . ($url ?: 'URL_COMENTADA') . "\n";
        $log .= "📊 TIPO FORMULARIO: " . strtoupper($form_type) . "\n";
        $log .= "🆔 FORM ID: " . ($form_id ?: 'NO DETECTADO') . "\n";

        // Verificar si las redirecciones están habilitadas
        $redirections_enabled = get_option('mimer_redirections_enabled', 1); // Por defecto activadas
        $log .= "🎯 REDIRECCIONES: " . ($redirections_enabled ? 'ACTIVADAS' : 'DESACTIVADAS') . "\n";

        // Si está en modo de pruebas, simular respuesta
        if ($test_mode) {
            $log .= "🧪 MODO PRUEBAS ACTIVADO - NO se envía al API real\n";
            
            if ($redirections_enabled) {
                $log .= "Simulando respuesta exitosa...\n";
                
                // Simular respuesta exitosa del API con URL específica del formulario
                $json = [
                    'success' => true,
                    'redirect_url' => $form_config['thank_you_url'],
                    'data' => [
                        'api_lead_id' => 'TEST_' . time(),
                        'api_response_message' => 'Test submission successful',
                        'api_validation_errors' => '',
                        'api_redirect_url' => $form_config['thank_you_url']
                    ]
                ];
                
                $redirect_url = $json['redirect_url'];
                $log .= "✅ Respuesta simulada exitosa - Redirect URL: " . $json['data']['api_redirect_url'] . "\n";
            } else {
                $log .= "✅ Datos preparados correctamente para envío (redirecciones desactivadas)\n";
            }
            
        } else {
            // Envío real al API
            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => json_encode($data),
                'timeout' => 10,
            ]);

            if (is_wp_error($response)) {
                $log .= "❌ Error en petición: " . $response->get_error_message() . "\n";
                if ($redirections_enabled) {
                    $redirect_url = $form_config['rejected_url'];
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                $status_code = wp_remote_retrieve_response_code($response);
                $log .= "✅ Datos enviados al API - Código respuesta: " . $status_code . "\n";
                $log .= "📥 Respuesta recibida: " . $body . "\n";
                
                if ($redirections_enabled) {
                    $json = json_decode($body, true);
                    
                    // Verificar si el API devuelve una URL de redirección válida
                    if (isset($json['redirect_url']) && !empty($json['redirect_url'])) {
                        $redirect_url = $json['redirect_url'];
                        $log .= "✅ API devolvió redirect_url: " . $redirect_url . "\n";
                    } else if (isset($json['data']['api_redirect_url']) && !empty($json['data']['api_redirect_url'])) {
                        $redirect_url = $json['data']['api_redirect_url'];
                        $log .= "✅ API devolvió api_redirect_url: " . $redirect_url . "\n";
                    } else {
                        // Usar URL de rechazo específica del formulario cuando el API NO devuelva redirección
                        $redirect_url = $form_config['rejected_url'];
                        $log .= "⚠️ API no devolvió URL de redirección - usando rejected_url (" . $form_type . ")\n";
                    }
                } else {
                    $log .= "🎯 Redirecciones desactivadas - Elementor maneja la redirección\n";
                }
            }
        }

        // Solo procesar redirecciones si están habilitadas
        if ($redirections_enabled && isset($redirect_url)) {
            // Guardar la URL en la sesión Y en cookie como backup
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
        }

        $log .= "🎯 PROCESAMIENTO COMPLETO\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // Limpiar output buffer para evitar parsererror
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Solo guardar en sesión si las redirecciones están habilitadas
    }
}