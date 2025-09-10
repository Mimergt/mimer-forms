# 🚀 Sistema Unificado Multi-Formulario - Documentación

## Versión: v2.0-unified-system

### 📋 Resumen

El sistema unificado permite manejar múltiples tipos de formularios (Depo Provera, RoundUp, y futuros formularios) con una sola implementación que detecta automáticamente el tipo de formulario basado en los campos enviados.

## 🔍 Detección Automática

### Mecanismo de Detección

El sistema detecta el tipo de formulario analizando los campos enviados y comparándolos con campos de detección específicos definidos en la configuración:

```php
'detection_fields' => array('campo1', 'campo2')
```

Si **TODOS** los campos de detección están presentes, se identifica ese tipo de formulario.

### Tipos de Formulario Soportados

#### 1. **Depo Provera** (Formulario original)
- **Campos de detección:** `case_depo_provera_taken`, `case_depo_provera_use`
- **API Form ID:** `ir-lca-depo-post`
- **Success URL:** `https://injuryresolve.com/dp-thankyou/`
- **Rejected URL:** `https://injuryresolve.com/dp_rejected/`

#### 2. **RoundUp** (Nuevo formulario)
- **Campos de detección:** `exposed`, `injury`
- **API Form ID:** `ir-lca-roundup-post`
- **Success URL:** `https://injuryresolve.com/ru-thankyou/`
- **Rejected URL:** `https://injuryresolve.com/ru_rejected/`

#### 3. **Fallback**
- Si no se detecta ningún tipo específico, usa configuración de Depo Provera
- Garantiza compatibilidad con formularios existentes

## 🔄 Mapeo de Campos Dinámico

### Configuración por Formulario

Cada tipo de formulario tiene su propio mapeo de campos definido en `field_mappings`:

```php
'field_mappings' => array(
    'api-field-name' => 'form_field_name',
    'lead-first-name' => 'lead_first_name',
    'case-injury' => 'injury'  // RoundUp
    'case-injury' => 'case_injury'  // Depo Provera
)
```

### Campos Especiales

Algunos campos requieren procesamiento especial:
- **Teléfono:** Se limpia automáticamente (solo números)
- **Attorney:** Se normaliza a "Yes"/"No"
- **TrustedForm:** Se obtiene de `$_POST['xxTrustedFormToken']`
- **IP Address:** Se agrega automáticamente

## 🛠️ Implementación Técnica

### Estructura del Código

```php
class MimerFormsVDI {
    // Configuración centralizada de todos los formularios
    private static $form_configs = array(
        'form_type' => array(
            'api_form_id' => 'api-endpoint-id',
            'signature' => 'api-signature',
            'detection_fields' => array('field1', 'field2'),
            'thank_you_url' => 'success-url',
            'rejected_url' => 'rejection-url',
            'field_mappings' => array(...)
        )
    );
    
    // Función de detección automática
    private static function detect_form_type($fields);
    
    // Función principal (modificada para ser dinámica)
    public static function send_submission_to_vdi($fields);
}
```

### Flujo de Procesamiento

1. **Recepción de datos** → `send_submission_to_vdi($fields)`
2. **Detección automática** → `detect_form_type($fields)`
3. **Carga de configuración** → `$form_configs[$detected_type]`
4. **Mapeo dinámico** → Usa `field_mappings` específico
5. **Construcción de URL** → API endpoint con parámetros específicos
6. **Envío al API** → Con datos mapeados correctamente
7. **Redirección dinámica** → URLs específicas por formulario

## 🧪 Testing y Validación

### Archivos de Prueba Incluidos

1. **`test-detection.php`** - Pruebas automatizadas del sistema de detección
2. **`test-roundup-form.html`** - Formulario RoundUp funcional para pruebas
3. **`test-unified-system.php`** - Pruebas completas del sistema

### Ejecutar Pruebas

```bash
# Prueba de detección automática
php test-detection.php

# Las pruebas verifican:
# ✅ Detección correcta de Depo Provera
# ✅ Detección correcta de RoundUp  
# ✅ Fallback para datos incompletos
# ✅ Configuraciones específicas por formulario
# ✅ URLs dinámicas correctas
```

## 📋 Guía de Implementación

### Para Formularios Existentes (Depo Provera)

**✅ No requiere cambios** - Siguen funcionando automáticamente

### Para Formularios Nuevos (RoundUp)

1. **Incluir campos de detección obligatorios:**
   ```html
   <input name="exposed" type="radio" value="Yes, directly exposed">
   <select name="injury">
       <option value="Non-Hodgkin's Lymphoma">Non-Hodgkin's Lymphoma</option>
   </select>
   ```

