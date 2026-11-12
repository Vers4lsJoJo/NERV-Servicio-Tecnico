# NERV SERVICE CONTROL — Portal administrativo y portal de clientes

Aplicación web local para gestionar solicitudes y reparaciones técnicas. Tecnologías: HTML5, CSS3, JavaScript, PHP 8.1+ y MySQL/MariaDB (InnoDB). Diseñada para XAMPP.

## Áreas del sistema

### Administrador
- Inicia sesión desde `login.php` y accede a `index.php`.
- Revisa solicitudes de clientes en `solicitudes_admin.php`.
- Se mantienen los módulos de registro, consulta y administración técnica. La edición completa de diagnósticos y estados sigue pendiente de la siguiente fase.

### Cliente
- Crea una cuenta desde `cliente_registro.php` e inicia sesión desde `cliente_login.php`.
- En `cliente/index.php` consulta únicamente sus propias solicitudes.
- En `cliente/solicitud.php` envía tipo de dispositivo, marca y descripción del daño.
- El sistema genera un número de servicio único y registra el estado inicial `RECIBIDO`.

## Instalación nueva en XAMPP
1. Copia la carpeta `NERV-SERVICE-CONTROL` dentro de `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL.
3. Abre `http://localhost/phpmyadmin` e importa `database/nerv_service.sql`.
4. Revisa `config/database.php` y ajusta las credenciales MySQL si es necesario.
5. Abre `http://localhost/NERV-SERVICE-CONTROL/setup_admin.php` y crea el administrador (contraseña de mínimo 10 caracteres).
6. Elimina `setup_admin.php` del servidor después de crear la cuenta.
7. Administrador: `http://localhost/NERV-SERVICE-CONTROL/login.php`.
8. Clientes: `http://localhost/NERV-SERVICE-CONTROL/cliente_registro.php` o `cliente_login.php`.

## Si ya instalaste la versión anterior
No vuelvas a importar el SQL completo sobre una base que ya contiene datos. En phpMyAdmin selecciona `nerv_service` e importa una sola vez `database/migracion_portal_clientes.sql`. Añade email, teléfono y contraseña a `clientes` sin borrar registros.

## Archivos principales
- `cliente_registro.php`, `cliente_login.php`: registro e inicio de sesión de clientes.
- `cliente/index.php`: panel privado del cliente.
- `cliente/solicitud.php`: creación de solicitudes.
- `solicitudes_admin.php`: bandeja administrativa de solicitudes.
- `includes/auth.php`: sesiones, permisos y tokens CSRF.
- `config/database.php`: conexión PDO.
- `database/nerv_service.sql`: esquema para instalación nueva.
- `database/migracion_portal_clientes.sql`: actualización de la base anterior.

## Seguridad y alcance
Las contraseñas se guardan con `password_hash`; las consultas usan PDO y parámetros; los formularios de registro, login y solicitud validan CSRF; las páginas de cliente filtran los servicios por el ID de sesión. Es un paquete de desarrollo local. Antes de publicarlo en Internet se requieren HTTPS, limitación de intentos, copias de seguridad y pruebas de seguridad.
