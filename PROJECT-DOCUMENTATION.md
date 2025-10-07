# 📘 Mimer Forms VDI - Documentación del Proyecto

**Última actualización:** 7 de octubre de 2025  
**Versión del plugin:** 2.6.final  
**Estado:** Producción activa

---

## 🎯 Descripción General

Plugin de WordPress que integra formularios de Elementor con el API de VDI (Valor Digital Intelligence) para el sitio **InjuryResolve.com**. Procesa leads relacionados con demandas médicas (actualmente Depo-Provera) y los envía a un sistema de gestión de casos.

---

## 📁 Estructura del Proyecto

```
mimer-forms/
├── formularios-elementor.php          # Plugin principal
├── admin/
│   └── back-end.php                   # Panel de administración
├── includes/
│   ├── forms-api.php                  # Lógica de integración con VDI API
│   ├── form-validation.php            # Clase de validación de formularios
│   ├── form-validation.js             # Validaciones frontend (v2.6)
│   ├── some_magic.js                  # Utilidades JS (TrustedForm)
│   ├── class-numverify.php            # Validación de teléfonos (Numverify API)
│   ├── ajax-handler.js                # Handler de requests AJAX
│   ├── multi-form-handler.js          # Manejo de múltiples formularios
│   └── select2-handler.php            # Integración Select2
├── log.txt                            # Log de operaciones
└── *.md                               # Documentación técnica
```

---

## 🔧 Componentes Principales

### 1. **formularios-elementor.php**
Archivo principal del plugin que:
- Registra hooks de WordPress
- Carga scripts y estilos
- Inicializa la validación de formularios
- Hook: `elementor_pro/forms/validation` → `env_validate_phone_number()`

### 2. **forms-api.php** (Clase: `MimerFormsVDI`)
Maneja la comunicación con el API de VDI:
- **Método principal:** `send_submission_to_vdi($fields)`
- **Validaciones:**
  - Solo procesa si `case_diagnosis === "Brain Meningioma"`
  - Otros diagnósticos van a `/dp_rejected/`
- **Timeout:** 10 segundos
- **Modo de pruebas:** Configurable desde el admin
- **Redirección:** Usa `$_SESSION` para pasar URL al frontend

### 3. **form-validation.php** (Clase: `MimerFormValidation`)
Validaciones del lado del servidor:
- Validación de teléfono (Numverify API)
- Validación de campos requeridos
- Validación de formato de email
- Logging de validaciones

### 4. **form-validation.js** (v2.6.final)
Validaciones frontend:
- Validación en tiempo real de campos
- Soporte para ZIP code (número de 5 dígitos)
- Validación de teléfono con formato US
- Prevención de doble envío
- Manejo de redirecciones post-submit

### 5. **some_magic.js**
Utilidades JavaScript:
- **Función principal:** `moveTrustedFormFieldsToWrapper()`
- Mueve campos TrustedForm dinámicos dentro del wrapper de Elementor
- Permite que TrustedForm tokens se guarden en submissions de Elementor
- Se ejecuta en `window.load` y `DOMContentLoaded`

---

## 📋 Formularios Activos

### **Formulario: Depo-Provera (ID: `dp_form`)**

**URL:** `https://injuryresolve.com/depo-provera-lawsuit/`

#### Campos del Formulario:

| Campo | Name | Type | Required | Notas |
|-------|------|------|----------|-------|
| Depo-Provera Taken | `form_fields[case_depo_provera_taken]` | Radio | ✅ | Yes/No |
| Duration of Use | `form_fields[case_depo_provera_use]` | Select | ✅ | 1 year, 2 years, etc. |
| Diagnosis | `form_fields[case_diagnosis]` | Select | ✅ | **CRÍTICO:** Solo "Brain Meningioma" procesa API |
| Attorney | `form_fields[case_attorney]` | Select | ✅ | Yes/No |
| Case Description | `form_fields[case_description]` | Textarea | ✅ | Descripción libre |
| First Name | `form_fields[lead_first_name]` | Text | ✅ | |
| Last Name | `form_fields[lead_last_name]` | Text | ✅ | |
| Phone | `form_fields[lead_phone]` | Tel | ✅ | Validado con Numverify |
| Email | `form_fields[lead_email]` | Email | ✅ | |
| ZIP Code | `form_fields[lead_zip_code]` | Number | ✅ | Máximo 5 dígitos |
| Accept Terms | `form_fields[accept]` | Checkbox | ✅ | |
| TrustedForm | `form_fields[trustedform]` | Hidden | ❌ | Auto-generado |
| Case Injury | `form_fields[case_injury]` | Hidden | ❌ | Valor: "Brain Meningioma" |

