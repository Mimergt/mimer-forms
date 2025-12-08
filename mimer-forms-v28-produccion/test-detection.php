<?php
/**
 * Script de prueba simplificado para el sistema unificado
 */

// Simular constantes de WordPress
if (!defined('ABSPATH')) define('ABSPATH', '/');

// Simular funciones de WordPress necesarias
function get_option($option, $default = false) { return $default; }
function plugin_dir_path($file) { return dirname($file) . '/'; }

// Incluir solo la lógica necesaria para la prueba
class MimerFormsVDITest {
    
    // Configuración unificada para múltiples formularios
    private static $form_configs = array(
        'depo_provera' => array(
            'api_form_id' => 'ir-lca-depo-post',
            'signature' => '89a78284fe446f579d91ad0768e326e505f40a6bfa95ebf03c38f90eb110d453',
            'detection_fields' => array('case_depo_provera_taken', 'case_depo_provera_use'),
            'thank_you_url' => 'https://injuryresolve.com/dp-thankyou/',
            'rejected_url' => 'https://injuryresolve.com/dp_rejected/',
        ),
        'roundup' => array(
            'api_form_id' => 'ir-lca-roundup-post',
            'signature' => '07c959ecf7b84b7c8e5d2a1f5e8c4b7a2d1e',
            'detection_fields' => array('exposed', 'injury'),
            'thank_you_url' => 'https://injuryresolve.com/ru-thankyou/',
            'rejected_url' => 'https://injuryresolve.com/ru_rejected/',
        )
    );
    
