# Políticas de Seguridad - Sistema de Asistencia

## Índice
1. [Introducción](#introducción)
2. [Control de Acceso](#control-de-acceso)
3. [Validación de Datos](#validación-de-datos)
4. [Seguridad en Base de Datos](#seguridad-en-base-de-datos)
5. [Protección de Sesiones](#protección-de-sesiones)
6. [Auditoría y Logging](#auditoría-y-logging)
7. [Respuesta a Incidentes](#respuesta-a-incidentes)

## Introducción

Este documento define las políticas y procedimientos de seguridad implementados en el plugin de asistencia para Moodle. Estas políticas son obligatorias y deben ser seguidas por todos los desarrolladores y administradores del sistema.

## Control de Acceso

### 1. Autenticación
```php
// Implementado en attendance.php
require_login(); // Obligatorio en todos los puntos de entrada

// Verificación de capacidades
require_capability('local/asistencia:view', $context);
```

### 2. Roles y Permisos
```php
// Definido en db/access.php
$capabilities = [
    'local/asistencia:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],
    'local/asistencia:edit' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ]
    ]
];
```

### 3. Validación de Contexto
```php
public static function validate_context($contextid) {
    // Verificar que el contexto existe y es válido
    $context = context::instance_by_id($contextid);
    self::validate_context($context);
    
    // Verificar permisos en el contexto
    if (!has_capability('local/asistencia:view', $context)) {
        throw new required_capability_exception($context, 'local/asistencia:view', 'nopermissions', 'local_asistencia');
    }
}
```

## Validación de Datos

### 1. Entrada de Usuario
```php
class input_validator {
    public static function validate_attendance_data($data) {
        // Validar formato de fecha
        if (!self::is_valid_date($data['date'])) {
            throw new invalid_parameter_exception('Formato de fecha inválido');
        }
        
        // Validar estado de asistencia
        if (!in_array($data['attendance'], [-1, 0, 1, 2, 3, -8])) {
            throw new invalid_parameter_exception('Estado de asistencia inválido');
        }
        
        // Validar horas
        if ($data['amounthours'] < 0 || $data['amounthours'] > 24) {
            throw new invalid_parameter_exception('Cantidad de horas inválida');
        }
        
        // Sanitizar observaciones
        $data['observations'] = clean_param($data['observations'], PARAM_TEXT);
        
        return $data;
    }
}
```

### 2. Sanitización de Salida
```php
// En templates y salida HTML
$output = format_text($data, FORMAT_HTML, ['trusted' => false]);
echo clean_text($output);
```

## Seguridad en Base de Datos

### 1. Prevención de SQL Injection
```php
public static function get_attendance_records($courseid, $studentid) {
    global $DB;
    
    // Usar parámetros nombrados
    $params = [
        'courseid' => $courseid,
        'studentid' => $studentid
    ];
    
    // Usar API de Moodle para consultas
    return $DB->get_records('local_asistencia', $params);
}
```

### 2. Transacciones
```php
public static function save_attendance_batch($records) {
    global $DB;
    
    try {
        $transaction = $DB->start_delegated_transaction();
        
        foreach ($records as $record) {
            self::validate_attendance_data($record);
            $DB->insert_record('local_asistencia', $record);
        }
        
        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
    }
}
```

## Protección de Sesiones

### 1. Manejo de Sesiones
```php
// Implementado en cada archivo PHP
require_sesskey(); // Para operaciones POST

// Validación en formularios
$mform->addElement('hidden', 'sesskey', sesskey());
```

### 2. Prevención de CSRF
```php
// En formularios
$url = new moodle_url('/local/asistencia/attendance.php');
$mform = new attendance_form($url);

// Validación
if ($fromform = $mform->get_data()) {
    require_sesskey();
    // Procesar datos
}
```

## Auditoría y Logging

### 1. Registro de Eventos
```php
class attendance_logger {
    public static function log_attendance_change($courseid, $userid, $action) {
        $event = \local_asistencia\event\attendance_updated::create([
            'contextid' => context_course::instance($courseid)->id,
            'userid' => $userid,
            'other' => [
                'action' => $action
            ]
        ]);
        $event->trigger();
    }
}
```

### 2. Monitoreo de Actividades
```php
public static function monitor_suspicious_activity($userid) {
    $attempts = get_user_preferences('local_asistencia_failed_attempts', 0, $userid);
    
    if ($attempts > 5) {
        self::notify_admin("Actividad sospechosa detectada para el usuario $userid");
        self::block_temporary_access($userid);
    }
}
```

## Respuesta a Incidentes

### 1. Manejo de Errores
```php
public static function handle_security_incident($error, $context) {
    // Registrar incidente
    debugging('[Security Alert] ' . $error->getMessage(), DEBUG_DEVELOPER);
    
    // Notificar administradores
    $admins = get_admins();
    foreach ($admins as $admin) {
        $message = new \core\message\message();
        $message->component = 'local_asistencia';
        $message->name = 'security_incident';
        $message->userfrom = get_admin();
        $message->userto = $admin;
        $message->subject = 'Alerta de Seguridad';
        $message->fullmessage = $error->getMessage();
        message_send($message);
    }
}
```

### 2. Plan de Recuperación
```php
public static function emergency_recovery($courseid) {
    global $DB;
    
    // Backup de datos críticos
    $records = $DB->get_records('local_asistencia', ['courseid' => $courseid]);
    self::create_emergency_backup($records);
    
    // Restaurar último estado válido
    $lastValidState = self::get_last_valid_state($courseid);
    if ($lastValidState) {
        self::restore_attendance_state($lastValidState);
    }
}
```

## Recomendaciones de Implementación

1. **Actualizaciones de Seguridad**
   - Mantener Moodle actualizado
   - Revisar regularmente las dependencias
   - Aplicar parches de seguridad

2. **Configuración Segura**
   - Usar HTTPS
   - Configurar headers de seguridad
   - Implementar políticas de contraseñas fuertes

3. **Monitoreo Continuo**
   - Revisar logs regularmente
   - Configurar alertas automáticas
   - Realizar auditorías periódicas

## Checklist de Seguridad

- [ ] Validación de entrada en todos los formularios
- [ ] Sanitización de salida en todas las vistas
- [ ] Control de acceso basado en roles
- [ ] Protección contra CSRF
- [ ] Logging de eventos críticos
- [ ] Manejo seguro de sesiones
- [ ] Prevención de SQL Injection
- [ ] Backup regular de datos

## Contacto de Seguridad

Para reportar vulnerabilidades de seguridad:
- Email: security@ejemplo.com
- Teléfono de emergencia: +XX XXX XXX XXX
- Sistema de tickets: [URL]

---

**Nota**: Este documento debe ser revisado y actualizado regularmente para mantener su efectividad y relevancia. 