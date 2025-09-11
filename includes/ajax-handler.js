/**
 * Mimer Forms VDI - AJAX Handler Override
 * Maneja la respuesta especial cuando el formulario se procesa vía hook
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Interceptar respuestas AJAX de Elementor
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Solo procesar si es admin-ajax.php
        if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
            try {
                var response = JSON.parse(xhr.responseText);
                
                // Si es nuestra respuesta especial, simular éxito
                if (response && response.data && response.data.mimer_processed) {
                    console.log('🔄 Mimer Forms: Formulario procesado vía hook de validación');
                    
                    // Encontrar el formulario y simular éxito
                    var $form = $('form[id*="roundup"], form[id*="depo"]');
                    if ($form.length > 0) {
                        // Ocultar mensajes de error si existen
                        $form.find('.elementor-message-danger').remove();
                        
                        // Mostrar mensaje de éxito temporal
                        var $successMsg = $('<div class="elementor-message elementor-message-success" style="display: block;">Form submitted successfully!</div>');
                        $form.prepend($successMsg);
                        
                        // Ocultar el mensaje después de 3 segundos
                        setTimeout(function() {
                            $successMsg.fadeOut();
                        }, 3000);
                        
                        console.log('✅ Mimer Forms: Mensaje de éxito mostrado');
                    }
                }
            } catch (e) {
                // Ignorar errores de parsing - no es nuestra respuesta
            }
        }
    });
    
    // También interceptar errores AJAX
    $(document).ajaxError(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response && response.data && response.data.mimer_processed) {
                    // Prevenir que se muestre el error al usuario
                    event.stopImmediatePropagation();
                    console.log('🛑 Mimer Forms: Error AJAX interceptado y manejado');
                }
            } catch (e) {
                // Ignorar errores de parsing
            }
        }
    });
    
    console.log('✅ Mimer Forms AJAX Handler cargado');
});
