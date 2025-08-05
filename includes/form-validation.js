/**
 * Mimer Forms VDI - Form Validation
 * Validaciones de formulario en inglés para Elementor Forms
 * Versión: 1.0
 */

(function() {
    'use strict';
    
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
        errorMessage.textContent = text;
        return errorMessage;
    }
    
    /**
     * Remover mensajes de error existentes
     */
    function removeExistingError(container) {
        const existingError = container.querySelector(SELECTORS.ERROR_MESSAGE);
        if (existingError) {
            existingError.remove();
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
            if (!select.value || select.value === '--select--' || select.value === '') {
                removeExistingError(select.parentElement);
                const errorMessage = createErrorMessage(VALIDATION_MESSAGES.SELECT_REQUIRED);
                select.parentElement.appendChild(errorMessage);
                isValid = false;
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
        submitButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                console.log('📤 Submit Method 3 - Button click detectado!', button);
                // No preventDefault aquí, solo logging
            });
        });
        
        console.log('✅ Validación configurada para formulario');
    }
    
    /**
     * Manejar el submit del formulario
     */
    function handleFormSubmit(e, form) {
        console.log('📤 Submit detectado! Iniciando validación...');
        let isValid = true;
        
        // Validar radio buttons
        console.log('🔘 Validando radio buttons...');
        if (!validateRadioGroups(form)) {
            console.log('❌ Error en radio buttons');
            isValid = false;
        } else {
            console.log('✅ Radio buttons OK');
        }
        
        // Validar selects
        console.log('📋 Validando selects...');
        if (!validateSelectFields(form)) {
            console.log('❌ Error en selects');
            isValid = false;
        } else {
            console.log('✅ Selects OK');
        }
        
        if (!isValid) {
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
     * Inicializar cuando el DOM esté listo
     */
    function init() {
        console.log('🔍 Mimer Form Validation - Iniciando...');
        const forms = document.querySelectorAll(SELECTORS.FORM);
        console.log('📋 Formularios encontrados:', forms.length);
        
        forms.forEach(function(form, index) {
            console.log('🎯 Inicializando formulario #' + (index + 1));
            
            // Debug: mostrar contenido del formulario
            console.log('📋 Form details:');
            console.log('  - ID:', form.id);
            console.log('  - Action:', form.action);
            console.log('  - Method:', form.method);
            
            // Debug: mostrar radio buttons encontrados
            const radioGroups = form.querySelectorAll('input[type="radio"]');
            console.log('  - Radio buttons encontrados:', radioGroups.length);
            radioGroups.forEach(function(radio, i) {
                console.log('    Radio #' + (i+1) + ':', radio.name, '=', radio.value);
            });
            
            // Debug: mostrar selects encontrados
            const selects = form.querySelectorAll('select');
            console.log('  - Selects encontrados:', selects.length);
            selects.forEach(function(select, i) {
                console.log('    Select #' + (i+1) + ':', select.name, 'options:', select.options.length);
            });
            
            initFormValidation(form);
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
