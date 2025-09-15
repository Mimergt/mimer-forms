<script>
  jQuery(document).ready(function ($) {
    // ✅ SOPORTE PARA MÚLTIPLES FORMULARIOS: dp_form Y dp_formv2
    const FORM_SELECTORS = '#dp_form, #dp_formv2';
    
    // Función de inicio del formulario
    $('#inicio_form').on('click', function (e) {
      e.preventDefault(); // Previene comportamiento por defecto si fuera <a> o <button type="submit">

      // Ocultar #inicio
      $('#inicio').hide();

      // Mostrar #the_form
      $('#the_form').show();
    });
    
    $('#siguiente-form1').on('click', function (e) {
      e.preventDefault(); // Previene comportamiento por defecto si fuera <a> o <button type="submit">

      // Ocultar #disclaimer
      $('#disclaimer').hide();
    });

    console.log('Script cargado - jQuery ready (Multi-Form Support)'); // DEBUG
    
    // ✅ RADIOS DE case_depo_provera_taken - SOPORTE MULTI-FORM
    $(FORM_SELECTORS).on(
      'change',
      'input[type="radio"][name="form_fields[case_depo_provera_taken]"]',
      function () {
        console.log('Change detectado en radio:', this.id, 'checked:', this.checked, 'form:', $(this).closest('form').attr('id')); // DEBUG
        if (
          this.checked &&
          (this.id === 'form-field-case_depo_provera_taken-0' ||
            this.id === 'form-field-case_depo_provera_taken-2')
        ) {
          console.log('Radio válido, avanzando manualmente...'); // DEBUG
          const $step = $(this).closest('.e-form__step');
          const $form = $(this).closest('form');
          const $currentStepElement = $step;
          const $nextStepElement = $step.next('.e-form__step');
          if ($nextStepElement.length) {
            // Obtener números de paso
            const currentStepIndex = $form.find('.e-form__step').index($currentStepElement);
            const nextStepIndex = currentStepIndex + 1;
            // Ocultar paso actual
            $currentStepElement.addClass('elementor-hidden');
            // Mostrar siguiente paso
            $nextStepElement.removeClass('elementor-hidden');
            // Actualizar el indicador de progreso
            const $progressBarAlt2 = $form.find('.e-form__indicators');
            if ($progressBarAlt2.length) {
              // Actualizar la barra de progreso visual
              const $progressMeter = $progressBarAlt2.find('.e-form__indicators__indicator__progress__meter');
              if ($progressMeter.length) {
                const totalSteps = $form.find('.e-form__step').length;
                const progressPercentage = ((nextStepIndex + 1) / totalSteps) * 100;
                $progressMeter.css('width', progressPercentage + '%');
                console.log('Barra de progreso actualizada para radios case_depo_provera_taken:', progressPercentage + '%'); // DEBUG
              }
            }
            // Trigger eventos de Elementor si existen
            try {
              $form.trigger('step_changed', [nextStepIndex]);
              $nextStepElement.trigger('step_activated');
            } catch(eventError) {
              console.log('Error al activar eventos:', eventError); // DEBUG
            }
            console.log('Paso cambiado manualmente para radios case_depo_provera_taken de', currentStepIndex, 'a', nextStepIndex); // DEBUG
          } else {
            console.log('No hay siguiente paso para radios case_depo_provera_taken'); // DEBUG
          }
        }
      }
    );

    // ✅ AVANZAR AUTOMÁTICAMENTE PARA TODOS LOS OTROS CAMPOS DE RADIO - SOPORTE MULTI-FORM
    const otherRadioFields = [
      'case_depo_provera_use',
      'case_depo_provera_ba03', 
      'case_injury',
      'case_attorney'
    ];
    
    otherRadioFields.forEach(function(fieldName) {
      $(FORM_SELECTORS).on(
        'change',
        'input[type="radio"][name="form_fields[' + fieldName + ']"]',
        function () {
          console.log('Change detectado en radio ' + fieldName + ':', this.id, 'checked:', this.checked, 'form:', $(this).closest('form').attr('id')); // DEBUG
          if (this.checked) {
            console.log('Radio válido para ' + fieldName + ', buscando botón next...'); // DEBUG
            const $step = $(this).closest('.e-form__step');
            const $form = $(this).closest('form');
            
            // Intentar múltiples métodos para avanzar el formulario
            const $nextBtn = $step.find(
              'button[type="button"][data-direction="next"], .e-form__buttons__wrapper__button-next').first();
            
            if ($nextBtn.length) {
              console.log('Botón Next encontrado para ' + fieldName + ', probando método alternativo...'); // DEBUG
              
              // Método 1: Intentar usar la API de Elementor directamente
              try {
                const $currentStepElement = $step;
                const $nextStepElement = $step.next('.e-form__step');
                
                if ($nextStepElement.length) {
                  console.log('Siguiente paso encontrado, cambiando manualmente...'); // DEBUG
                  
                  // Obtener números de paso
                  const currentStepIndex = $form.find('.e-form__step').index($currentStepElement);
                  const nextStepIndex = currentStepIndex + 1;
                  
                  // Ocultar paso actual
                  $currentStepElement.addClass('elementor-hidden');
                  // Mostrar siguiente paso
                  $nextStepElement.removeClass('elementor-hidden');
                  
                  // Actualizar el indicador de progreso
                  const $progressBar = $form.find('.e-form__indicators__indicator');
                  const $progressBarAlt = $form.find('.elementor-progress-bar');
                  const $progressBarAlt2 = $form.find('.e-form__indicators');
                  const $allProgressElements = $form.find('[class*="progress"], [class*="indicator"], [class*="step"]');
                  
                  console.log('Elementos de progreso encontrados:', {
                    'e-form__indicators__indicator': $progressBar.length,
                    'elementor-progress-bar': $progressBarAlt.length, 
                    'e-form__indicators': $progressBarAlt2.length,
                    'all progress elements': $allProgressElements.length
                  }); // DEBUG
                  
                  if ($allProgressElements.length > 0) {
                    console.log('Elementos encontrados:', $allProgressElements.map((i, el) => el.className).get()); // DEBUG
                  }
                  
                  if ($progressBar.length) {
                    // Marcar el paso actual como completado
                    $progressBar.eq(currentStepIndex).addClass('e-form__indicators__indicator--completed');
                    // Marcar el siguiente paso como activo
                    $progressBar.removeClass('e-form__indicators__indicator--state-current');
                    $progressBar.eq(nextStepIndex).addClass('e-form__indicators__indicator--state-current');
                    console.log('Indicador de progreso actualizado para ' + fieldName); // DEBUG
                  } else if ($progressBarAlt2.length) {
                    // Intentar con estructura alternativa
                    const $indicators = $progressBarAlt2.find('*[class*="indicator"]');
                    if ($indicators.length) {
                      $indicators.eq(currentStepIndex).addClass('completed');
                      $indicators.removeClass('current active');
                      $indicators.eq(nextStepIndex).addClass('current active');
                      console.log('Indicador de progreso alternativo actualizado para ' + fieldName); // DEBUG
                    }
                    
                    // Actualizar la barra de progreso visual
                    const $progressMeter = $progressBarAlt2.find('.e-form__indicators__indicator__progress__meter');
                    if ($progressMeter.length) {
                      const totalSteps = $form.find('.e-form__step').length;
                      const progressPercentage = ((nextStepIndex + 1) / totalSteps) * 100;
                      $progressMeter.css('width', progressPercentage + '%');
                      console.log('Barra de progreso actualizada para ' + fieldName + ':', progressPercentage + '%'); // DEBUG
                    }
                    
                    // Actualizar cualquier elemento de porcentaje
                    const $progressText = $progressBarAlt2.find('.e-form__indicators__indicator__progress__text, [class*="percentage"]');
                    if ($progressText.length) {
                      const totalSteps = $form.find('.e-form__step').length;
                      const progressPercentage = Math.round(((nextStepIndex + 1) / totalSteps) * 100);
                      $progressText.text(progressPercentage + '%');
                      console.log('Texto de progreso actualizado para ' + fieldName + ':', progressPercentage + '%'); // DEBUG
                    }
                  }
                  
                  // Actualizar cualquier contador numérico
                  const $counter = $form.find('.e-form__indicators__progress__number');
                  const $counterAlt = $form.find('[class*="number"], [class*="count"]');
                  if ($counter.length) {
                    $counter.text(nextStepIndex + 1);
                    console.log('Contador numérico actualizado para ' + fieldName); // DEBUG
                  } else if ($counterAlt.length) {
                    $counterAlt.text(nextStepIndex + 1);
                    console.log('Contador alternativo actualizado para ' + fieldName); // DEBUG
                  }
                  
                  // Trigger eventos de Elementor si existen
                  try {
                    $form.trigger('step_changed', [nextStepIndex]);
                    $nextStepElement.trigger('step_activated');
                  } catch(eventError) {
                    console.log('Error al activar eventos:', eventError); // DEBUG
                  }
                  
                  console.log('Paso cambiado manualmente para ' + fieldName + ' de', currentStepIndex, 'a', nextStepIndex); // DEBUG
                } else {
                  console.log('No hay siguiente paso para ' + fieldName); // DEBUG
                }
              } catch(e) {
                console.log('Error en método de cambio manual para ' + fieldName + ':', e); // DEBUG
              }
              
            } else {
              console.log('No se encontró botón Next para ' + fieldName); // DEBUG
            }
          }
        }
      );
    });

    // ✅ DEBUG: Escuchar clicks en radios - SOPORTE MULTI-FORM
    $(FORM_SELECTORS).on('click', 'input[type="radio"]', function() {
      console.log('Click directo en radio:', this.id, 'checked:', this.checked, 'form:', $(this).closest('form').attr('id')); // DEBUG
    });

    // ✅ DEBUG: Escuchar clicks en labels - SOPORTE MULTI-FORM
    $(FORM_SELECTORS).on('click', 'label', function() {
      console.log('Click en label:', this, 'for:', $(this).attr('for'), 'form:', $(this).closest('form').attr('id')); // DEBUG
    });

    // ✅ DEBUG: Escuchar clicks en CUALQUIER elemento dentro del formulario - SOPORTE MULTI-FORM
    $(FORM_SELECTORS).on('click', '*', function(e) {
      console.log('Click en elemento:', this.tagName, this.id, this.className, 'form:', $(this).closest('form').attr('id')); // DEBUG
    });

    // ✅ DEBUG: Verificar si existen los radios - SOPORTE MULTI-FORM
    console.log('Radios dp_form encontrados:', $('#dp_form input[type="radio"][name="form_fields[case_depo_provera_taken]"]').length);
    console.log('Radios dp_formv2 encontrados:', $('#dp_formv2 input[type="radio"][name="form_fields[case_depo_provera_taken]"]').length);
    console.log('Total radios encontrados:', $(FORM_SELECTORS + ' input[type="radio"][name="form_fields[case_depo_provera_taken]"]').length);

    // ✅ BOTONES PERSONALIZADOS - SOPORTE MULTI-FORM
    $(document).on('click', '#siguiente-form, #siguiente-form1', function (e) {
      e.preventDefault();
      console.log('Click en botón personalizado:', this.id); // DEBUG
      
      // Buscar el paso actual visible en CUALQUIERA de los formularios
      var $currentStep = $(FORM_SELECTORS).find('.e-form__step').filter(function() {
        return !$(this).hasClass('elementor-hidden') && $(this).css('display') !== 'none';
      }).first();
      
      console.log('Paso actual encontrado:', $currentStep.length, $currentStep[0], 'form:', $currentStep.closest('form').attr('id')); // DEBUG
      
      // Busca el botón "Siguiente" real dentro del paso actual
      var $nextBtn = $currentStep.find(
        'button[type="button"][data-direction="next"], .e-form__buttons__wrapper__button-next').first();
      if ($nextBtn.length) {
        console.log('Botón Next encontrado para botón personalizado, haciendo click...'); // DEBUG
        console.log('Botón Next detalles:', $nextBtn[0], 'visible:', $nextBtn.is(':visible'), 'disabled:', $nextBtn.prop('disabled')); // DEBUG
        
        // Usar el mismo método manual que funciona para los radios
        const $currentStepElement = $currentStep;
        const $nextStepElement = $currentStep.next('.e-form__step');
        const $form = $currentStep.closest('form');
        
        if ($nextStepElement.length) {
          console.log('Siguiente paso encontrado para botón personalizado, cambiando manualmente...'); // DEBUG
          
          // Obtener números de paso
          const currentStepIndex = $form.find('.e-form__step').index($currentStepElement);
          const nextStepIndex = currentStepIndex + 1;
          
          // Ocultar paso actual
          $currentStepElement.addClass('elementor-hidden');
          // Mostrar siguiente paso
          $nextStepElement.removeClass('elementor-hidden');
          
          // Actualizar el indicador de progreso
          const $progressBarAlt2 = $form.find('.e-form__indicators');
          
          if ($progressBarAlt2.length) {
            // Actualizar la barra de progreso visual
            const $progressMeter = $progressBarAlt2.find('.e-form__indicators__indicator__progress__meter');
            if ($progressMeter.length) {
              const totalSteps = $form.find('.e-form__step').length;
              const progressPercentage = ((nextStepIndex + 1) / totalSteps) * 100;
              $progressMeter.css('width', progressPercentage + '%');
              console.log('Barra de progreso actualizada para botón personalizado:', progressPercentage + '%'); // DEBUG
            }
          }
          
          console.log('Paso cambiado manualmente para botón personalizado de', currentStepIndex, 'a', nextStepIndex); // DEBUG
        } else {
          console.log('No hay siguiente paso para botón personalizado'); // DEBUG
        }
        
      } else {
        console.log('No se encontró botón Next para botón personalizado'); // DEBUG
      }
    });
	
    // ✅ FUNCIÓN GENÉRICA PARA SELECTS - SOPORTE MULTI-FORM
    function avanzarSiTieneValor(selector) {
      $(FORM_SELECTORS).on('change', selector, function () {
        console.log('Change detectado en select:', selector, 'value:', $(this).val(), 'form:', $(this).closest('form').attr('id')); // DEBUG
        if ($(this).val()) {
          const $step = $(this).closest('.e-form__step');
          const $nextBtn = $step.find(
            'button[type="button"][data-direction="next"], .e-form__buttons__wrapper__button-next').first();
          if ($nextBtn.length) {
            console.log('Avanzando por select:', selector); // DEBUG
            $nextBtn.trigger('click');
          }
        }
      });
    }

    // ✅ APLICAR SOLO PARA EL SELECT DE other_injections - SOPORTE MULTI-FORM
    avanzarSiTieneValor('#form-field-other_injections');
    
    // ✅ LOG INICIAL PARA VERIFICAR FORMULARIOS DISPONIBLES
    console.log('=== MIMER MULTI-FORM HANDLER INITIALIZED ===');
    console.log('Formularios encontrados:');
    console.log('- dp_form:', $('#dp_form').length > 0 ? '✅ Encontrado' : '❌ No encontrado');
    console.log('- dp_formv2:', $('#dp_formv2').length > 0 ? '✅ Encontrado' : '❌ No encontrado');
    console.log('Total de formularios soportados:', $(FORM_SELECTORS).length);
  });
</script>