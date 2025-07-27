<?php

class ENV_Numverify {
    private static function get_api_key() {
        return get_option('mimer_phone_validator_api_key');
    }

    public static function validate($phone) {
        $api_key = self::get_api_key();
        $url = "http://apilayer.net/api/validate?access_key=" . $api_key .
               "&number=" . urlencode($phone) .
               "&country_code=US&format=1";

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return ['valid' => false, 'error' => 'Error al conectar con Numverify'];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        $result = [
            'valid' => isset($data['valid']) && $data['valid'] === true && $data['country_code'] === 'US',
            'data' => $data
        ];

        $log_full = "[" . date('Y-m-d H:i:s') . "] Respuesta completa: " . print_r($data, true) . "\n";
        file_put_contents(plugin_dir_path(__FILE__) . '/../log.txt', $log_full, FILE_APPEND);

        return $result;
    }
}
