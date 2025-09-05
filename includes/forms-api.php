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

          $trustedform = isset($_POST['xxTrustedFormToken']) ? sanitize_text_field($_POST['xxTrustedFormToken']) : 'not available';

        // Mapear los campos del formulario de Elementor a los campos requeridos por VDI
$data = [
  "lead-first-name"             => $fields['lead_first_name'],
  "lead-last-name"              => $fields['lead_last_name'],
  "lead-email-address"          => $fields['lead_email'],
  "lead-phone"                  => $lead_phone,
  "case-depo-provera-taken"     => $fields['case_depo_provera_taken'],
  "case-depo-provera-use"       => $fields['case_depo_provera_use'],
  "case-injury"                 => $fields['case_injury'],
//  "case-diagnosis"              => '',
  "case-description"            => isset($fields['case_description']) ? $fields['case_description'] : '',
  "case-attorney"               => $attorney,
  "lead-trusted-form-url"   => $trustedform,
  "lead-ip-address"             => $_SERVER['REMOTE_ADDR'],
  "lead-zip-code"               => $zip_code,
  "lead-state"                  => isset($fields['lead_state']) ? $fields['lead_state'] : '', // <-- NUEVO CAMPO
  // 🆕 CAMPOS ACTUALIZADOS
  "other-injections"            => isset($fields['other_injections']) ? $fields['other_injections'] : '',
 // "case-depo-provera-ba03"      => isset($fields['case_depo_provera_ba03']) ? $fields['case_depo_provera_ba03'] : '',
];
        
        // 🔍 DEBUG: Log de TrustedForm capturado
        $debug_log = "[" . date('Y-m-d H:i:s') . "] 🔍 TrustedForm capturado: " . $trustedform . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $debug_log, FILE_APPEND);
        
        // 🚨 URL DEL API COMENTADA POR SEGURIDAD - MODO PRUEBAS EXTREMAS 🚨
        // RECORDATORIO: Descomentar cuando se confirme que el modo de pruebas funciona correctamente
        $url = 'https://api.valuedirectinc.com/api/submissions?form=ir-lca-depo-post&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=89a78284fe446f579d91ad0768e326e505f40a6bfa95ebf03c38f90eb110d453';
        // $url = ''; // URL INTENCIONALMENTE VACÍA PARA PREVENIR ENVÍOS ACCIDENTALES

        // Logging simplificado
        $log = "[" . date('Y-m-d H:i:s') . "] " . ($test_mode ? "🧪 MODO PRUEBAS" : "🔴 ENVÍO A VDI") . " - Preparando envío\n";
        
        // 📋 AGREGAR JSON PARA VALIDACIÓN EXTERNA
        $json_payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $log .= "📋 JSON QUE SE ENVÍA AL API:\n" . $json_payload . "\n";
        $log .= "🔗 URL DESTINO: " . ($url ?: 'URL_COMENTADA') . "\n";

        // Si está en modo de pruebas, solo log
        if ($test_mode) {
            $log .= "🧪 MODO PRUEBAS ACTIVADO - NO se envía al API real\n";
            $log .= "✅ Datos preparados correctamente para envío\n";
        } else {
            // Envío real al API - SOLO ENVIAR, NO PROCESAR RESPUESTA
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
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $log .= "✅ Datos enviados al API - Código respuesta: " . $status_code . "\n";
                
                // Solo log de respuesta para debug, no procesamos redirecciones
                $body = wp_remote_retrieve_body($response);
                if (!empty($body)) {
                    $log .= "📥 Respuesta del API: " . substr($body, 0, 200) . "...\n";
                }
            }
        }

        $log .= "� PROCESAMIENTO COMPLETO - Sin redirecciones (maneja Elementor)\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // ✅ FIN - No redirecciones, no sesiones, solo envío al API
    }
}