#### Campos TrustedForm (dinámicos):
- `form-field-trustedform_1` → `form_fields[trustedform_cert]`
- `xxTrustedFormToken_1` → `form_fields[trustedform_token]`
- `xxTrustedFormPingUrl_1` → `form_fields[trustedform_ping]`

---

## 🔄 Flujo de Procesamiento

```
Usuario llena formulario
    ↓
JavaScript valida campos (form-validation.js)
    ↓
Submit → AJAX a WordPress
    ↓
Hook: elementor_pro/forms/validation
    ↓
PHP: env_validate_phone_number()
    ↓
Validación Numverify (teléfono)
    ↓
Verifica: case_diagnosis === "Brain Meningioma"?
    ↓
    ├─ SÍ → MimerFormsVDI::send_submission_to_vdi()
    │         ↓
    │         POST a VDI API (timeout: 10s)
    │         ↓
    │         API devuelve redirect_url?
    │         ↓
    │         ├─ SÍ → Usar redirect_url del API
    │         └─ NO → Redirigir a /dp_rejected/
    │
    └─ NO → Redirigir directo a /dp_rejected/
    ↓
Guardar redirect_url en $_SESSION
    ↓
JavaScript lee sesión y redirige
```

---

## 🔐 Configuración de Seguridad

### API URL (forms-api.php línea ~37)
```php
$url = 'https://api-vdi.luchtech.dev/api/submissions?form=depo-provera-injury-resolve&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=f6bed0c57b7e6745e427faf65796f2fef47e8fb8ea1c01566ee4ba576f34e0ed';
```

### Modo de Pruebas
- **Activación:** Panel Admin → Forms VDI → API → "🧪 Modo de Pruebas"
- **Efecto:** Simula respuesta del API sin enviar datos reales
- **Log:** Guarda en `log.txt` todos los payloads

---

## 🧪 Testing y Debugging

### Logs
- **Archivo:** `log.txt` en raíz del plugin
- **Contenido:**
  - Timestamps de envíos
  - JSON payloads completos
  - Respuestas del API
  - Errores de validación
  - URLs de redirección

### Consola del Navegador
Con modo de pruebas desactivado, los logs están minimizados para producción.

### Endpoints de Redirección
- **Éxito (API):** Variable según respuesta del API
- **Rechazo:** `https://injuryresolve.com/dp_rejected/`
- **Thank you (simulado):** `https://injuryresolve.com/dp-thankyou/`

---

## 📦 Dependencias Externas

### APIs
1. **VDI API** (Luchtech)
   - URL: `https://api-vdi.luchtech.dev`
   - Timeout: 10 segundos
   - Método: POST
   - Content-Type: application/json

2. **Numverify API**
   - Validación de números telefónicos
   - Configuración en `class-numverify.php`

3. **TrustedForm**
   - Certificación de leads
   - Scripts externos: `cert.trustedform.com`, `ping.trustedform.com`
   - Campos inyectados dinámicamente

### JavaScript Libraries
- jQuery (WordPress core)
- Elementor Pro Forms API

---

## 🚀 Deployment

### Instalación
1. Subir carpeta `mimer-forms` a `/wp-content/plugins/`
2. Activar plugin en WordPress admin
3. Configurar API keys en admin panel
4. Probar en modo de pruebas primero

### Actualización
```bash
cd /path/to/mimer-forms
git pull origin main
# Limpiar caché de WordPress/Elementor si es necesario
```

### Cache Busting
- `form-validation.js`: Versión `2.6.final.{timestamp}`
- `some_magic.js`: Versión `1.0.{timestamp}`

