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
        form.querySelectorAll(SELECTORS.RADIO_REQUIRED).forEach(function(radio) {
            const name = radio.name;
            if (!radioGroups[name]) {
                radioGroups[name] = {
                    radios: form.querySelectorAll('input[type="radio"][name="' + name + '"]'),
                    isChecked: false,
                    container: radio.closest(SELECTORS.FIELD_GROUP)
                };
            }
        });
        
        // Verificar cada grupo
        Object.keys(radioGroups).forEach(function(groupName) {
            const group = radioGroups[groupName];
            group.isChecked = Array.from(group.radios).some(radio => radio.checked);
            
            if (!group.isChecked) {
                removeExistingError(group.container);
                const errorMessage = createErrorMessage(VALIDATION_MESSAGES.RADIO_REQUIRED);
                group.container.appendChild(errorMessage);
                isValid = false;
            } else {
                removeExistingError(group.container);
            }
        });
        
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
        // Personalizar mensajes HTML5
        customizeHTML5Messages(form);
        
        // Setup limpieza automática de errores
        setupErrorCleanup(form);
        
        // Validación en submit
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar radio buttons
            if (!validateRadioGroups(form)) {
                isValid = false;
            }
            
            // Validar selects
            if (!validateSelectFields(form)) {
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Permitir envío normal del formulario
            return true;
        });
    }
    
    /**
     * Inicializar cuando el DOM esté listo
     */
    function init() {
        const forms = document.querySelectorAll(SELECTORS.FORM);
        forms.forEach(initFormValidation);
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
