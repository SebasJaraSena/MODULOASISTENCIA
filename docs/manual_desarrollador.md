# Manual del Desarrollador - Sistema de Asistencia

## Índice
1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Base de Datos](#base-de-datos)
5. [Componentes Principales](#componentes-principales)
6. [Guía de Desarrollo](#guía-de-desarrollo)
7. [API y Servicios](#api-y-servicios)
8. [Pruebas](#pruebas)

## Introducción

El Sistema de Asistencia es una aplicación web desarrollada como plugin local para Moodle, diseñada para gestionar el registro y seguimiento de asistencia de estudiantes. Este manual proporciona la documentación técnica necesaria para desarrolladores que necesiten mantener o extender el sistema.

### Requisitos Técnicos
- PHP 7.4 o superior
- MySQL/MariaDB
- Moodle 3.9+
- Servidor web Apache/Nginx

## Arquitectura del Sistema

### Patrón de Diseño
El sistema sigue una arquitectura MVC (Modelo-Vista-Controlador) adaptada al framework de Moodle:
- **Modelos**: Ubicados en `classes/`
- **Vistas**: Templates en `templates/`
- **Controladores**: Archivos PHP en la raíz y `classes/external/`

### Flujo de Datos
1. Las solicitudes son manejadas por los controladores principales (`attendance.php`, `index.php`)
2. Los controladores utilizan clases externas para procesar la lógica de negocio
3. Los datos son almacenados/recuperados usando el API de DB de Moodle
4. Las vistas son renderizadas usando el sistema de templates Mustache

## Estructura del Proyecto

```
local/asistencia/
├── amd/                    # JavaScript modular
│   ├── src/               # Código fuente JS
│   └── build/             # JS compilado
├── classes/               # Clases PHP
│   ├── external/          # API externa
│   └── task/             # Tareas programadas
├── db/                    # Definiciones de BD
├── lang/                 # Archivos de idioma
├── styles/               # Hojas de estilo CSS
├── templates/            # Templates Mustache
├── tests/               # Pruebas unitarias
└── Diagramas/           # Documentación UML
```

## Base de Datos

### Tablas Principales

#### local_asistencia
```sql
CREATE TABLE local_asistencia (
    id INT PRIMARY KEY,
    courseid INT,
    studentid INT,
    teacherid INT,
    attendance TEXT,
    date DATE,
    observations TEXT,
    amounthours INT
);
```

#### local_asistencia_permanente
```sql
CREATE TABLE local_asistencia_permanente (
    id INT PRIMARY KEY,
    course_id INT,
    student_id INT,
    full_attendance JSON
);
```

### Relaciones y Constraints
- Las claves foráneas se relacionan con las tablas estándar de Moodle (`user`, `course`)
- Se mantiene integridad referencial mediante constraints

## Componentes Principales

### Sistema de Asistencia (attendance.php)
- Maneja el registro de asistencia diaria
- Implementa validaciones de entrada
- Gestiona permisos y roles

```php
class local_asistencia_external {
    public static function fetch_students($contextid, $courseid, $roleid, $offset, $limit, $condition) {
        // Implementación
    }
    
    public static function fetch_attendance_report($attendancehistory, $initialdate, $finaldate, $cumulous, $userid) {
        // Implementación
    }
}
```

### Gestión de Reportes
- Generación de reportes en CSV y PDF
- Filtrado por fechas y cursos
- Exportación de datos detallados

## Guía de Desarrollo

### Añadir Nuevas Funcionalidades
1. Crear nuevas clases en `classes/` siguiendo el estándar de Moodle
2. Implementar la lógica de negocio
3. Crear templates Mustache si se requiere UI
4. Actualizar lang strings
5. Añadir tests unitarios

### Estándares de Código
- Seguir [Moodle Coding Style](https://docs.moodle.org/dev/Coding_style)
- Documentar usando PHPDoc
- Mantener compatibilidad con versiones anteriores

### JavaScript
- Usar AMD (Asynchronous Module Definition)
- Compilar con `grunt amd` antes de desplegar
- Mantener separación de concerns

```javascript
define(['jquery'], function($) {
    return {
        init: function() {
            // Implementación
        }
    };
});
```

## API y Servicios

### API Externa
- Endpoints RESTful para integración
- Autenticación mediante tokens
- Documentación de endpoints disponibles

### Servicios Web
```php
class fetch_activities {
    public static function fetch_attendance_report() {
        // Implementación
    }
    
    public static function fetch_activities_report() {
        // Implementación
    }
}
```

## Pruebas

### Tests Unitarios
- Ubicados en `tests/`
- Ejecutar con PHPUnit
- Cobertura de código recomendada: >80%

### Tests de Integración
- Probar integración con Moodle
- Verificar permisos y roles
- Validar flujos de datos

### Depuración
- Usar debugging de Moodle
- Revisar logs del sistema
- Herramientas de profiling

## Mantenimiento

### Actualizaciones
1. Respaldar base de datos
2. Actualizar código fuente
3. Ejecutar upgrade.php
4. Verificar funcionalidad

### Resolución de Problemas
- Consultar logs de error
- Verificar permisos de archivos
- Validar configuración de BD

## Seguridad

### Mejores Prácticas
- Validar todas las entradas de usuario
- Usar prepared statements
- Implementar control de acceso
- Sanitizar salida HTML

### Permisos
- Definir capabilities en `db/access.php`
- Verificar roles y contextos
- Implementar checks de seguridad

## Contacto y Soporte

Para soporte técnico o consultas de desarrollo:
- Email: soporte@ejemplo.com
- Documentación: [URL del repositorio]
- Issues: Sistema de tickets

---

**Nota**: Este manual debe mantenerse actualizado con cada cambio significativo en el sistema. 