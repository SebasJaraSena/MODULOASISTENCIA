# Contenido de los Diagramas

## 1. Diagrama de Clases (Diagramas/clases.puml)
- Clase `fetch_students` con métodos para gestión de estudiantes y validación 
- Clase `fetch_activities` para obtención de actividades
- Clase `foreing_db_connection` para conexión a base de datos externa
- Clase `report_downloader` para exportación de reportes
- Clase `local_asistencia_external` como interfaz principal
- Relaciones de herencia y dependencia entre clases
- Atributos y métodos principales de cada clase

## 2. Diagrama de Arquitectura (Diagramas/arquitectura.puml)
- Capa de Frontend: Interfaz de usuario, templates Mustache y JS (AMD)
- Capa de Backend: Controladores, servicios y modelos
- Componentes Core: Caché, validación y autenticación
- Base de datos: Tablas de asistencia y configuración
- Flujos de comunicación entre capas
- Interacción con la API de Moodle

## 3. Diagrama de Entidad-Relación
- Entidad `Usuario` con atributos de identificación
- Entidad `Curso` con información de cursos
- Entidad `Asistencia` con fechas y estados
- Entidad `Configuración` para parámetros del sistema
- Entidad `Logs` para registro de eventos
- Relaciones entre entidades y cardinalidad
- Atributos primarios y foráneos

## 4. Diagrama de Casos de Uso
- Actor Instructor y sus interacciones:
  - Registrar asistencia diaria
  - Consultar asistencia histórica
  - Generar reportes de asistencia
- Actor Administrador y sus interacciones:
  - Configurar parámetros del sistema
  - Gestionar permisos
  - Acceder a reportes globales
- Relaciones de inclusión y extensión

## 5. Diagrama de Secuencia
- Secuencia "Registro de Asistencia":
  - Interacción usuario-sistema
  - Validación de datos
  - Almacenamiento en base de datos
  - Confirmación al usuario
- Secuencia "Generación de Reportes":
  - Solicitud de reporte
  - Consulta de datos
  - Procesamiento
  - Entrega de resultado

## 6. Diagrama de Estado
- Estados de Asistencia:
  - Pendiente
  - Registrado
  - Validado
  - Cerrado
- Transiciones entre estados
- Eventos que provocan transiciones
- Condiciones para cambios de estado

## 7. Diagrama de Historias de Usuario
- Historia "Como instructor quiero registrar asistencia diaria"
- Historia "Como instructor quiero generar reportes de asistencia"
- Historia "Como administrador quiero configurar el sistema"
- Historia "Como instructor quiero consultar histórico de asistencia"
- Criterios de aceptación para cada historia
- Prioridades asociadas 