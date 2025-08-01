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
        return;
      }

      // --- AJAX SUBMIT Y REDIRECCIÓN ---
      e.preventDefault();

      const formData = new FormData(form);

      fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        console.log('Respuesta AJAX:', data); // <-- Añade esto
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      })
      .catch(() => {
        // Si hay error, redirige a la página local de gracias
        window.location.href = 'https://injuryresolve.com/dp-thankyou/';
      });
    });
  });
});