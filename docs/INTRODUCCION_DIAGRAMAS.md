# Introducción a los Diagramas del Sistema de Asistencia

## 1. Diagrama de Clases
En este diagrama se explicará la estructura del código del sistema, mostrando cómo las distintas clases se relacionan entre sí. Se podrá visualizar cómo la clase principal `local_asistencia_external` coordina las operaciones con las clases especializadas como `fetch_students` y `report_downloader`, permitiendo entender la arquitectura orientada a objetos del plugin.

## 2. Diagrama de Arquitectura
En este diagrama se explicará cómo están organizadas las diferentes capas del sistema y cómo interactúan entre ellas. Se mostrará el flujo de información desde la interfaz de usuario a través del backend hasta la base de datos, así como la integración con el núcleo de Moodle, permitiendo entender cómo el plugin se inserta en el ecosistema de la plataforma.

## 3. Diagrama de Entidad-Relación
En este diagrama se explicará cómo se organizan y relacionan los datos del sistema en la base de datos. Se mostrará la estructura que conecta usuarios, cursos y registros de asistencia, así como las entidades de configuración y logs, permitiendo entender cómo se almacena y mantiene la integridad de la información.

## 4. Diagrama de Casos de Uso
En este diagrama se explicará la interacción de los usuarios con el sistema desde un punto de vista funcional. Se mostrarán las diferentes acciones que pueden realizar instructores y administradores, como registrar asistencias o configurar el sistema, permitiendo entender los requisitos funcionales del plugin desde la perspectiva del usuario.

## 5. Diagrama de Secuencia
En este diagrama se explicará el orden cronológico de las interacciones entre los componentes del sistema durante procesos específicos. Se mostrará paso a paso cómo fluye la información durante el registro de asistencia o la generación de reportes, permitiendo entender la dinámica temporal del sistema en funcionamiento.

## 6. Diagrama de Estado
En este diagrama se explicará el ciclo de vida de los registros de asistencia dentro del sistema. Se mostrarán los diferentes estados por los que puede pasar un registro de asistencia y cómo se producen las transiciones entre ellos, permitiendo entender el comportamiento dinámico de los datos a lo largo del tiempo.

## 7. Diagrama de Historias de Usuario
En este diagrama se explicarán los requisitos del sistema desde la perspectiva de las necesidades de los usuarios. Se mostrarán las historias de usuario que definen las funcionalidades esperadas por instructores y administradores, permitiendo entender el valor que el plugin aporta a sus usuarios finales y las expectativas que debe cumplir. 