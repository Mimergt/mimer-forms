# 🚀 Mimer Forms VDI Integration

**Versión:** 2.9.1
**Descripción:** Sistema de integración para formularios de Elementor con validación avanzada, detección automática de casos y envío a API externa (ValueDirectInc).

## 📋 Funcionalidad Principal

Este plugin intercepta los envíos de formularios de Elementor Pro, valida los datos, y los envía a diferentes endpoints API según el tipo de caso legal detectado.

### 🔍 Detección Automática de Casos
El sistema detecta automáticamente el tipo de formulario basándose en la presencia de campos clave:

| Tipo de Caso | Campos Clave (Detection Triggers) | API Endpoint (Interno) |
| :--- | :--- | :--- |
| **Depo Provera (V1)** | `case_depo_provera_taken` | `ir-lca-depo-post` |
| **Depo Provera (V2)** | `case_depo_provera_taken` + ID `dp_formv2` | `zm-ir-lca-depo-post` |
| **RoundUp** | `case_exposed` | `ir-lca-roundup-post` |
| **Roblox (V1)** | `case_abuse_type`, `case_interaction` | `vdi-fb-linkout-ir` |
| **Roblox (V2)** | `roblox_formV2` (hidden) | `vdi-lca-bfire-ir` |

---

## 🛠️ Componentes del Sistema

### 1. `formularios-elementor.php` (Core)
- **Hook Principal:** `elementor_pro/forms/validation`
- **Función:** Intercepta el envío, ejecuta `MimerFormValidation`, determina el tipo de caso y llama a la función de envío API correspondiente.
- **Auto-Redirect:** Maneja shortcodes para redireccionar al usuario a páginas de "Thank You" o "Rejected" basándose en la respuesta del API.

### 2. `includes/forms-api.php` (API Handler)
Contiene la lógica de envío (`cURL`) para cada tipo de formulario.
- **Mapeo de Campos:** Transforma los nombres de campos de Elementor a los requeridos por el API (ej. `case_attorney` → `case-attorney` ["Yes"/"No"]).
- **Headers & Auth:** Inyecta las firmas (`signature`) y parámetros de usuario necesarios para cada endpoint.
- **Prevención de Duplicados:** Usa hashes MD5 de los payloads en sesión para evitar envíos dobles accidentales.

### 3. `includes/form-validation.php` & `.js` (Validación)
- **Validación PHP:** Verifica reglas de negocio básicas antes de enviar.
- **Validación JS:** Provee validación en tiempo real en el navegador (feedback visual al usuario).
- **NumVerify:** Integración (opcional/configurable) para limpiar y validar números de teléfono.

### 4. `includes/select2-handler.php` (UX)
Mejora la experiencia de usuario convirtiendo selectores nativos en componentes `Select2` buscables.

---

## 🚀 Instalación y Configuración

1. **Activar el Plugin:** Subir a `wp-content/plugins` y activar.
2. **Dependencias:** Requiere Elementor Pro.
3. **Configuración (Admin):**
   - Panel disponible en WP Admin (si aplica).
   - Variables críticas (URLs, Signatures) están definidas en `includes/forms-api.php`.

## 🔄 Flujo de Redirección (Auto-Redirect)

El plugin **no** usa la redirección nativa de Elementor. En su lugar:
1. El formulario se envía vía AJAX.
2. El API responde con `accepted` (true/false) y una URL.
3. El plugin guarda esta URL en `$_SESSION['mimer_api_redirect_url']`.
4. El usuario llega a una página genérica de agradecimiento que **DEBE** contener el shortcode:
   
   ```shortcode
   [mimer_auto_redirect]
   ```
   
   *Este shortcode lee la sesión y redirige al usuario a la URL final correspondiente.*

## 🐛 Debugging

El plugin genera un archivo `log.txt` en la raíz del directorio del plugin con detalles de cada transacción:
- Payloads enviados.
- Respuestas del API.
- Errores de validación.

*Nota: Este archivo se excluye del repositorio por seguridad.*