---

## 🔍 Identificación de Formularios

### Cómo se detecta un formulario:

1. **Por ID del form:**
   ```javascript
   const form = document.querySelector('#dp_form');
   ```

2. **Por clase de Elementor:**
   ```javascript
   const elementorForms = document.querySelectorAll('.elementor-form');
   ```

3. **Por hook de WordPress:**
   ```php
   add_action('elementor_pro/forms/validation', 'env_validate_phone_number', 10, 2);
   ```

### Para agregar un NUEVO formulario:

#### Paso 1: Crear formulario en Elementor
- Asignar ID único (ej: `roundup_form`)
- Configurar campos con names como `form_fields[campo_name]`

#### Paso 2: Agregar método en `forms-api.php`
```php
public static function send_roundup_to_api($fields) {
    // Validaciones específicas
    // Mapeo de campos
    // POST al API correspondiente
}
```

#### Paso 3: Actualizar `some_magic.js` si usa TrustedForm
```javascript
const form = document.querySelector('#roundup_form');
// Agregar lógica similar
```

#### Paso 4: Routing en `env_validate_phone_number()`
```php
// Detectar formulario por algún campo único
if (isset($fields['case_roundup_used'])) {
    MimerFormsVDI::send_roundup_to_api($flat_fields);
} else {
    MimerFormsVDI::send_submission_to_vdi($flat_fields);
}
```

---

## 🐛 Problemas Conocidos y Soluciones

### 1. Headers Already Sent
**Causa:** Output antes de `session_start()`  
**Solución:** Implementado `ob_start()` en plugin principal

### 2. Redirección no funciona
**Causa:** Sesión no iniciada o cookies bloqueadas  
**Solución:** Backup con cookies `mimer_redirect_backup`

### 3. TrustedForm no se guarda
**Causa:** Campos generados fuera del wrapper de Elementor  
**Solución:** `some_magic.js` los mueve automáticamente

### 4. Doble procesamiento
**Causa:** Usuario hace doble clic en submit  
**Solución:** Prevención en `form-validation.js` (botón disabled)

---

## 📊 Mapeo de Campos al API

### Depo-Provera Form → VDI API

| Formulario | API Field | Transformación |
|------------|-----------|----------------|
| `lead_first_name` | `lead-first-name` | Directo |
| `lead_last_name` | `lead-last-name` | Directo |
| `lead_email` | `lead-email-address` | Directo |
| `lead_phone` | `lead-phone` | Solo dígitos |
| `case_depo_provera_taken` | `case-depo-provera-taken` | Directo |
| `case_depo_provera_use` | `case-depo-provera-use` | Directo |
| `case_diagnosis` | `case-diagnosis` | Directo |
| `case_injury` | `case-injury` | Directo |
| `case_description` | `case-description` | Directo |
| `case_attorney` | `case-attorney` | Yes/No (capitalizado) |
| `trustedform_cert` | `lead-trusted-form-cert-id` | Directo |
| `lead_zip_code` | `lead-zip-code` | String |
| `$_SERVER['REMOTE_ADDR']` | `lead-ip-address` | Directo |

---

## 📞 Contacto y Soporte

**Desarrollador:** Mimer  
**Repositorio:** `github.com/Mimergt/mimer-forms`  
**Cliente:** InjuryResolve.com  

---

## 📜 Historial de Cambios Importantes

- **v2.6.final** - Limpieza de logs en producción
- **v2.6** - Soporte para ZIP code como number field
- **v2.5** - Integración TrustedForm con Elementor submissions
- **v2.0** - Validación de Brain Meningioma obligatoria
- **v1.4** - Primera versión estable con VDI API
- **v1.0** - Versión inicial

---

## ⚠️ Notas Importantes

1. **NUNCA** deshabilitar el check de `case_diagnosis === "Brain Meningioma"` sin autorización
2. **SIEMPRE** probar en modo de pruebas antes de cambios importantes
3. **REVISAR** `log.txt` regularmente para detectar errores
4. **MANTENER** las API keys fuera del repositorio público
5. **HACER BACKUP** antes de cada actualización importante

---

**Fin de la documentación**