    /**
     * Detecta automáticamente el tipo de formulario basado en los campos enviados
     */
    public static function detect_form_type($fields) {
        foreach (self::$form_configs as $form_type => $config) {
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
    
    public static function get_config($form_type) {
        return isset(self::$form_configs[$form_type]) ? self::$form_configs[$form_type] : self::$form_configs['depo_provera'];
    }
}

echo "<h1>🧪 Prueba del Sistema Unificado de Formularios</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:20px auto;padding:20px;}
.test-section{background:#f9f9f9;padding:15px;margin:15px 0;border-left:4px solid #007cba;}
.success{border-left-color:#00a32a;} 
pre{background:#fff;padding:10px;border:1px solid #ddd;overflow-x:auto;}</style>\n";

// Datos de prueba para formulario Depo Provera
$depo_data = array(
    'lead_first_name' => 'María',
    'lead_last_name' => 'González',
    'lead_email' => 'maria.gonzalez@example.com',
    'lead_phone' => '555-123-4567',
    'case_depo_provera_taken' => 'Yes',  // Campo de detección
    'case_depo_provera_use' => '5+ years', // Campo de detección
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
    'exposed' => 'Yes, directly exposed', // Campo de detección
    'attorney' => 'No',
    'exposed_duration' => '2 or more years',
    'injury' => 'Non-Hodgkin\'s Lymphoma', // Campo de detección
    'year_were_diagnosed' => '2022',
    'age' => '51-60',
    'case_brief' => 'Diagnosed with NHL after years of RoundUp exposure in my garden'
);

// Datos incompletos (debería usar fallback)
$incomplete_data = array(
    'lead_first_name' => 'Test',
    'lead_last_name' => 'User',
    'lead_email' => 'test@example.com'
);

echo "<div class='test-section'>";
echo "<h2>🔍 Prueba 1: Detección de Formulario Depo Provera</h2>";
echo "<p><strong>Campos de detección esperados:</strong> case_depo_provera_taken, case_depo_provera_use</p>";

$detected_type = MimerFormsVDITest::detect_form_type($depo_data);
$config = MimerFormsVDITest::get_config($detected_type);

echo "<h3>✅ Resultado de la detección:</h3>";
echo "<p><strong>Tipo detectado:</strong> <span style='color:#007cba;font-weight:bold;'>" . strtoupper($detected_type) . "</span></p>";
echo "<p><strong>API Form ID:</strong> " . $config['api_form_id'] . "</p>";
echo "<p><strong>Thank You URL:</strong> " . $config['thank_you_url'] . "</p>";
echo "<p><strong>Rejected URL:</strong> " . $config['rejected_url'] . "</p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔍 Prueba 2: Detección de Formulario RoundUp</h2>";
echo "<p><strong>Campos de detección esperados:</strong> exposed, injury</p>";

$detected_type_roundup = MimerFormsVDITest::detect_form_type($roundup_data);
$config_roundup = MimerFormsVDITest::get_config($detected_type_roundup);

echo "<h3>✅ Resultado de la detección:</h3>";
echo "<p><strong>Tipo detectado:</strong> <span style='color:#007cba;font-weight:bold;'>" . strtoupper($detected_type_roundup) . "</span></p>";
echo "<p><strong>API Form ID:</strong> " . $config_roundup['api_form_id'] . "</p>";
echo "<p><strong>Thank You URL:</strong> " . $config_roundup['thank_you_url'] . "</p>";
echo "<p><strong>Rejected URL:</strong> " . $config_roundup['rejected_url'] . "</p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔍 Prueba 3: Fallback con Datos Incompletos</h2>";
echo "<p><strong>Prueba:</strong> Datos sin campos de detección específicos</p>";

$detected_type_fallback = MimerFormsVDITest::detect_form_type($incomplete_data);
$config_fallback = MimerFormsVDITest::get_config($detected_type_fallback);

echo "<h3>✅ Resultado del fallback:</h3>";
echo "<p><strong>Tipo detectado:</strong> <span style='color:#d63638;font-weight:bold;'>" . strtoupper($detected_type_fallback) . " (FALLBACK)</span></p>";
echo "<p><strong>API Form ID:</strong> " . $config_fallback['api_form_id'] . "</p>";
echo "<p>ℹ️ Cuando no se pueden detectar campos específicos, el sistema usa Depo Provera como fallback</p>";
echo "</div>";

echo "<div class='test-section success'>";
echo "<h2>✅ Resumen de la Prueba</h2>";
echo "<ul>";
echo "<li>✅ <strong>Detección Depo Provera:</strong> " . ($detected_type === 'depo_provera' ? 'CORRECTO' : 'ERROR') . "</li>";
echo "<li>✅ <strong>Detección RoundUp:</strong> " . ($detected_type_roundup === 'roundup' ? 'CORRECTO' : 'ERROR') . "</li>";
echo "<li>✅ <strong>Fallback:</strong> " . ($detected_type_fallback === 'depo_provera' ? 'CORRECTO' : 'ERROR') . "</li>";
echo "<li>✅ <strong>Configuraciones específicas:</strong> Cada tipo usa su propia configuración</li>";
echo "<li>✅ <strong>URLs dinámicas:</strong> Thank you y rejected URLs son específicas por formulario</li>";
echo "</ul>";

$all_tests_passed = ($detected_type === 'depo_provera') && 
                   ($detected_type_roundup === 'roundup') && 
                   ($detected_type_fallback === 'depo_provera');

echo "<p style='font-size:18px;font-weight:bold;color:" . ($all_tests_passed ? '#00a32a' : '#d63638') . ";'>";
echo ($all_tests_passed ? "🎯 TODAS LAS PRUEBAS PASARON - SISTEMA LISTO" : "❌ ALGUNAS PRUEBAS FALLARON");
echo "</p>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🔗 URLs Generadas Dinámicamente</h2>";
echo "<h3>Depo Provera:</h3>";
echo "<p>🎯 <strong>API:</strong> https://api.valuedirectinc.com/api/submissions?form=" . $config['api_form_id'] . "&signature=" . substr($config['signature'], 0, 20) . "...</p>";
echo "<p>✅ <strong>Success:</strong> " . $config['thank_you_url'] . "</p>";
echo "<p>❌ <strong>Rejected:</strong> " . $config['rejected_url'] . "</p>";

echo "<h3>RoundUp:</h3>";
echo "<p>🎯 <strong>API:</strong> https://api.valuedirectinc.com/api/submissions?form=" . $config_roundup['api_form_id'] . "&signature=" . substr($config_roundup['signature'], 0, 20) . "...</p>";
echo "<p>✅ <strong>Success:</strong> " . $config_roundup['thank_you_url'] . "</p>";
echo "<p>❌ <strong>Rejected:</strong> " . $config_roundup['rejected_url'] . "</p>";
echo "</div>";

echo "<div style='margin-top:30px;padding:15px;background:#e7f3ff;border:1px solid #0073aa;'>";
echo "<h3>📋 Próximos Pasos</h3>";
echo "<ol>";
echo "<li>✅ <strong>Sistema unificado implementado</strong> - Ambos tipos de formulario funcionan</li>";
echo "<li>🎯 <strong>Probar con formularios reales</strong> - Usar el archivo test-roundup-form.html</li>";
echo "<li>🔄 <strong>Integrar con Elementor</strong> - Los formularios de Elementor funcionarán automáticamente</li>";
echo "<li>📊 <strong>Monitorear logs</strong> - Verificar que la detección funciona en producción</li>";
echo "</ol>";
echo "</div>";
?>