2. **Usar los nombres de campo correctos:**
   - `lead_first_name`, `lead_last_name`, `lead_email`, `lead_phone`
   - `attorney` (en lugar de `case_attorney`)
   - `exposed_duration`, `year_were_diagnosed`, `age`
   - `case_brief` (en lugar de `case_description`)

3. **TrustedForm automático** - Se maneja automáticamente

### Agregar Nuevos Tipos de Formulario

1. **Agregar configuración** en `$form_configs`:
   ```php
   'nuevo_tipo' => array(
       'api_form_id' => 'ir-lca-nuevo-post',
       'signature' => 'nueva-signature',
       'detection_fields' => array('campo_unico_1', 'campo_unico_2'),
       'thank_you_url' => 'https://injuryresolve.com/nuevo-thankyou/',
       'rejected_url' => 'https://injuryresolve.com/nuevo_rejected/',
       'field_mappings' => array(
           'api-field' => 'form_field',
           // ... mapeos específicos
       )
   )
   ```

2. **Definir campos de detección únicos** que identifiquen ese formulario

3. **Mapear campos específicos** del formulario al API

## 📊 Monitoreo y Logs

### Información en Logs

Los logs ahora incluyen información de detección:

```
[2024-01-15 10:30:45] 🔴 MODO PRODUCCIÓN - Formulario detectado: ROUNDUP (12 campos)
[2024-01-15 10:30:45] 📝 CASE_INJURY (roundup): 'Non-Hodgkin's Lymphoma'
[2024-01-15 10:30:45] 🔗 URL DESTINO: https://api.valuedirectinc.com/api/submissions?form=ir-lca-roundup-post&signature=...
```

### Campos de Monitoreo

- **Tipo detectado** - Confirma que la detección funciona
- **Número de campos** - Verifica integridad de datos
- **URLs dinámicas** - Confirma configuración correcta
- **Respuestas del API** - Monitorea éxito/fallos

## 🔒 Seguridad y Compatibilidad

### Compatibilidad Hacia Atrás

- **✅ Formularios existentes** siguen funcionando sin cambios
- **✅ Sistema de fallback** maneja casos edge
- **✅ Configuración modular** permite actualizaciones sin romper funcionalidad

### Seguridad

- **Validación de campos** mantenida
- **Sanitización automática** de datos
- **Signatures específicas** por formulario
- **URLs controladas** por configuración

## 🚀 Estado de Producción

### ✅ Validaciones Completadas

- [x] Detección automática funcional
- [x] Mapeo de campos correcto  
- [x] URLs dinámicas operativas
- [x] Sistema de fallback estable
- [x] Compatibilidad hacia atrás
- [x] Testing automatizado pasado
- [x] Documentación completa

### 🎯 Listo para Despliegue

El sistema v2.0-unified-system está **listo para producción** con todas las validaciones completadas y testing exhaustivo realizado.

## 📋 Changelog

### v2.2-roundup-field-mapping (Actual)
- 🔧 **RoundUp Field Fix**: Corrección crítica en mapeo de campos RoundUp
- 🔧 **Detection Update**: Campos de detección actualizados a `case_exposed`, `case_injury`
- 🔧 **Mapping Correction**: Mapeo desde `case_exposed` → `case-exposed` (API)
- ✅ **Test Added**: Nuevo test de detección automática (`test-roundup-detection.php`)
- ✅ **Production Ready**: Compatibilidad con formularios reales de producción

### v2.1-select2-integration
- ✅ **Select2 Integration**: CDN-based Select2 4.1.0-rc.0 con diseño responsivo
- ✅ **Admin Controls**: Toggle para activar/desactivar Select2 desde admin
- ✅ **Mobile Optimization**: Búsqueda deshabilitada en dispositivos móviles
- ✅ **Elementor Compatibility**: Detección automática de formularios Elementor
- ✅ **Multi-step Support**: Compatible con formularios de múltiples pasos

### v2.0-unified-system
- ✅ **Sistema Unificado**: Detección automática entre Depo Provera y RoundUp
- ✅ **Multi-Form API**: Configuración centralizada para múltiples formularios
- ✅ **Auto-Detection**: Algoritmo inteligente basado en campos específicos
- ✅ **Logging Mejorado**: Información detallada por tipo de formulario

---

**Versión:** v2.2-roundup-field-mapping  
**Autor:** Sistema de IA  
**Fecha:** 2024  
**Estado:** ✅ PRODUCCIÓN READY
