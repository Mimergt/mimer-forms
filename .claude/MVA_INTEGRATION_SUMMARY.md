# MVA Form Integration Summary

## Fecha: 2026-02-04

## Tareas Completadas ✅

### 1. Backup en Git
- ✅ Estado actual guardado en rama `main` (commit: 511c7d7)
- ✅ Versión anterior: 3.0.3

### 2. Nueva Rama "vma"
- ✅ Rama creada: `vma`
- ✅ Versión actualizada: **4.0.0**
- ✅ Commits realizados:
  - `3d53905` - chore: bump version to 4.0.0 for MVA form integration
  - `dd013bb` - feat: add MVA (Motor Vehicle Accident) form integration
- ✅ Rama subida a GitHub: `origin/vma`

### 3. Integración API MVA
- ✅ Función creada: `send_mva_to_api()` en `includes/forms-api.php`
- ✅ Detección de formulario agregada en `formularios-elementor.php`
- ✅ Mapeo de campos según colección de Postman

## Detalles de la Integración

### Endpoint API
```
https://api.valuedirectinc.com/api/v2/submissions?form=lbm-mva&team=vdi&user=ee5a1aba-6009-4d58-8a16-3810e2f777ad&signature=59a9a1ccf0b363c4d2b69b93da44759b3fc74224877c75acb6930bde8d5a64e7
```

### Campos del Formulario MVA

#### Información del Lead
- `lead-first-name` - Nombre
- `lead-last-name` - Apellido
- `lead-email-address` - Email
- `lead-phone` - Teléfono (10 dígitos, limpiado automáticamente)
- `lead-state` - Estado
- `lead-zip-code` - Código postal
- `lead-ip-address` - IP del usuario (automático)
- `lead-trusted-form-url` - TrustedForm token

#### Información del Caso
- `case-physically-injured` - ¿Lesionado físicamente? (Yes/No)
- `case-accident-date` - Fecha del accidente (MM/DD/YYYY)
  - **Validación**: Debe estar dentro de los últimos 2 años
- `case-received-treatment` - ¿Recibió tratamiento? (Yes/No)
- `case-at-fault` - ¿Fue culpable? (Yes/No)
  - **Validación**: "Yes" no es aceptado por la API
- `case-attorney` - ¿Tiene abogado? (Yes/No)
  - **Validación**: "Yes" no es aceptado por la API
- `case-notes` - Notas adicionales

### Detección del Formulario

El formulario MVA se detecta mediante:
1. **Form ID**: `mva_form`
2. **Form Name**: `mva-form`
3. **Campo específico**: `case_accident_date`

### Normalización de Datos

La función realiza las siguientes normalizaciones:
- **Teléfono**: Elimina todos los caracteres no numéricos y corrige números de 11 dígitos que empiezan con "1"
- **Yes/No**: Normaliza a "Yes" o "No" (case-insensitive)
- **Zip Code**: Convierte a string
- **IP Address**: Captura automáticamente desde `$_SERVER['REMOTE_ADDR']`

### Archivos Modificados

1. **`formularios-elementor.php`**
   - Línea 6: Versión actualizada a 4.0.0
   - Línea 161: Variable `$is_mva_form` agregada
   - Líneas 177-182: Detección del formulario MVA
   - Línea 217: Verificación de formulario MVA
   - Líneas 233-238: Llamada a `send_mva_to_api()`

2. **`includes/forms-api.php`**
   - Líneas 247-303: Nueva función `send_mva_to_api()`
   - Mapeo completo de campos según Postman collection
   - Integración con `simple_api_call()` existente

## Configuración en Elementor

Para usar el formulario MVA en Elementor:

1. **Form ID/CSS ID**: `mva_form`
2. **Form Name**: `mva-form`
3. **Campos requeridos** (IDs):
   - `lead_first_name`
   - `lead_last_name`
   - `lead_email` o `lead_email_address`
   - `lead_phone`
   - `lead_state`
   - `lead_zip_code`
   - `case_physically_injured`
   - `case_accident_date`
   - `case_received_treatment`
   - `case_at_fault`
   - `case_attorney`
   - `case_notes` (opcional)

## Validaciones de la API

Según la colección de Postman, la API valida:

1. **case-physically-injured**: Solo acepta "Yes"
2. **case-accident-date**: Debe estar dentro de los últimos 2 años
3. **case-at-fault**: Solo acepta "No" (rechaza "Yes")
4. **case-attorney**: Solo acepta "No" (rechaza "Yes")

## Respuestas de la API

### Éxito (200)
```json
{
  "success": true,
  "code": 200,
  "message": "Successfully created a new submission.",
  "data": {
    "uuid": "...",
    "status": "pending",
    ...
  }
}
```

### Error (422)
```json
{
  "success": false,
  "code": 422,
  "message": "Only accidents that happened within a specific period are accepted.",
  "errors": {
    "case-accident-date": [
      "Only accidents that happened within a specific period are accepted."
    ]
  }
}
```

## Testing

Para probar la integración:

1. Activar modo de pruebas en el admin del plugin
2. Crear un formulario en Elementor con ID `mva_form`
3. Verificar logs en `log.txt`
4. Revisar que los datos se mapeen correctamente

## Próximos Pasos

- [ ] Crear formulario MVA en Elementor
- [ ] Probar en modo de pruebas
- [ ] Validar con datos reales
- [ ] Merge a `main` cuando esté probado

## Notas Adicionales

- El formulario usa la API v2 (igual que SSDI y Roblox V2)
- Formato JSON para el body (Content-Type: application/json)
- TrustedForm token se captura del campo oculto `xxTrustedFormToken`
- La función reutiliza `simple_api_call()` para consistencia con otros formularios
