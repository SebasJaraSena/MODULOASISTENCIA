# Dependencias y Librerías - Sistema de Asistencia

## Dependencias Core

### Moodle Core
- **Versión Mínima**: 2023100400
- **Componente**: `local_asistencia`
- **Tipo**: Plugin Local

## Librerías PHP

### Librerías Internas de Moodle
1. **Formularios**
   ```php
   require_once("$CFG->libdir/formslib.php")
   ```

2. **API Externa**
   ```php
   require_once("$CFG->libdir/externallib.php")
   ```

3. **Excel**
   ```php
   require_once($CFG->libdir . '/excellib.class.php')
   ```

4. **Formato de Datos**
   ```php
   require_once($CFG->libdir . '/classes/dataformat.php')
   ```

### Librerías Externas
1. **PDO**
   - Utilizado para conexiones a bases de datos externas
   - Drivers: PostgreSQL (`pgsql`)

## Módulos JavaScript

### AMD Modules
1. **Observaciones de Asistencia**
   ```javascript
   local_asistencia/attendance_observations
   ```

## Namespaces y Clases Utilizadas

### Core Moodle
```php
use core\dataformat;
use core\plugininfo\local;
use core_calendar\local\event\forms\create;
use core_php_time_limit;
use block_rss_client\output\item;
```

### Sistema de Caché
```php
$cache = cache::make('local_asistencia', 'coursestudentslist');
```
- Modo: `MODE_SESSION`
- TTL: 10 segundos
- Tipo: Simple Data (JSON serializable)

## Dependencias Frontend

### CSS
- Archivo: `styles/styles.css`
- Cargado dinámicamente con timestamp para caché busting

### Templates
- Sistema: Mustache
- Ubicación: `templates/`
- Templates principales:
  - `manage`
  - `error`

## Integraciones

### Base de Datos
1. **Moodle DB**
   - Tablas principales:
     - `local_asistencia`
     - `local_asistencia_config`
     - `local_asistencia_logs`

2. **Base de Datos Externa**
   - Tipo: PostgreSQL
   - Conexión: PDO
   - Configuración: Dinámica vía tabla `local_asistencia_config`

## Capacidades (Capabilities)

```php
$capabilities = [
    'local/asistencia:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE
    ],
    'local/asistencia:manage' => [
        'riskbitmask' => RISK_XSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ]
];
```

## Requisitos del Sistema

### PHP
- PDO Extension
- PostgreSQL Extension

### Base de Datos
- MySQL/MariaDB (Moodle)
- PostgreSQL (DB Externa)

### Servidor Web
- Soporte para sesiones PHP
- Soporte para caché

---
**Nota**: Este documento refleja las dependencias actualmente implementadas en el sistema. 