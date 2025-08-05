document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('form');

  forms.forEach(function(form) {
    // Personalizar mensajes de validación HTML5 en inglés
    const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    requiredFields.forEach(function(field) {
      // Establecer mensaje personalizado en inglés
      field.addEventListener('invalid', function(e) {
        if (field.validity.valueMissing) {
          if (field.tagName.toLowerCase() === 'select') {
            field.setCustomValidity('Please select an option');
          } else {
            field.setCustomValidity('Please complete this field');
          }
        } else {
          field.setCustomValidity('');
        }
      });
      
      // Limpiar mensaje cuando el campo se llena
      field.addEventListener('input', function() {
        field.setCustomValidity('');
      });
      
      field.addEventListener('change', function() {
        field.setCustomValidity('');
      });
    });

    form.addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validación adicional para selects con valor por defecto
      form.querySelectorAll('select[required]').forEach(function(select) {
        if (!select.value || select.value === '--select--' || select.value === '') {
          // Eliminar cualquier error previo
          let existingError = select.parentElement.querySelector('.elementor-message');
          if (existingError) existingError.remove();

          // Crear mensaje de error visual
          const errorMessage = document.createElement('div');
          errorMessage.className = 'elementor-message elementor-message-danger';
          errorMessage.textContent = 'Please select a valid option';
          select.parentElement.appendChild(errorMessage);

          isValid = false;
        }
      });

      if (!isValid) {
        e.preventDefault();
        return;
      }

      // --- ENVÍO NORMAL DEL FORMULARIO ---
      // Elimina el e.preventDefault() y el AJAX para permitir el submit tradicional
      // El formulario se enviará y la redirección la hará PHP
    });
  });
});