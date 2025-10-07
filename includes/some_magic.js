/**
 * Mimer Forms VDI - Utility Scripts
 * Scripts adicionales para formularios (sin validaciones)
 * Las validaciones están en form-validation.js
 */

// Esperar a que TODO esté completamente cargado (incluyendo scripts externos)
window.addEventListener('load', function() {
  // 📝 MOVER CAMPOS TRUSTEDFORM DENTRO DEL WRAPPER PARA QUE ELEMENTOR LOS GUARDE
  moveTrustedFormFieldsToWrapper();
});

// Backup con DOMContentLoaded por si 'load' no funciona
document.addEventListener('DOMContentLoaded', function () {
  // Esperar un poco más y hacer un intento adicional
  setTimeout(function() {
    moveTrustedFormFieldsToWrapper();
  }, 2000);
});

/**
 * Mueve los campos TrustedForm generados dinámicamente dentro del wrapper de Elementor
 * para que se guarden automáticamente en las submissions
 */
function moveTrustedFormFieldsToWrapper() {
  const form = document.querySelector('#dp_form');
  if (!form) return;
  
  const wrapper = form.querySelector('.elementor-form-fields-wrapper');
  if (!wrapper) return;
  
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
      
      // Verificar si ya fue movido
      if (field.name.includes('form_fields[')) {
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
      }
    }
  });
  
  // Reintentar si no se encontraron campos
  if (foundFields === 0) {
    setTimeout(moveTrustedFormFieldsToWrapper, 2000);
  }
}