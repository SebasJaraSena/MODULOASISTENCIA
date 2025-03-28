# Índice de Diagramas - Sistema de Asistencia

## Introducción

Este documento sirve como guía de referencia para todos los diagramas generados para el Sistema de Asistencia de Moodle. Cada diagrama tiene un propósito específico que contribuye a la comprensión de diferentes aspectos del sistema, desde su estructura hasta su comportamiento.

## Diagramas Disponibles

### 1. Diagrama de Clases (Diagramas/clases.puml)
- **Propósito**: Muestra la estructura estática del sistema, definiendo las clases, atributos, métodos y relaciones entre objetos.
- **Uso**: Sirve como referencia para desarrolladores para entender la arquitectura del sistema y las responsabilidades de cada componente.
- **Elementos clave**: Clases como `fetch_students`, `foreing_db_connection`, y sus relaciones.

### 2. Diagrama de Arquitectura (Diagramas/arquitectura.puml)
- **Propósito**: Representa la estructura general del sistema y la interacción entre sus componentes principales.
- **Uso**: Proporciona una visión de alto nivel de cómo se organizan los módulos y cómo interactúan entre sí.
- **Elementos clave**: Capas de Frontend, Backend, Core y Base de Datos, junto con sus interacciones.

### 3. Diagrama de Entidad-Relación 
- **Propósito**: Visualiza la estructura de la base de datos, mostrando entidades, atributos y relaciones.
- **Uso**: Guía para entender el modelo de datos y para implementaciones o modificaciones en la base de datos.
- **Elementos clave**: Entidades como `Usuario`, `Curso`, `Asistencia` y sus relaciones.

### 4. Diagrama de Casos de Uso
- **Propósito**: Muestra las interacciones entre actores (usuarios) y el sistema, representando las funcionalidades.
- **Uso**: Ayuda a comprender los requisitos funcionales desde la perspectiva del usuario.
- **Elementos clave**: Actores como Instructor y Administrador, y casos de uso como "Registrar Asistencia" y "Generar Reportes".

### 5. Diagrama de Secuencia
- **Propósito**: Ilustra la interacción entre objetos en orden temporal, mostrando mensajes y secuencias de operaciones.
- **Uso**: Facilita la comprensión de cómo los objetos colaboran para realizar funciones específicas.
- **Elementos clave**: Secuencias para procesos como "Registrar Asistencia" y "Generar Reporte".

### 6. Diagrama de Estado
- **Propósito**: Muestra los diferentes estados por los que pasa un objeto durante su ciclo de vida.
- **Uso**: Permite comprender las transiciones de estado y comportamientos en respuesta a eventos.
- **Elementos clave**: Estados de asistencia como "Pendiente", "Registrado", "Aprobado" y sus transiciones.

### 7. Diagrama de Historias de Usuario
- **Propósito**: Representa los requisitos desde la perspectiva del usuario en formato narrativo.
- **Uso**: Facilita la comprensión de las necesidades del usuario y las funcionalidades esperadas.
- **Elementos clave**: Historias como "Como instructor, quiero registrar asistencia para realizar seguimiento".

## Uso de los Diagramas

### Para Desarrolladores
- El **Diagrama de Clases** y el **Diagrama de Arquitectura** son fundamentales para entender la estructura del código.
- El **Diagrama de Entidad-Relación** es esencial para trabajar con la base de datos.
- Los **Diagramas de Secuencia** ayudan a comprender los flujos de proceso.

### Para Analistas
- Los **Casos de Uso** e **Historias de Usuario** son útiles para validar requisitos.
- El **Diagrama de Estado** ayuda a verificar el comportamiento del sistema.

### Para Gestores
- El **Diagrama de Arquitectura** proporciona una visión general del sistema.
- Las **Historias de Usuario** muestran el valor para el cliente.

## Localización de Diagramas

| Diagrama | Ubicación | Formato |
|----------|-----------|---------|
| Diagrama de Clases | Diagramas/clases.puml | PlantUML |
| Diagrama de Arquitectura | Diagramas/arquitectura.puml | PlantUML |
| Diagrama Entidad-Relación | Diagramas/er.puml | PlantUML |
| Diagrama de Casos de Uso | Diagramas/casos_uso.puml | PlantUML |
| Diagrama de Secuencia | Diagramas/secuencia.puml | PlantUML |
| Diagrama de Estado | Diagramas/estado.puml | PlantUML |
| Historias de Usuario | Diagramas/historias.puml | PlantUML |

## Mantenimiento y Actualización

Los diagramas deben mantenerse actualizados con cada cambio significativo en el sistema. El procedimiento recomendado incluye:

1. Identificar qué diagramas se ven afectados por el cambio
2. Actualizar los diagramas correspondientes
3. Documentar la actualización en el registro de cambios
4. Revisar la consistencia entre diagramas relacionados

---
**Nota**: Este documento sirve como punto de entrada para entender los diferentes diagramas del sistema y su propósito. Para cada diagrama específico, consulte el archivo correspondiente para obtener información detallada. 