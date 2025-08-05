/**
 * Mimer Forms VDI - Form Validation
 * Validaciones de formulario en inglés para Elementor Forms
 * Versión: 1.2 - Enhanced debugging
 */

(function() {
    'use strict';
    
    console.log('🚀 NUEVA VERSION 1.9 - Fixed select wrapper layout issues!');
    
    // Configuración de mensajes de validación
    const VALIDATION_MESSAGES = {
        RADIO_REQUIRED: 'Please select one option',
        SELECT_REQUIRED: 'Please select a valid option',
        FIELD_REQUIRED: 'Please complete this field',
        SELECT_PLACEHOLDER: 'Please select an option'
    };
    
    // Selectores de elementos
    const SELECTORS = {
        FORM: 'form',
        REQUIRED_FIELDS: 'input[required], select[required], textarea[required]',
        RADIO_REQUIRED: 'input[type="radio"][required]',
        SELECT_REQUIRED: 'select[required]',
        RADIO_ALL: 'input[type="radio"]',
        ERROR_MESSAGE: '.elementor-message',
        FIELD_GROUP: '.elementor-field-group'
    };
    
    // Clases CSS para mensajes de error
    const ERROR_CLASSES = 'elementor-message elementor-message-danger';
    
    /**
     * Personalizar mensajes HTML5 en inglés
     */
    function customizeHTML5Messages(form) {
        const requiredFields = form.querySelectorAll(SELECTORS.REQUIRED_FIELDS);
        
        requiredFields.forEach(function(field) {
            // Establecer mensaje personalizado en inglés
            field.addEventListener('invalid', function(e) {
                if (field.validity.valueMissing) {
                    if (field.tagName.toLowerCase() === 'select') {
                        field.setCustomValidity(VALIDATION_MESSAGES.SELECT_PLACEHOLDER);
                    } else {
                        field.setCustomValidity(VALIDATION_MESSAGES.FIELD_REQUIRED);
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
    }
    
    /**
     * Crear mensaje de error visual
     */
    function createErrorMessage(text) {
        const errorMessage = document.createElement('div');
        errorMessage.className = ERROR_CLASSES;
        
        // Crear el icono usando emoji simple
        const iconSpan = document.createElement('span');
        iconSpan.style.cssText = 'display: inline-block; margin-right: 5px; color: #d72651; font-weight: bold;';
        iconSpan.textContent = '⚠️'; // Emoji directo, sin fallbacks
        
        errorMessage.appendChild(iconSpan);
        errorMessage.appendChild(document.createTextNode(text));
        
        return errorMessage;
    }
    
    /**
     * Remover mensajes de error existentes
     */
    function removeExistingError(container) {
        // Buscar en el container y también en sus elementos hermanos
        const existingError = container.querySelector(SELECTORS.ERROR_MESSAGE);
        if (existingError) {
            existingError.remove();
            return;
        }
        
        // También buscar errores que puedan estar como hermanos del container
        const siblingError = container.parentElement ? container.parentElement.querySelector(SELECTORS.ERROR_MESSAGE) : null;
        if (siblingError) {
            // Verificar que el error esté relacionado con este container
            const previousElement = siblingError.previousElementSibling;
            if (previousElement && (previousElement === container || previousElement.contains(container))) {
                siblingError.remove();
            }
        }
    }
    
    /**
     * Validar grupos de radio buttons
     */
    function validateRadioGroups(form) {
        const radioGroups = {};
        let isValid = true;
        
        // Agrupar radio buttons por name
        const radioButtons = form.querySelectorAll(SELECTORS.RADIO_REQUIRED);
        console.log('🔘 Radio buttons encontrados:', radioButtons.length);
        
        radioButtons.forEach(function(radio) {
            const name = radio.name;
            console.log('📝 Procesando radio:', name);
            if (!radioGroups[name]) {
                radioGroups[name] = {
                    radios: form.querySelectorAll('input[type="radio"][name="' + name + '"]'),
                    isChecked: false,
                    container: radio.closest(SELECTORS.FIELD_GROUP)
                };
                console.log('📦 Grupo creado para:', name, 'Radios en grupo:', radioGroups[name].radios.length);
            }
        });
        
        // Verificar cada grupo
        Object.keys(radioGroups).forEach(function(groupName) {
            const group = radioGroups[groupName];
            group.isChecked = Array.from(group.radios).some(radio => radio.checked);
            console.log('🔍 Grupo ' + groupName + ' - Seleccionado:', group.isChecked);
            
            if (!group.isChecked) {
                console.log('❌ Agregando error para:', groupName);
                removeExistingError(group.container);
                const errorMessage = createErrorMessage(VALIDATION_MESSAGES.RADIO_REQUIRED);
                group.container.appendChild(errorMessage);
                isValid = false;
            } else {
                console.log('✅ Limpiando error para:', groupName);
                removeExistingError(group.container);
            }
        });
        
        console.log('🔘 Validación radio resultado:', isValid);
        return isValid;
    }
    
    /**
     * Validar select fields
     */
    function validateSelectFields(form) {
        let isValid = true;
        
        form.querySelectorAll(SELECTORS.SELECT_REQUIRED).forEach(function(select) {
            console.log('🔍 Validando select:', select.name, 'valor actual:', `"${select.value}"`, 'selectedIndex:', select.selectedIndex);
            
            if (!select.value || select.value === '--select--' || select.value === '') {
                console.log('❌ Select inválido:', select.name, 'razón: valor vacío o placeholder');
                
                // Buscar el contenedor correcto para el mensaje de error
                const selectWrapper = select.closest('.elementor-select-wrapper');
                const fieldGroup = select.closest('.elementor-field-group');
                
                // Determinar dónde poner el mensaje - después del wrapper, no dentro
                const errorContainer = selectWrapper ? selectWrapper.parentElement : (fieldGroup || select.parentElement);
                
                console.log('📍 Select error - wrapper:', selectWrapper ? 'encontrado' : 'no encontrado');
                console.log('📍 Error container será:', errorContainer.className || 'sin clase');
                
                removeExistingError(errorContainer);
                const errorMessage = createErrorMessage(VALIDATION_MESSAGES.SELECT_REQUIRED);
                
                // Insertar después del wrapper, no dentro
                if (selectWrapper) {
                    selectWrapper.insertAdjacentElement('afterend', errorMessage);
                } else {
                    errorContainer.appendChild(errorMessage);
                }
                
                isValid = false;
            } else {
                console.log('✅ Select válido:', select.name, 'valor:', `"${select.value}"`);
                
                // Limpiar cualquier error existente si el select es válido
                const selectWrapper = select.closest('.elementor-select-wrapper');
                const fieldGroup = select.closest('.elementor-field-group');
                const errorContainer = selectWrapper ? selectWrapper.parentElement : (fieldGroup || select.parentElement);
                removeExistingError(errorContainer);
            }
        });
        
        return isValid;
    }
    
    /**
     * Validar textarea fields
     */
    function validateTextareaFields(form) {
        let isValid = true;
        
        form.querySelectorAll('textarea[required]').forEach(function(textarea) {
            console.log('🔍 Validando textarea:', textarea.name, 'valor actual:', `"${textarea.value.trim()}"`, 'length:', textarea.value.trim().length);
            
            if (!textarea.value.trim() || textarea.value.trim() === '') {
                console.log('❌ Textarea inválido:', textarea.name, 'razón: valor vacío');
                
                // Buscar el contenedor correcto para el mensaje de error
                const fieldGroup = textarea.closest('.elementor-field-group');
                const errorContainer = fieldGroup || textarea.parentElement;
                
                console.log('📍 Textarea error container será:', errorContainer.className || 'sin clase');
                
                removeExistingError(errorContainer);
                const errorMessage = createErrorMessage(VALIDATION_MESSAGES.FIELD_REQUIRED);
                
                errorContainer.appendChild(errorMessage);
                isValid = false;
            } else {
                console.log('✅ Textarea válido:', textarea.name, 'caracteres:', textarea.value.trim().length);
                
                // Limpiar cualquier error existente si el textarea es válido
                const fieldGroup = textarea.closest('.elementor-field-group');
                const errorContainer = fieldGroup || textarea.parentElement;
                removeExistingError(errorContainer);
            }
        });
        
        return isValid;
    }
    
    /**
     * Setup event listeners para limpiar errores automáticamente
     */
    function setupErrorCleanup(form) {
        // Radio buttons
        form.querySelectorAll(SELECTORS.RADIO_ALL).forEach(function(radio) {
            radio.addEventListener('change', function() {
                const container = radio.closest(SELECTORS.FIELD_GROUP);
                if (container) {
                    removeExistingError(container);
                }
            });
        });
        
        // Select fields - limpiar errores cuando se selecciona una opción válida
        form.querySelectorAll('select').forEach(function(select) {
            select.addEventListener('change', function() {
                console.log('🔄 Select cambió:', select.name, 'valor:', select.value);
                
                // Si se selecciona una opción válida, limpiar error
                if (select.value && select.value !== '--select--' && select.value !== '') {
                    console.log('✅ Valor válido seleccionado, limpiando error para:', select.name);
                    
                    // Buscar el contenedor donde se mostró el error
                    const selectWrapper = select.closest('.elementor-select-wrapper');
                    const fieldGroup = select.closest('.elementor-field-group');
                    const errorContainer = selectWrapper ? selectWrapper.parentElement : (fieldGroup || select.parentElement);
                    
                    removeExistingError(errorContainer);
                } else {
                    console.log('⚠️ Valor no válido seleccionado:', select.value);
                }
            });
        });
        
        // Textarea fields - limpiar errores cuando el usuario escriba contenido válido
        form.querySelectorAll('textarea[required]').forEach(function(textarea) {
            textarea.addEventListener('input', function() {
                console.log('✏️ Textarea cambió:', textarea.name, 'caracteres:', textarea.value.trim().length);
                
                // Si hay contenido válido, limpiar error
                if (textarea.value.trim().length > 0) {
                    console.log('✅ Contenido válido en textarea, limpiando error para:', textarea.name);
                    
                    const fieldGroup = textarea.closest('.elementor-field-group');
                    const errorContainer = fieldGroup || textarea.parentElement;
                    removeExistingError(errorContainer);
                } else {
                    console.log('⚠️ Textarea aún vacío:', textarea.name);
                }
            });
        });
    }
    
    /**
     * Inicializar validación para un formulario
     */
    function initFormValidation(form) {
        console.log('🔧 Configurando validación para formulario:', form);
        console.log('📝 Formulario ID:', form.id);
        console.log('📝 Formulario clases:', form.className);
        
        // Personalizar mensajes HTML5
        customizeHTML5Messages(form);
        
        // Setup limpieza automática de errores
        setupErrorCleanup(form);
        
        // Múltiples formas de capturar el submit para asegurar que funcione con Elementor
        
        // Método 1: addEventListener normal
        form.addEventListener('submit', function(e) {
            console.log('📤 Submit Method 1 - addEventListener detectado!');
            return handleFormSubmit(e, form);
        });
        
        // Método 2: onsubmit property (backup)
        const originalOnSubmit = form.onsubmit;
        form.onsubmit = function(e) {
            console.log('📤 Submit Method 2 - onsubmit detectado!');
            const result = handleFormSubmit(e, form);
            if (originalOnSubmit && result) {
                return originalOnSubmit.call(this, e);
            }
            return result;
        };
        
        // Método 3: Intercept button clicks
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        console.log('🔍 Submit buttons encontrados:', submitButtons.length);
        
        submitButtons.forEach(function(button, index) {
            console.log('🔘 Button #' + (index + 1) + ':', button.id || 'sin-id', button.className || 'sin-class');
            
            button.addEventListener('click', function(e) {
                console.log('📤 Submit Method 3 - Button click detectado!', button);
                
                // Para Elementor, interceptamos el click y validamos ANTES del submit
                console.log('🔍 Validando en button click...');
                if (!validateForm(form)) {
                    console.log('🛑 Validación falló en button click - Cancelando');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                } else {
                    console.log('✅ Validación OK en button click - Permitiendo submit');
                }
            });
            
            // También agregar listener con capture = true para asegurar que se ejecute primero
            button.addEventListener('click', function(e) {
                console.log('📤 Submit Method 3B - Button click CAPTURE detectado!');
            }, true);
        });
        
        // También buscar cualquier elemento que pueda actuar como botón de submit
        const allButtons = form.querySelectorAll('button, [type="submit"], .elementor-button');
        console.log('🔍 Todos los botones encontrados:', allButtons.length);
        allButtons.forEach(function(btn, i) {
            console.log('  Button #' + (i+1) + ':', btn.tagName, btn.type || 'sin-type', btn.id || 'sin-id');
        });
        
        // Método 4: Hook específico para Elementor Pro Forms (con verificación segura)
        if (window.elementorProFrontend && window.elementorProFrontend.hooks && window.elementorProFrontend.hooks.addAction) {
            console.log('🎯 Elementor Pro detectado - Agregando hooks');
            try {
                window.elementorProFrontend.hooks.addAction('panel/open_editor/widget/form', function() {
                    console.log('📝 Elementor form hook activado');
                });
            } catch (error) {
                console.log('⚠️ Error en Elementor hook:', error.message);
            }
        } else {
            console.log('ℹ️ Elementor Pro hooks no disponibles - usando otros métodos');
        }
        
        // Método 5: Intercept usando jQuery (si está disponible)
        if (window.jQuery) {
            window.jQuery(form).on('submit', function(e) {
                console.log('📤 Submit Method 5 - jQuery submit detectado!');
                return handleFormSubmit(e.originalEvent || e, form);
            });
        }
        
        console.log('✅ Validación configurada para formulario');
    }
    
    /**
     * Validar formulario completo
     */
    function validateForm(form) {
        console.log('🔘 Validando radio buttons...');
        const radioValid = validateRadioGroups(form);
        
        console.log('🔽 Validando selects...');
        const selectValid = validateSelectFields(form);
        
        console.log('📝 Validando textareas...');
        const textareaValid = validateTextareaFields(form);
        
        const isValid = radioValid && selectValid && textareaValid;
        console.log('📊 Resultado validación total:', isValid ? '✅ VÁLIDO' : '❌ INVÁLIDO');
        
        return isValid;
    }
    
    /**
     * Manejar el submit del formulario
     */
    function handleFormSubmit(e, form) {
        console.log('📤 Submit detectado! Iniciando validación...');
        
        if (!validateForm(form)) {
            console.log('🛑 Validación falló - Cancelando envío');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
        
        console.log('🚀 Validación pasó - Permitiendo envío');
        return true;
    }
    
    /**
     * Asegurar que los estilos de iconos estén disponibles
     */
    function ensureIconStyles() {
        // Verificar si ya existe un estilo para los iconos
        if (document.getElementById('mimer-validation-icons')) {
            return;
        }
        
        const style = document.createElement('style');
        style.id = 'mimer-validation-icons';
        style.textContent = `
            /* Estilos para mensajes de error - sin :before, solo emoji del span */
            .elementor-message.elementor-message-danger:before {
                content: "" !important;
                display: none !important;
            }
            
            .elementor-message.elementor-message-danger {
                color: #d72651 !important;
                padding: 8px 12px !important;
                margin-top: 5px !important;
                border-left: 3px solid #d72651 !important;
                background-color: rgba(215, 38, 81, 0.1) !important;
                border-radius: 3px !important;
                width: 100% !important;
                box-sizing: border-box !important;
                display: block !important;
            }
            
            /* Asegurar que los field groups con selects tengan layout vertical */
            .elementor-field-group:has(.elementor-select-wrapper + .elementor-message) {
            display:flex;    
            /*flex-direction: column !important;*/
            }
            
            /* Alternativa más compatible para navegadores que no soportan :has() */
            .elementor-field-group .elementor-select-wrapper + .elementor-message {
                width: 100% !important;
                margin-top: 5px !important;
            }
        `;
        
        document.head.appendChild(style);
        console.log('✅ Estilos de iconos emoji agregados');
    }
    
    /**
     * Inicializar cuando el DOM esté listo
     */
    function init() {
        console.log('🔍 Mimer Form Validation - Iniciando...');
        
        // Asegurar que los estilos de iconos estén disponibles
        ensureIconStyles();
        
        const forms = document.querySelectorAll(SELECTORS.FORM);
        console.log('📋 Formularios encontrados:', forms.length);
        
        forms.forEach(function(form, index) {
            console.log('🎯 Inicializando formulario #' + (index + 1));
            
            try {
                // Debug: mostrar contenido del formulario
                console.log('📋 Form details:');
                console.log('  - ID:', form.id || 'sin ID');
                console.log('  - Action:', form.action || 'sin action');
                console.log('  - Method:', form.method || 'sin method');
                
                // Debug: mostrar radio buttons encontrados
                const radioGroups = form.querySelectorAll('input[type="radio"]');
                console.log('  - Radio buttons encontrados:', radioGroups.length);
                
                if (radioGroups.length > 0) {
                    radioGroups.forEach(function(radio, i) {
                        console.log('    Radio #' + (i+1) + ':', radio.name || 'sin-name', '=', radio.value || 'sin-value');
                    });
                }
                
                // Debug: mostrar selects encontrados
                const selects = form.querySelectorAll('select');
                console.log('  - Selects encontrados:', selects.length);
                
                if (selects.length > 0) {
                    selects.forEach(function(select, i) {
                        console.log('    Select #' + (i+1) + ':', select.name || 'sin-name', 'options:', select.options ? select.options.length : 0);
                    });
                }
                
                // Debug: mostrar textareas encontrados
                const textareas = form.querySelectorAll('textarea');
                console.log('  - Textareas encontrados:', textareas.length);
                
                if (textareas.length > 0) {
                    textareas.forEach(function(textarea, i) {
                        console.log('    Textarea #' + (i+1) + ':', textarea.name || 'sin-name', 'required:', textarea.required);
                    });
                }
                
                console.log('🚀 Llamando initFormValidation...');
                initFormValidation(form);
                console.log('✅ initFormValidation completado');
                
            } catch (error) {
                console.error('❌ Error en inicialización del formulario:', error);
            }
        });
        
        // Listener global como backup - captura TODOS los submits
        document.addEventListener('submit', function(e) {
            console.log('🌐 Submit global detectado! Formulario:', e.target);
            console.log('🌐 Formulario ID:', e.target.id);
            console.log('🌐 Formulario clases:', e.target.className);
            
            // Solo procesar si es un formulario que nos interesa
            if (e.target.tagName === 'FORM') {
                console.log('🌐 Es un formulario válido - procesando...');
                const result = handleFormSubmit(e, e.target);
                if (!result) {
                    console.log('🛑 Listener global canceló el envío');
                }
            }
        }, true); // useCapture = true para interceptar antes que otros handlers
        
        console.log('✅ Mimer Form Validation - Inicialización completa');
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
