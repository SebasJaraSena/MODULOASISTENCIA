# Documentación de Arquitectura - Sistema de Asistencia

## Índice
1. [Visión General](#visión-general)
2. [Componentes del Sistema](#componentes-del-sistema)
3. [Flujo de Datos](#flujo-de-datos)
4. [Integración con Moodle](#integración-con-moodle)
5. [Detalles Técnicos](#detalles-técnicos)

## Visión General

El Sistema de Asistencia está construido como un plugin local de Moodle, siguiendo una arquitectura modular y basada en componentes. La arquitectura está diseñada para ser escalable, mantenible y segura.

## Componentes del Sistema

### 1. Frontend
- **Interfaz de Usuario**
  - Templates Mustache para vistas
  - Hojas de estilo CSS personalizadas
  - Componentes JavaScript AMD
  - Formularios interactivos

- **JavaScript (AMD)**
  - Módulo de asistencia (`attendance_observations.js`)
  - Módulo de vistas (`attendance_views.js`)
  - Gestión de eventos y validaciones

- **Templates**
  - Plantillas para lista de asistencia
  - Plantillas para reportes
  - Plantillas para configuración

### 2. Backend

#### Controladores
- `attendance.php`: Gestión principal de asistencia
- `index.php`: Punto de entrada y navegación
- `history.php`: Manejo de históricos

#### Servicios
- **Clase External**
  ```php
  class local_asistencia_external {
      // Servicios de estudiantes
      public static function fetch_students()
      // Servicios de reportes
      public static function fetch_attendance_report()
      // Servicios de actividades
      public static function fetch_activities_report()
  }
  ```

#### Modelos
- **Gestión de Datos**
  ```php
  class fetch_activities {
      // Métodos de acceso a datos
      public static function fetch_attendance_report()
      public static function fetch_activities_report()
  }
  ```

### 3. Core

#### Sistema de Caché
```php
// Implementación de caché
$cache = cache::make('local_asistencia', 'coursestudentslist');
$cache->set("course$courseid.user$userid", $condition);
```

#### Gestor de Sesiones
```php
// Manejo de sesiones
require_login();
global $USER;
$userid = $USER->id;
```

#### Validador
```php
// Validaciones implementadas
require_capability('local/asistencia:view', $context);
if (!has_capability('local/asistencia:edit', $context))
```

### 4. Base de Datos

#### Tablas Principales
- **local_asistencia**
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

- **local_asistencia_permanente**
  ```sql
  CREATE TABLE local_asistencia_permanente (
      id INT PRIMARY KEY,
      course_id INT,
      student_id INT,
      full_attendance JSON
  );
  ```

## Flujo de Datos

### 1. Registro de Asistencia
1. Usuario accede a la interfaz
2. Frontend envía petición HTTP
3. Controlador valida permisos
4. Servicio procesa la solicitud
5. Modelo actualiza la base de datos
6. Caché se actualiza
7. Respuesta retorna al usuario

### 2. Generación de Reportes
1. Solicitud de reporte
2. Validación de permisos
3. Consulta a base de datos
4. Procesamiento de datos
5. Generación de documento
6. Entrega al usuario

## Integración con Moodle

### 1. API de Moodle
- Uso de funciones core
- Integración con sistema de usuarios
- Acceso a contextos y roles

### 2. Hooks y Eventos
```php
// Ejemplo de evento
$event = \local_asistencia\event\attendance_updated::create([
    'contextid' => $contextid,
    'userid' => $userid
]);
$event->trigger();
```

## Detalles Técnicos

### 1. Seguridad
- Autenticación mediante Moodle
- Validación de capacidades
- Sanitización de datos
- Control de acceso por roles

### 2. Rendimiento
- Sistema de caché implementado
- Consultas optimizadas
- Carga diferida de recursos
- Paginación de resultados

### 3. Mantenibilidad
- Código modular
- Separación de responsabilidades
- Documentación inline
- Estándares de codificación Moodle

## Diagramas

Los diagramas detallados de la arquitectura se encuentran en:
- `Diagramas/arquitectura.puml`: Diagrama general de componentes
- `Diagramas/clases.puml`: Diagrama de clases
- `Diagramas/entidadRelacion.puml`: Diagrama de base de datos

## Consideraciones de Desarrollo

### 1. Extensibilidad
- Usar hooks para puntos de extensión
- Mantener interfaces consistentes
- Documentar APIs públicas

### 2. Testing
- Tests unitarios para componentes
- Tests de integración
- Cobertura de código

### 3. Despliegue
- Proceso de instalación documentado
- Scripts de migración
- Procedimientos de backup

---

**Nota**: Esta documentación debe mantenerse actualizada con cada cambio significativo en la arquitectura del sistema. 