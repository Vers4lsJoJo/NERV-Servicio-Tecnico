# NERV SERVICE CONTROL — Portal administrativo y portal de clientes

Aplicación web local para gestionar solicitudes y reparaciones técnicas. Tecnologías: HTML5, CSS3, JavaScript, PHP 8.1+ y MySQL/MariaDB (InnoDB). Diseñada para XAMPP.

## Áreas del sistema

### Administrador
- Inicia sesión desde `login.php` y accede a `index.php`.
- Revisa solicitudes de clientes en `solicitudes_admin.php`.
- Gestiona todas las reparaciones desde `administracion.php`: búsqueda, filtro por estado y acceso al detalle.
- Actualiza el diagnóstico y el estado desde `detalle_servicio.php`; los cambios se procesan en `actualizar_servicio.php`.
- Cada cambio de diagnóstico o estado queda registrado en `historial_servicios`, con fecha y administrador responsable.
- No se permite marcar como `REPARADO`, `NO REPARABLE` o `ENTREGADO` un servicio sin diagnóstico.

### Cliente
- Crea una cuenta desde `cliente_registro.php` e inicia sesión desde `cliente_login.php`.
- En `cliente/index.php` consulta únicamente sus propias solicitudes, diagnósticos y el historial de actualizaciones.
- En `cliente/solicitud.php` envía tipo de dispositivo, marca y descripción del daño.
- El sistema genera un número de servicio único y registra el estado inicial `RECIBIDO`.

## Instalación en el proyecto existente (XAMPP)
1. Haz una copia de seguridad de tu carpeta actual `C:\xampp\htdocs\NERV-Servicio-Tecnico\`.
2. Extrae el contenido de esta actualización dentro de esa carpeta y permite reemplazar los archivos existentes. Conserva tu `config/database.php` si contiene credenciales distintas.
3. Inicia Apache y MySQL en XAMPP.
4. No importes nuevamente `nerv_service.sql` ni la migración del portal: esta fase reutiliza las tablas existentes.
5. Administrador: `http://localhost/NERV-Servicio-Tecnico/login.php`.
6. Clientes: `http://localhost/NERV-Servicio-Tecnico/cliente_login.php` o `cliente_registro.php`.
7. Si `setup_admin.php` todavía existe en tu servidor, elimínalo por seguridad.

## Si ya instalaste la versión anterior
No vuelvas a importar el SQL completo sobre una base que ya contiene datos. En phpMyAdmin selecciona `nerv_service` e importa una sola vez `database/migracion_portal_clientes.sql`. Añade email, teléfono y contraseña a `clientes` sin borrar registros.

## Archivos principales
- `cliente_registro.php`, `cliente_login.php`: registro e inicio de sesión de clientes.
- `cliente/index.php`: panel privado del cliente.
- `cliente/solicitud.php`: creación de solicitudes.
- `solicitudes_admin.php`: bandeja de solicitudes enviadas por cuentas de clientes.
- `administracion.php`: buscador y filtro de todas las reparaciones.
- `detalle_servicio.php`: ficha de reparación, formulario de actualización e historial.
- `actualizar_servicio.php`: validación y guardado seguro de cambios con transacción MySQL.
- `includes/auth.php`: sesiones, permisos y tokens CSRF.
- `config/database.php`: conexión PDO.
- `database/nerv_service.sql`: esquema para instalación nueva.
- `database/migracion_portal_clientes.sql`: actualización de la base anterior.

## Actualización de esta fase
La gestión de diagnósticos y estados utiliza las tablas `servicios` e `historial_servicios` que ya están incluidas en el esquema de la Fase 02. No requiere importar un SQL nuevo ni modificar la base de datos existente.

## Seguridad y alcance
Las contraseñas se guardan con `password_hash`; las consultas usan PDO y parámetros; los formularios de registro, login y solicitud validan CSRF; las páginas de cliente filtran los servicios por el ID de sesión. Es un paquete de desarrollo local. Antes de publicarlo en Internet se requieren HTTPS, limitación de intentos, copias de seguridad y pruebas de seguridad.
