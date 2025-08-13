/**
 * Mimer Forms VDI - Utility Scripts
 * Scripts adicionales para formularios (sin validaciones)
 * Las validaciones están en form-validation.js
 */

// Esperar a que TODO esté completamente cargado (incluyendo scripts externos)
window.addEventListener('load', function() {
  console.log('🔄 Página completamente cargada, iniciando movimiento de campos TrustedForm...');
  
  // 📝 MOVER CAMPOS TRUSTEDFORM DENTRO DEL WRAPPER PARA QUE ELEMENTOR LOS GUARDE
  moveTrustedFormFieldsToWrapper();
});

// Backup con DOMContentLoaded por si 'load' no funciona
document.addEventListener('DOMContentLoaded', function () {
  console.log('Mimer Forms VDI - Utility scripts loaded');
  
  // Esperar un poco más y hacer un intento adicional
  setTimeout(function() {
    console.log('🔄 Intento adicional de movimiento de campos TrustedForm...');
    moveTrustedFormFieldsToWrapper();
  }, 2000);
});

/**
 * Mueve los campos TrustedForm generados dinámicamente dentro del wrapper de Elementor
 * para que se guarden automáticamente en las submissions
 */
function moveTrustedFormFieldsToWrapper() {
  const form = document.querySelector('#dp_form');
  if (!form) {
    console.log('⚠️ Formulario #dp_form no encontrado');
    return;
  }
  
  const wrapper = form.querySelector('.elementor-form-fields-wrapper');
  if (!wrapper) {
    console.log('⚠️ Wrapper .elementor-form-fields-wrapper no encontrado');
    return;
  }
  
  // Campos TrustedForm a mover
  const trustedFormFields = [
    'form-field-trustedform_1',
    'xxTrustedFormToken_1', 
    'xxTrustedFormPingUrl_1'
  ];
  
  let movedCount = 0;
  let foundFields = 0;
  
  trustedFormFields.forEach(function(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
      foundFields++;
      console.log('✅ Campo TrustedForm encontrado:', fieldId, '=', field.value.substring(0, 50) + '...');
      
      // Verificar si ya fue movido
      if (field.name.includes('form_fields[')) {
        console.log('⏭️ Campo ya movido:', fieldId);
        return;
      }
      
      // Crear un div wrapper estilo Elementor para el campo
      const fieldWrapper = document.createElement('div');
      fieldWrapper.className = 'elementor-field-type-hidden elementor-field-group elementor-column elementor-col-100';
      
      // Actualizar el name del campo para que Elementor lo procese
      const originalName = field.name;
      let newName = '';
      
      if (fieldId.includes('trustedform')) {
        newName = 'form_fields[trustedform_cert]';
      } else if (fieldId.includes('Token')) {
        newName = 'form_fields[trustedform_token]';
      } else if (fieldId.includes('PingUrl')) {
        newName = 'form_fields[trustedform_ping]';
      }
      
      if (newName) {
        field.name = newName;
        field.className = 'elementor-field elementor-size-sm elementor-field-textual';
        
        // Mover el campo al wrapper
        fieldWrapper.appendChild(field);
        wrapper.appendChild(fieldWrapper);
        
        movedCount++;
        console.log('🚀 Campo movido:', fieldId, '→', newName);
      }
    } else {
      console.log('❌ Campo TrustedForm no encontrado:', fieldId);
    }
  });
  
  console.log('📊 Resumen TrustedForm: ' + foundFields + ' encontrados, ' + movedCount + ' movidos');
  
  if (foundFields === 0) {
    console.log('🔄 TrustedForm aún no cargado, reintentando en 2 segundos...');
    setTimeout(moveTrustedFormFieldsToWrapper, 2000);
  }
}