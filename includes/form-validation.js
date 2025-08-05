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
     * Validar text input fields (nombre, apellido, etc.)
     */
    function validateTextFields(form) {
        let isValid = true;
        
        form.querySelectorAll('input[type="text"][required]').forEach(function(textField) {
            console.log('🔍 Validando text field:', textField.name, 'valor actual:', `"${textField.value.trim()}"`, 'length:', textField.value.trim().length);
            
            if (!textField.value.trim() || textField.value.trim() === '') {
                console.log('❌ Text field inválido:', textField.name, 'razón: valor vacío');
                
                // Buscar el contenedor correcto para el mensaje de error
                const fieldGroup = textField.closest('.elementor-field-group');
                const errorContainer = fieldGroup || textField.parentElement;
                
                console.log('📍 Text field error container será:', errorContainer.className || 'sin clase');
                
                removeExistingError(errorContainer);
                const errorMessage = createErrorMessage(VALIDATION_MESSAGES.FIELD_REQUIRED);
                
                errorContainer.appendChild(errorMessage);
                isValid = false;
            } else {
                console.log('✅ Text field válido:', textField.name, 'caracteres:', textField.value.trim().length);
                
                // Limpiar cualquier error existente si el text field es válido
                const fieldGroup = textField.closest('.elementor-field-group');
                const errorContainer = fieldGroup || textField.parentElement;
                removeExistingError(errorContainer);
            }
        });
        
        return isValid;
    }
    
    /**
     * Validar y normalizar campo de teléfono con numverify
     */
    function validatePhoneField(form) {
        let isValid = true;
        
        const phoneField = form.querySelector('input[name*="phone"], input[name*="Phone"], input[name*="tel"], input[id*="phone"], input[id*="Phone"]');
        
        if (!phoneField) {
            console.log('📞 No se encontró campo de teléfono en el formulario');
            return isValid;
        }
        
        console.log('🔍 Validando teléfono:', phoneField.name || phoneField.id, 'valor actual:', `"${phoneField.value.trim()}"`);
        
        if (!phoneField.value.trim()) {
            console.log('❌ Teléfono inválido: campo vacío');
            
            const fieldGroup = phoneField.closest('.elementor-field-group');
            const errorContainer = fieldGroup || phoneField.parentElement;
            
            removeExistingError(errorContainer);
            const errorMessage = createErrorMessage('Please enter a valid phone number');
            errorContainer.appendChild(errorMessage);
            
            return false;
        }
        
        // Aquí haríamos la validación con numverify
        // Por ahora, validamos formato básico y simulamos normalización
        console.log('📞 Validando teléfono con numverify:', phoneField.value.trim());
        
        // TODO: Implementar llamada real a numverify
        // Por ahora, simulamos la normalización
        const normalizedPhone = normalizePhoneNumber(phoneField.value.trim());
        if (normalizedPhone) {
            console.log('✅ Teléfono válido y normalizado:', normalizedPhone);
            phoneField.value = normalizedPhone;
            
            // Limpiar errores existentes
            const fieldGroup = phoneField.closest('.elementor-field-group');
            const errorContainer = fieldGroup || phoneField.parentElement;
            removeExistingError(errorContainer);
        } else {
            console.log('❌ Teléfono inválido: formato no reconocido');
            
            const fieldGroup = phoneField.closest('.elementor-field-group');
            const errorContainer = fieldGroup || phoneField.parentElement;
            
            removeExistingError(errorContainer);
            const errorMessage = createErrorMessage('Please enter a valid US phone number');
            errorContainer.appendChild(errorMessage);
            
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Normalizar número de teléfono (simulación de numverify)
     */
    function normalizePhoneNumber(phone) {
        // Remover todos los caracteres que no sean dígitos
        const digitsOnly = phone.replace(/\D/g, '');
        
        console.log('📞 Dígitos extraídos:', digitsOnly);
        
        // Si tiene 10 dígitos, agregar código de país US (+1)
        if (digitsOnly.length === 10) {
            return '1' + digitsOnly;
        }
        
        // Si tiene 11 dígitos y empieza con 1, es válido
        if (digitsOnly.length === 11 && digitsOnly.startsWith('1')) {
            return digitsOnly;
        }
        
        // Cualquier otro caso es inválido
        return null;
    }
    
    /**
     * Validar campo de ZIP code (exactamente 5 dígitos)
     */
    function validateZipCodeField(form) {
        let isValid = true;
        
        const zipField = form.querySelector('input[name*="zip"], input[name*="Zip"], input[name*="ZIP"], input[name*="postal"], input[id*="zip"], input[id*="Zip"], input[id*="ZIP"], input[type="number"][name*="zip"], input[type="number"][id*="zip"]');
        
        if (!zipField) {
            console.log('📮 No se encontró campo de ZIP code en el formulario');
            return isValid;
        }
        
        console.log('🔍 Validando ZIP code:', zipField.name || zipField.id, 'tipo:', zipField.type, 'valor actual:', `"${zipField.value.trim()}"`);
        
        const zipValue = zipField.value.trim();
        
        if (!zipValue) {
            console.log('❌ ZIP code inválido: campo vacío');
            
            const fieldGroup = zipField.closest('.elementor-field-group');
            const errorContainer = fieldGroup || zipField.parentElement;
            
            removeExistingError(errorContainer);
            const errorMessage = createErrorMessage('Please enter a valid ZIP code');
            errorContainer.appendChild(errorMessage);
            
            return false;
        }
        
        // Validar que sea exactamente 5 dígitos
        const zipPattern = /^\d{5}$/;
        if (!zipPattern.test(zipValue)) {
            console.log('❌ ZIP code inválido: debe ser exactamente 5 dígitos, recibido:', zipValue, 'length:', zipValue.length);
            
            const fieldGroup = zipField.closest('.elementor-field-group');
            const errorContainer = fieldGroup || zipField.parentElement;
            
            removeExistingError(errorContainer);
            const errorMessage = createErrorMessage('ZIP code must be exactly 5 digits');
            errorContainer.appendChild(errorMessage);
            
            return false;
        }
        
        console.log('✅ ZIP code válido:', zipValue);
        
        // Limpiar errores existentes
        const fieldGroup = zipField.closest('.elementor-field-group');
        const errorContainer = fieldGroup || zipField.parentElement;
        removeExistingError(errorContainer);
        
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
        
        // Text fields - limpiar errores cuando el usuario escriba contenido válido
        form.querySelectorAll('input[type="text"][required]').forEach(function(textField) {
            textField.addEventListener('input', function() {
                console.log('✏️ Text field cambió:', textField.name, 'caracteres:', textField.value.trim().length);
                
                // Si hay contenido válido, limpiar error
                if (textField.value.trim().length > 0) {
                    console.log('✅ Contenido válido en text field, limpiando error para:', textField.name);
                    
                    const fieldGroup = textField.closest('.elementor-field-group');
                    const errorContainer = fieldGroup || textField.parentElement;
                    removeExistingError(errorContainer);
                } else {
                    console.log('⚠️ Text field aún vacío:', textField.name);
                }
            });
        });
        
        // Phone field - validar y normalizar en tiempo real
        const phoneField = form.querySelector('input[name*="phone"], input[name*="Phone"], input[name*="tel"], input[id*="phone"], input[id*="Phone"]');
        if (phoneField) {
            console.log('📞 Configurando listeners para campo de teléfono:', phoneField.name || phoneField.id);
            
            phoneField.addEventListener('blur', function() {
                console.log('📞 Teléfono perdió foco, validando:', phoneField.value);
                
                if (phoneField.value.trim()) {
                    const normalizedPhone = normalizePhoneNumber(phoneField.value.trim());
                    if (normalizedPhone) {
                        console.log('✅ Teléfono normalizado automáticamente:', normalizedPhone);
                        phoneField.value = normalizedPhone;
                        
                        const fieldGroup = phoneField.closest('.elementor-field-group');
                        const errorContainer = fieldGroup || phoneField.parentElement;
                        removeExistingError(errorContainer);
                    }
                }
            });
        }
        
        // ZIP code field - limitar entrada y validar en tiempo real
        const zipField = form.querySelector('input[name*="zip"], input[name*="Zip"], input[name*="ZIP"], input[name*="postal"], input[id*="zip"], input[id*="Zip"], input[id*="ZIP"], input[type="number"][name*="zip"], input[type="number"][id*="zip"]');
        if (zipField) {
            console.log('📮 Configurando listeners para campo ZIP code:', zipField.name || zipField.id);
            console.log('📮 ZIP field elemento:', zipField, 'tipo:', zipField.type);
            
            // Para campos de tipo number, agregar maxlength mediante setAttribute
            if (zipField.type === 'number') {
                zipField.setAttribute('maxlength', '5');
                console.log('📮 Establecido maxlength=5 para campo number');
            }
            
            // Función para filtrar entrada
            function filterZipInput(field) {
                console.log('📮 Filtrando ZIP - Valor antes:', field.value, 'tipo:', field.type);
                
                // Para campos number, el navegador ya filtra no-números, pero podemos limitar longitud
                let value = field.value.toString().replace(/\D/g, '');
                
                // Limitar a máximo 5 dígitos
                if (value.length > 5) {
                    value = value.slice(0, 5);
                    console.log('📮 ZIP truncado a 5 dígitos:', value);
                }
                
                // Actualizar el campo
                field.value = value;
                console.log('📮 ZIP field valor final:', field.value);
                
                // Si tiene exactamente 5 dígitos, limpiar errores
                if (value.length === 5) {
                    console.log('✅ ZIP code válido, limpiando errores');
                    const fieldGroup = field.closest('.elementor-field-group');
                    const errorContainer = fieldGroup || field.parentElement;
                    removeExistingError(errorContainer);
                }
            }
            
            // Event listener para input (escritura normal)
            zipField.addEventListener('input', function(e) {
                console.log('📮 ZIP input event triggered');
                filterZipInput(zipField);
            });
            
            // Event listener para keyup (backup)
            zipField.addEventListener('keyup', function(e) {
                console.log('📮 ZIP keyup event triggered');
                filterZipInput(zipField);
            });
            
            // Event listener para keydown (prevenir entrada de caracteres no válidos)
            zipField.addEventListener('keydown', function(e) {
                console.log('📮 ZIP keydown - Key:', e.key, 'KeyCode:', e.keyCode, 'campo tipo:', zipField.type);
                
                // Permitir teclas de control (backspace, delete, tab, escape, enter)
                if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                    // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true)) {
                    return;
                }
                
                // Si ya tiene 5 dígitos y no es una tecla de control, prevenir
                const currentValue = zipField.value.toString().replace(/\D/g, '');
                if (currentValue.length >= 5) {
                    console.log('📮 ZIP ya tiene 5 dígitos, previniendo entrada adicional');
                    e.preventDefault();
                    return;
                }
                
                // Para campos number, solo validar si ya hay 5 dígitos
                // El navegador ya filtra caracteres no numéricos en type="number"
                if (zipField.type === 'number') {
                    // Solo prevenir si ya hay 5 dígitos
                    if (currentValue.length >= 5) {
                        e.preventDefault();
                    }
                } else {
                    // Para campos text, validar caracteres no numéricos
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        console.log('📮 Caracter no numérico, previniendo:', e.key);
                        e.preventDefault();
                    }
                }
            });
            
            // Prevenir pegar contenido no numérico
            zipField.addEventListener('paste', function(e) {
                console.log('📮 ZIP paste event triggered');
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const numericOnly = paste.replace(/\D/g, '').slice(0, 5);
                zipField.value = numericOnly;
                console.log('📮 ZIP code pegado y filtrado:', numericOnly);
                
                // Trigger input event para procesar
                filterZipInput(zipField);
            });
            
            // Para campos number, también agregar un listener especial
            if (zipField.type === 'number') {
                zipField.addEventListener('change', function(e) {
                    console.log('📮 ZIP number field change event');
                    filterZipInput(zipField);
                });
            }
        }
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
        
        console.log('✏️ Validando text fields...');
        const textFieldsValid = validateTextFields(form);
        
        console.log('📞 Validando teléfono...');
        const phoneValid = validatePhoneField(form);
        
        console.log('📮 Validando ZIP code...');
        const zipValid = validateZipCodeField(form);
        
        const isValid = radioValid && selectValid && textareaValid && textFieldsValid && phoneValid && zipValid;
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
                
                // Debug: mostrar text fields encontrados
                const textFields = form.querySelectorAll('input[type="text"]');
                console.log('  - Text fields encontrados:', textFields.length);
                
                if (textFields.length > 0) {
                    textFields.forEach(function(textField, i) {
                        console.log('    Text field #' + (i+1) + ':', textField.name || 'sin-name', 'required:', textField.required);
                    });
                }
                
                // Debug: mostrar campo de teléfono encontrado
                const phoneField = form.querySelector('input[name*="phone"], input[name*="Phone"], input[name*="tel"], input[id*="phone"], input[id*="Phone"]');
                console.log('  - Campo de teléfono encontrado:', phoneField ? 'SÍ' : 'NO');
                
                if (phoneField) {
                    console.log('    Phone field:', phoneField.name || phoneField.id || 'sin-name', 'type:', phoneField.type, 'required:', phoneField.required);
                }
                
                // Debug: mostrar campo de ZIP code encontrado
                const zipField = form.querySelector('input[name*="zip"], input[name*="Zip"], input[name*="ZIP"], input[name*="postal"], input[id*="zip"], input[id*="Zip"], input[id*="ZIP"], input[type="number"][name*="zip"], input[type="number"][id*="zip"]');
                console.log('  - Campo de ZIP code encontrado:', zipField ? 'SÍ' : 'NO');
                
                if (zipField) {
                    console.log('    ZIP field:', zipField.name || zipField.id || 'sin-name', 'type:', zipField.type, 'required:', zipField.required);
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
