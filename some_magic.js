

document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('form');

  forms.forEach(function(form) {
    form.addEventListener('submit', function(e) {
      let isValid = true;
      form.querySelectorAll('select[required]').forEach(function(select) {
        if (!select.value || select.value === '--select--') {
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
      }
    });
  });
});