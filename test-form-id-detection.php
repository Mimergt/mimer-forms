<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test: Detección por ID de Formulario</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid #007cba; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .method { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .method-id { background: #28a745; color: white; }
        .method-fields { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test: Sistema de Detección por ID de Formulario</h1>
        <p>Verificando la nueva detección por ID <code>roundup_form</code> con fallback a detección por campos</p>

        <?php
        // Incluir el archivo de la API
        require_once('../includes/forms-api.php');

        echo '<div class="test-section">';
        echo '<h3>🆔 Test 1: Detección por ID - RoundUp</h3>';
        
        // Simular campos de RoundUp con ID
        $roundup_fields_with_id = array(
            'lead_first_name' => 'John',
            'lead_last_name' => 'Doe', 
            'lead_email' => 'john@example.com',
            'case_exposed' => 'Yes, directly exposed',
            'case_injury' => 'Non-Hodgkin\'s Lymphoma (NHL)',
            'case_attorney' => 'No'
        );
        
        // Usar reflection para acceder al método privado
        $reflection = new ReflectionClass('MimerFormsVDI');
        $detect_method = $reflection->getMethod('detect_form_type');
        $detect_method->setAccessible(true);
        
        $detected_type_with_id = $detect_method->invoke(null, $roundup_fields_with_id, 'roundup_form');
        
        echo '<p><strong>ID del formulario:</strong> <code>roundup_form</code></p>';
        echo '<p><strong>Tipo detectado:</strong> <code>' . $detected_type_with_id . '</code> <span class="method method-id">DETECCIÓN POR ID</span></p>';
        
        if ($detected_type_with_id === 'roundup') {
            echo '<div class="result success">✅ <strong>ÉXITO:</strong> Detección por ID funcionando correctamente</div>';
        } else {
            echo '<div class="result error">❌ <strong>ERROR:</strong> ID no detectado correctamente</div>';
        }
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h3>🔍 Test 2: Fallback a Detección por Campos</h3>';
        
        // Simular detección sin ID (fallback)
        $detected_type_no_id = $detect_method->invoke(null, $roundup_fields_with_id, null);
        
        echo '<p><strong>ID del formulario:</strong> <code>null</code> (sin ID)</p>';
        echo '<p><strong>Tipo detectado:</strong> <code>' . $detected_type_no_id . '</code> <span class="method method-fields">DETECCIÓN POR CAMPOS</span></p>';
        
        if ($detected_type_no_id === 'roundup') {
            echo '<div class="result success">✅ <strong>ÉXITO:</strong> Fallback por campos funcionando</div>';
        } else {
            echo '<div class="result error">❌ <strong>ERROR:</strong> Fallback por campos falló</div>';
        }
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h3>🎯 Test 3: Prioridad ID vs Campos</h3>';
        
        // Campos que podrían confundir (sin case_exposed pero con ID correcto)
        $mixed_fields = array(
            'lead_first_name' => 'Test',
            'case_injury' => 'NHL',  // Solo uno de los campos de detección
            // Falta case_exposed - por campos sería depo_provera
        );
        
        $detected_with_id = $detect_method->invoke(null, $mixed_fields, 'roundup_form');
        $detected_without_id = $detect_method->invoke(null, $mixed_fields, null);
        
        echo '<p><strong>Campos incompletos:</strong> Solo <code>case_injury</code>, falta <code>case_exposed</code></p>';
        echo '<p><strong>Con ID roundup_form:</strong> <code>' . $detected_with_id . '</code> <span class="method method-id">POR ID</span></p>';
        echo '<p><strong>Sin ID:</strong> <code>' . $detected_without_id . '</code> <span class="method method-fields">POR CAMPOS</span></p>';
        
        if ($detected_with_id === 'roundup' && $detected_without_id === 'depo_provera') {
            echo '<div class="result success">✅ <strong>ÉXITO:</strong> Prioridad de ID funcionando correctamente</div>';
        } else {
            echo '<div class="result error">❌ <strong>ERROR:</strong> Prioridad no funciona como esperado</div>';
        }
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h3>🧬 Test 4: Formulario Depo Provera (sin afectar)</h3>';
        
        $depo_fields = array(
            'first_name' => 'Jane',
            'case_depo_provera_taken' => 'Yes',
            'case_depo_provera_use' => '2 years',
            'case_injury' => 'Blood clots'
        );
        
        // Depo Provera no debería verse afectado por la nueva lógica
        $detected_depo = $detect_method->invoke(null, $depo_fields, null);
        
        echo '<p><strong>Campos Depo Provera:</strong> <code>case_depo_provera_taken</code>, <code>case_depo_provera_use</code></p>';
        echo '<p><strong>Tipo detectado:</strong> <code>' . $detected_depo . '</code></p>';
        
        if ($detected_depo === 'depo_provera') {
            echo '<div class="result success">✅ <strong>ÉXITO:</strong> Depo Provera no afectado por cambios</div>';
        } else {
            echo '<div class="result error">❌ <strong>ERROR:</strong> Depo Provera afectado incorrectamente</div>';
        }
        echo '</div>';

        echo '<div class="test-section">';
        echo '<h3>📊 Resumen de Tests</h3>';
        
        $total_tests = 4;
        $passed_tests = 0;
        
        if ($detected_type_with_id === 'roundup') $passed_tests++;
        if ($detected_type_no_id === 'roundup') $passed_tests++;
        if ($detected_with_id === 'roundup' && $detected_without_id === 'depo_provera') $passed_tests++;
        if ($detected_depo === 'depo_provera') $passed_tests++;
        
        echo '<div class="result ' . ($passed_tests === $total_tests ? 'success' : 'error') . '">';
        echo '<strong>Tests Pasados:</strong> ' . $passed_tests . '/' . $total_tests;
        echo '</div>';
        
        if ($passed_tests === $total_tests) {
            echo '<div class="result success">🎉 <strong>¡PERFECTO!</strong> El sistema de detección por ID funciona correctamente con fallback a campos.</div>';
        } else {
            echo '<div class="result error">⚠️ <strong>ATENCIÓN:</strong> Hay problemas con la nueva detección por ID. Revisar configuración.</div>';
        }
        
        echo '<h4>🔧 Funcionalidades Verificadas:</h4>';
        echo '<ul>';
        echo '<li>✅ Detección prioritaria por ID <code>roundup_form</code></li>';
        echo '<li>✅ Fallback automático a detección por campos</li>';
        echo '<li>✅ Prioridad correcta: ID > Campos</li>';
        echo '<li>✅ Compatibilidad con Depo Provera mantenida</li>';
        echo '</ul>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
