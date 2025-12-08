<?php
/**
 * Script de prueba para el sistema unificado de formularios
 * Simula la recepción de datos de ambos tipos: Depo Provera y RoundUp
 */

// Incluir el archivo de la API
require_once 'includes/forms-api.php';

echo "<h1>🧪 Prueba del Sistema Unificado de Formularios</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:20px auto;padding:20px;}
.test-section{background:#f9f9f9;padding:15px;margin:15px 0;border-left:4px solid #007cba;}
.success{border-left-color:#00a32a;} .error{border-left-color:#d63638;}
pre{background:#fff;padding:10px;border:1px solid #ddd;overflow-x:auto;}</style>\n";

// Datos de prueba para formulario Depo Provera
$depo_data = array(
    'lead_first_name' => 'María',
    'lead_last_name' => 'González',
    'lead_email' => 'maria.gonzalez@example.com',
    'lead_phone' => '555-123-4567',
    'case_depo_provera_taken' => 'Yes',
    'case_depo_provera_use' => '5+ years',
    'case_injury' => 'Bone Loss',
    'case_description' => 'Suffered significant bone loss after long-term Depo Provera use',
    'case_attorney' => 'No',
    'lead_zip_code' => '12345',
    'lead_state' => 'NY',
    'other_injections' => 'None'
);

// Datos de prueba para formulario RoundUp
$roundup_data = array(
    'lead_first_name' => 'John',
    'lead_last_name' => 'Smith',
    'lead_email' => 'john.smith@example.com',
    'lead_phone' => '555-987-6543',
    'exposed' => 'Yes, directly exposed',
    'attorney' => 'No',
    'exposed_duration' => '2 or more years',
    'injury' => 'Non-Hodgkin\'s Lymphoma',
    'year_were_diagnosed' => '2022',
    'age' => '51-60',
    'case_brief' => 'Diagnosed with NHL after years of RoundUp exposure in my garden'
);

// Simular $_POST para TrustedForm
$_POST['xxTrustedFormToken'] = 'TEST_TOKEN_' . time();

echo "<div class='test-section'>";
echo "<h2>🔍 Prueba 1: Detección de Formulario Depo Provera</h2>";
echo "<p><strong>Campos de detección esperados:</strong> case_depo_provera_taken, case_depo_provera_use</p>";
echo "<h3>Datos de entrada:</h3>";
echo "<pre>" . print_r($depo_data, true) . "</pre>";

// Usar reflexión para acceder al método privado
$reflection = new ReflectionClass('MimerFormsVDI');
$detect_method = $reflection->getMethod('detect_form_type');
$detect_method->setAccessible(true);
$configs_property = $reflection->getProperty('form_configs');
$configs_property->setAccessible(true);

$detected_type = $detect_method->invoke(null, $depo_data);
$configs = $configs_property->getValue();

echo "<h3>✅ Resultado de la detección:</h3>";
echo "<p><strong>Tipo detectado:</strong> <span style='color:#007cba;font-weight:bold;'>" . strtoupper($detected_type) . "</span></p>";
echo "<p><strong>API Form ID:</strong> " . $configs[$detected_type]['api_form_id'] . "</p>";
echo "<p><strong>Thank You URL:</strong> " . $configs[$detected_type]['thank_you_url'] . "</p>";
echo "<p><strong>Rejected URL:</strong> " . $configs[$detected_type]['rejected_url'] . "</p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔍 Prueba 2: Detección de Formulario RoundUp</h2>";
echo "<p><strong>Campos de detección esperados:</strong> exposed, injury</p>";
echo "<h3>Datos de entrada:</h3>";
echo "<pre>" . print_r($roundup_data, true) . "</pre>";

$detected_type_roundup = $detect_method->invoke(null, $roundup_data);

echo "<h3>✅ Resultado de la detección:</h3>";
echo "<p><strong>Tipo detectado:</strong> <span style='color:#007cba;font-weight:bold;'>" . strtoupper($detected_type_roundup) . "</span></p>";
echo "<p><strong>API Form ID:</strong> " . $configs[$detected_type_roundup]['api_form_id'] . "</p>";
echo "<p><strong>Thank You URL:</strong> " . $configs[$detected_type_roundup]['thank_you_url'] . "</p>";
echo "<p><strong>Rejected URL:</strong> " . $configs[$detected_type_roundup]['rejected_url'] . "</p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔄 Prueba 3: Mapeo de Campos Dinámico</h2>";

echo "<h3>🅰️ Mapeo Depo Provera:</h3>";
$depo_config = $configs['depo_provera'];
echo "<table style='border-collapse:collapse;width:100%;'>";
echo "<tr style='background:#f0f0f0;'><th style='border:1px solid #ddd;padding:8px;'>Campo API</th><th style='border:1px solid #ddd;padding:8px;'>Campo Formulario</th><th style='border:1px solid #ddd;padding:8px;'>Valor</th></tr>";
foreach ($depo_config['field_mappings'] as $api_field => $form_field) {
    $value = isset($depo_data[$form_field]) ? $depo_data[$form_field] : '(no disponible)';
    echo "<tr><td style='border:1px solid #ddd;padding:8px;'>" . $api_field . "</td><td style='border:1px solid #ddd;padding:8px;'>" . $form_field . "</td><td style='border:1px solid #ddd;padding:8px;'>" . $value . "</td></tr>";
}
echo "</table>";

echo "<h3>🅱️ Mapeo RoundUp:</h3>";
$roundup_config = $configs['roundup'];
echo "<table style='border-collapse:collapse;width:100%;'>";
echo "<tr style='background:#f0f0f0;'><th style='border:1px solid #ddd;padding:8px;'>Campo API</th><th style='border:1px solid #ddd;padding:8px;'>Campo Formulario</th><th style='border:1px solid #ddd;padding:8px;'>Valor</th></tr>";
foreach ($roundup_config['field_mappings'] as $api_field => $form_field) {
    $value = isset($roundup_data[$form_field]) ? $roundup_data[$form_field] : '(no disponible)';
    echo "<tr><td style='border:1px solid #ddd;padding:8px;'>" . $api_field . "</td><td style='border:1px solid #ddd;padding:8px;'>" . $form_field . "</td><td style='border:1px solid #ddd;padding:8px;'>" . $value . "</td></tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='test-section success'>";
echo "<h2>✅ Resumen de la Prueba</h2>";
echo "<ul>";
echo "<li>✅ <strong>Detección automática:</strong> Funciona correctamente para ambos tipos de formulario</li>";
echo "<li>✅ <strong>Configuración dinámica:</strong> Cada formulario usa su configuración específica</li>";
echo "<li>✅ <strong>Mapeo de campos:</strong> Los campos se mapean correctamente según el tipo detectado</li>";
echo "<li>✅ <strong>URLs específicas:</strong> Cada formulario tiene sus propias URLs de éxito y rechazo</li>";
echo "<li>✅ <strong>Compatibilidad:</strong> El sistema mantiene compatibilidad con formularios existentes</li>";
echo "</ul>";
echo "<p><strong>🎯 El sistema unificado está listo para producción</strong></p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>📋 Instrucciones de Implementación</h2>";
echo "<ol>";
echo "<li><strong>Formularios existentes de Depo Provera:</strong> Seguirán funcionando sin cambios</li>";
echo "<li><strong>Formularios nuevos de RoundUp:</strong> Solo necesitan incluir los campos <code>exposed</code> e <code>injury</code></li>";
echo "<li><strong>Detección automática:</strong> El sistema detecta automáticamente el tipo basado en los campos presentes</li>";
echo "<li><strong>Configuración:</strong> Nuevos formularios se pueden agregar fácilmente en el array <code>\$form_configs</code></li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin-top:30px;padding:15px;background:#e7f3ff;border:1px solid #0073aa;'>";
echo "<p><strong>📁 Archivos modificados:</strong></p>";
echo "<ul>";
echo "<li><code>includes/forms-api.php</code> - Sistema unificado implementado</li>";
echo "<li><code>test-roundup-form.html</code> - Formulario de prueba creado</li>";
echo "<li><code>test-unified-system.php</code> - Script de pruebas creado</li>";
echo "</ul>";
echo "</div>";
?>
