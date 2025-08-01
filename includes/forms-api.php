<?php
if (!defined('ABSPATH')) exit;

class MimerFormsVDI {
    public static function send_submission_to_vdi($fields) {
        // Log de todos los campos recibidos
        $debug_log = "[" . date('Y-m-d H:i:s') . "] CAMPOS ORIGINALES DEL FORMULARIO\n";
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

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30, // <-- 30s timeout
        ]);

        // Logging
        $log = "[" . date('Y-m-d H:i:s') . "] ENVÍO A VDI\n";
        $log .= "Payload: " . print_r($data, true) . "\n";

        if (is_wp_error($response)) {
            $log .= "Error: " . $response->get_error_message() . "\n";
        } else {
            $log .= "Respuesta: " . wp_remote_retrieve_body($response) . "\n";
        }

        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log, FILE_APPEND);

        // Procesar redirección para AJAX
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);
            if (isset($json['redirect_url']) && !empty($json['redirect_url'])) {
                $redirect = $json['redirect_url'];
            } else {
                $redirect = 'https://injuryresolve.com/dp-thankyou/';
            }
        } else {
            $redirect = 'https://injuryresolve.com/dp-thankyou/';
        }

        // Devuelve JSON para AJAX
        echo json_encode(['redirect' => $redirect]);
        exit;
    }
}
