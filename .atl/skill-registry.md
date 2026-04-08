# Skill Registry - SenAttend

## Proposito
Este archivo documenta las reglas, convenciones y configuraciones obligatorias del proyecto SenAttend.
**LEER ANTES de cualquier modificacion de codigo.**

---

## Reglas Obligatorias

### Sin Iconos ni Emojis
- **PROHIBIDO** usar iconos/emoji en:
  - Codigo fuente (`.js`, `.php`, `.html`, `.css`)
  - Documentacion (`.md`)
  - Comentarios de codigo
  - Respuestas del chat

### Sin Codigo Inline
- **PROHIBIDO** en HTML/PHP:
  - `<style>` blocks
  - `<script>` inline
  - Atributos `style=`
  - Atributos `onclick=`, `onchange=`, etc.
- **PROHIBIDO** en JS:
  - Strings de estilos inyectados

### Maximo 200 Lineas por Archivo
- Archivos `.js` no deben exceder 200 lineas
- Archivos PHP/logica deben mantenerse pequenos
- **Solucion**: Si crece, extraer a nuevo modulo/archivo

---

## Principios SOLID (Obligatorios)

| Letra | Principio | Significado |
|-------|-----------|-------------|
| **S** | Single Responsibility | Cada clase/archivo hace UNA cosa |
| **O** | Open/Closed | Abierto para extension, cerrado para modificacion |
| **L** | Liskov Substitution | Subclases pueden reemplazar a sus padres |
| **I** | Interface Segregation | Preferir interfaces pequenas |
| **D** | Dependency Inversion | Depender de abstracciones, no concreciones |

---

## Estructura de Archivos

### CSS
- Ubicacion: `/css/` o `/public/css/`
- Archivos dedicados por modulo
- **Nunca** inline o en `<style>`

### JavaScript
- Ubicacion: `/js/`, `/public/assets/js/`, o `/public/js/`
- Archivos dedicados por modulo
- **Nunca** inline o en `<script>` embebido

### Vistas (HTML/PHP)
- Solo referencian via `<link>` y `<script src>`
- NUNCA estilos/scripts inline

---

## Seguridad

### XSS Prevention
```php
// CORRECTO
echo htmlspecialchars($userInput, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// INCORRECTO (PROHIBIDO)
echo $userInput;
```

---

## PWA / Service Worker

### Flash Messages
- Requieren header `Cache-Control: no-cache` para funcionar
- Sin esto, el navegador cachea y no muestra mensajes

### Cache Invalidation
- Despues de POST/PUT/DELETE, invalidar cache
- Si la UI no refleja cambios, verificar estrategia de cache

### Data Attributes
- Usar `data-*` en HTML para pasar datos a JS
- Event listeners en archivos JS dedicados, NO `onclick`

---

## Arquitectura del Proyecto

### Stack
- PHP MVC (custom)
- SQL (MySQL)
- JavaScript vanilla / PWA
- CSS vanilla

### Estructura de Directorios
```
senattend/
  public/
    index.php        # Router (registro manual de controllers)
    js/              # JavaScript
    css/             # CSS
  src/
    Controllers/     # Logica de controladores
    Repositories/    # Acceso a datos
    Models/          # Modelos de datos
    Support/         # Helpers/utilidades
  views/             # Vistas PHP
  database/
    migrations/      # Migraciones SQL
  config/            # Configuracion
```

### Registro de Controllers
Cualquier controller nuevo **debe** registrarse en `public/index.php`:
```php
// Agregar aqui:
$router->get('/nueva-ruta', [NuevoController::class, 'metodo']);
```

---

## Patrones Comunes

### AJAX Response Format
```json
{
  "success": true,
  "message": "Operacion exitosa",
  "errors": []
}
```

### Soft Delete Pattern
```php
// Query con soft delete:
$results = $db->query("SELECT * FROM tabla WHERE soft_delete IS NULL");

// Para restaurar:
$db->query("UPDATE tabla SET soft_delete = NULL WHERE id = ?");
```

---

## SDD (Spec-Driven Development)

Para cambios sustanciales, usar SDD:
1. `sdd-explore` - Investigar idea
2. `sdd-propose` - Proponer cambio
3. `sdd-spec` - Escribir especificaciones
4. `sdd-design` - Diseno tecnico
5. `sdd-tasks` - Lista de tareas
6. `sdd-apply` - Implementar
7. `sdd-verify` - Verificar
8. `sdd-archive` - Archivar

---

## Consultar Este Archivo
- **Busqueda rapida**: `mem_search(query: "configuracion proyecto")` en Engram
- **Archivo local**: `.atl/skill-registry.md`
- **Memoria maestra**: Engram topic `config/master`

---

Ultima actualizacion: 2026-04-08
