<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test: Detección de Formulario RoundUp</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid #007cba; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .fields-list { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
        .field-item { background: white; padding: 8px; border-radius: 4px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test: Sistema de Detección RoundUp</h1>
        <p>Verificando la detección correcta del formulario RoundUp con nombres de campo actualizados</p>

        <?php
        // Incluir el archivo de la API
        require_once('../includes/forms-api.php');

        echo '<div class="test-section">';
        echo '<h3>📋 Configuración Actual - RoundUp</h3>';
        
        // Obtener la configuración de RoundUp
        $reflection = new ReflectionClass('Mimer_Forms_API');
        $form_configs_property = $reflection->getProperty('form_configs');
        $form_configs_property->setAccessible(true);
        $form_configs = $form_configs_property->getValue();
        
        $roundup_config = $form_configs['roundup'];
        
        echo '<p><strong>Campos de Detección:</strong></p>';
        echo '<div class="fields-list">';
        foreach ($roundup_config['detection_fields'] as $field) {
            echo '<div class="field-item"><code>' . $field . '</code></div>';
        }
        echo '</div>';
        
        echo '<p><strong>Mapeo de Campos (primeros 6):</strong></p>';
        echo '<div class="fields-list">';
        $count = 0;
        foreach ($roundup_config['field_mappings'] as $form_field => $api_field) {
            if ($count < 6) {
                echo '<div class="field-item"><code>' . $form_field . '</code> → <code>' . $api_field . '</code></div>';
                $count++;
            }
        }
        echo '</div>';
        echo '</div>';

        // Test 1: Formulario RoundUp Real
        echo '<div class="test-section">';
        echo '<h3>🎯 Test 1: Formulario RoundUp Real</h3>';
        
        $roundup_fields = array(
            'lead_first_name' => 'John',
            'lead_last_name' => 'Doe', 
            'lead_email' => 'john@example.com',
            'lead_phone' => '1234567890',
            'case_exposed' => 'Yes, directly exposed',
            'case_exposed_duration' => 'Less than 1 year',
            'case_year_diagnosed' => '2020',
            'case_age_category' => '30-39',
            'case_injury' => 'Non-Hodgkin\'s Lymphoma (NHL)',
            'case_attorney' => 'No',
            'trusted_form_cert_url' => 'https://example.com'
        );
        
        echo '<p><strong>Campos del formulario:</strong></p>';
        echo '<div class="fields-list">';
        foreach ($roundup_fields as $field => $value) {
            echo '<div class="field-item"><code>' . $field . '</code>: ' . htmlspecialchars($value) . '</div>';
        }
        echo '</div>';
        
        $detected_type = Mimer_Forms_API::detect_form_type($roundup_fields);
        
        if ($detected_type === 'roundup') {
            echo '<div class="result success">✅ <strong>ÉXITO:</strong> Formulario detectado correctamente como <code>roundup</code></div>';
        } else {
            echo '<div class="result error">❌ <strong>ERROR:</strong> Formulario detectado como <code>' . $detected_type . '</code> en lugar de <code>roundup</code></div>';
        }
        echo '</div>';

        // Test 2: Formulario Depo Provera (para verificar que no interfiere)
        echo '<div class="test-section">';
        echo '<h3>🧬 Test 2: Formulario Depo Provera</h3>';
        
        $depo_fields = array(
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'case_injury' => 'Blood clots',
            'age_when_started' => '25',
            'duration_of_use' => '2 years',
            'attorney' => 'No'
        );
        
        echo '<p><strong>Campos del formulario:</strong></p>';
        echo '<div class="fields-list">';
        foreach ($depo_fields as $field => $value) {
            echo '<div class="field-item"><code>' . $field . '</code>: ' . htmlspecialchars($value) . '</div>';
        }
        echo '</div>';
        
        $detected_type_depo = Mimer_Forms_API::detect_form_type($depo_fields);
        
        if ($detected_type_depo === 'depo_provera') {
            echo '<div class="result success">✅ <strong>ÉXITO:</strong> Formulario detectado correctamente como <code>depo_provera</code></div>';
        } else {
            echo '<div class="result error">❌ <strong>ERROR:</strong> Formulario detectado como <code>' . $detected_type_depo . '</code> en lugar de <code>depo_provera</code></div>';
        }
        echo '</div>';

        // Test 3: Campos mixtos (debe detectar el que tenga todos los campos requeridos)
        echo '<div class="test-section">';
        echo '<h3>🔀 Test 3: Campos Mixtos</h3>';
        
        $mixed_fields = array(
            'first_name' => 'Mixed',  // Campo de Depo
            'case_exposed' => 'Yes',  // Campo de RoundUp
            'case_injury' => 'NHL',   // Presente en ambos pero con diferente mapeo
            'email' => 'mixed@example.com'
        );
        
        echo '<p><strong>Campos del formulario:</strong></p>';
        echo '<div class="fields-list">';
        foreach ($mixed_fields as $field => $value) {
            echo '<div class="field-item"><code>' . $field . '</code>: ' . htmlspecialchars($value) . '</div>';
        }
        echo '</div>';
        
        $detected_type_mixed = Mimer_Forms_API::detect_form_type($mixed_fields);
        
        echo '<div class="result info">ℹ️ <strong>RESULTADO:</strong> Formulario detectado como <code>' . $detected_type_mixed . '</code></div>';
        echo '<p><em>Nota: Este test verifica el comportamiento con campos parciales. Solo RoundUp tiene case_exposed, pero le falta case_injury para detección completa.</em></p>';
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h3>📊 Resumen de Tests</h3>';
        
        $total_tests = 3;
        $passed_tests = 0;
        
        if ($detected_type === 'roundup') $passed_tests++;
        if ($detected_type_depo === 'depo_provera') $passed_tests++;
        // Test 3 es informativo, no cuenta para pass/fail
        
        echo '<div class="result ' . ($passed_tests === 2 ? 'success' : 'error') . '">';
        echo '<strong>Tests Pasados:</strong> ' . $passed_tests . '/2 tests críticos';
        echo '</div>';
        
        if ($passed_tests === 2) {
            echo '<div class="result success">🎉 <strong>¡PERFECTO!</strong> El sistema de detección funciona correctamente con los nombres de campo actualizados.</div>';
        } else {
            echo '<div class="result error">⚠️ <strong>ATENCIÓN:</strong> Hay problemas con la detección automática. Revisar configuración.</div>';
        }
        echo '</div>';
        ?>
    </div>
</body>
</html>
