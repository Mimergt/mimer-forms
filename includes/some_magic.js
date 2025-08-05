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

    // Agregar listeners para radio buttons para limpiar errores
    form.querySelectorAll('input[type="radio"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        // Limpiar error del grupo cuando se selecciona una opción
        const container = radio.closest('.elementor-field-group');
        if (container) {
          let existingError = container.querySelector('.elementor-message');
          if (existingError) existingError.remove();
        }
      });
    });

    form.addEventListener('submit', function(e) {
      let isValid = true;
      
      // Validación para radio buttons
      const radioGroups = {};
      form.querySelectorAll('input[type="radio"][required]').forEach(function(radio) {
        const name = radio.name;
        if (!radioGroups[name]) {
          radioGroups[name] = {
            radios: form.querySelectorAll('input[type="radio"][name="' + name + '"]'),
            isChecked: false,
            container: radio.closest('.elementor-field-group')
          };
        }
      });
      
      // Verificar cada grupo de radio buttons
      Object.keys(radioGroups).forEach(function(groupName) {
        const group = radioGroups[groupName];
        group.isChecked = Array.from(group.radios).some(radio => radio.checked);
        
        if (!group.isChecked) {
          // Eliminar error previo
          let existingError = group.container.querySelector('.elementor-message');
          if (existingError) existingError.remove();
          
          // Crear mensaje de error
          const errorMessage = document.createElement('div');
          errorMessage.className = 'elementor-message elementor-message-danger';
          errorMessage.textContent = 'Please select one option';
          group.container.appendChild(errorMessage);
          
          isValid = false;
        } else {
          // Eliminar error si existe y hay selección
          let existingError = group.container.querySelector('.elementor-message');
          if (existingError) existingError.remove();
        }
      });
      
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