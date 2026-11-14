# NERV SERVICE CONTROL V6 — Portal administrativo y portal de clientes

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
4. Si actualizas desde V5, importa una sola vez `database/migracion_fase06_seguridad.sql`. No vuelvas a importar `nerv_service.sql` ni la migración del portal sobre una base que ya contiene datos.
5. Administrador: `http://localhost/NERV-Servicio-Tecnico/login.php`.
6. Clientes: `http://localhost/NERV-Servicio-Tecnico/cliente_login.php` o `cliente_registro.php`.
7. Si `setup_admin.php` todavía existe en tu servidor, elimínalo por seguridad.

## Si vienes de una versión anterior a la V5
Si tu base de datos todavía no tiene el portal de clientes, primero debes aplicar la migración `database/migracion_portal_clientes.sql` y después `database/migracion_fase06_seguridad.sql`. Si ya estás en V5, aplica únicamente la migración de Fase 06. No ejecutes migraciones repetidas.

## Archivos principales
- `cliente_registro.php`, `cliente_login.php`: registro e inicio de sesión de clientes.
- `cliente/index.php`: panel privado del cliente.
- `cliente/solicitud.php`: creación de solicitudes.
- `solicitudes_admin.php`: bandeja de solicitudes enviadas por cuentas de clientes.
- `administracion.php`: buscador y filtro de todas las reparaciones.
- `detalle_servicio.php`: ficha de reparación, formulario de actualización e historial.
- `actualizar_servicio.php`: validación y guardado seguro de cambios con transacción MySQL.
- `gestion_registros.php`, `eliminar_servicio.php`, `restaurar_servicio.php`: archivo reversible de servicios.
- `auditoria_registros.php`: bitácora de archivo y restauración.
- `generar_codigo_cliente.php`: emisión de códigos de activación para fichas de clientes anteriores.
- `includes/auth.php`: sesiones, permisos y tokens CSRF.
- `config/database.php`: conexión PDO.
- `database/nerv_service.sql`: esquema para instalación nueva.
- `database/migracion_portal_clientes.sql`: actualización de instalaciones anteriores al portal de clientes.
- `database/migracion_fase06_seguridad.sql`: actualización desde V5 para archivo reversible, auditoría y códigos de activación.

## Nota sobre la Fase 05
El registro, la consulta, los diagnósticos y los estados reutilizan las tablas `servicios` e `historial_servicios`. La Fase 05 no requiere migración adicional; la Fase 06 sí incorpora columnas de archivo y activación, además de una tabla de auditoría.

## Seguridad y alcance
Las contraseñas se guardan con `password_hash`; las consultas usan PDO y parámetros; los formularios que modifican datos validan CSRF; las páginas de cliente filtran los servicios por el ID de sesión. Las acciones de archivo y restauración se registran en una bitácora. Es un paquete de desarrollo local. Antes de publicarlo en Internet se requieren HTTPS, limitación de intentos, copias de seguridad y pruebas de seguridad.

## Fase 04 — Dashboard administrativo
El panel principal (`index.php`) incorpora un resumen dinámico de solicitudes totales, pendientes, en reparación y finalizadas; distribución de servicios por estado; actividad de recepción de los últimos seis meses; y una tabla con los siete servicios modificados más recientemente. Todos los indicadores se consultan desde las tablas existentes (`servicios` y `clientes`) al cargar el panel.

Esta actualización no requiere ejecutar scripts SQL ni modificar las tablas. Para instalarla, realiza una copia de seguridad de tu carpeta actual, reemplaza los archivos incluidos en el paquete y conserva `config/database.php` si tus credenciales son distintas. Inicia Apache y MySQL y abre `http://localhost/NERV-Servicio-Tecnico/login.php`.

**Criterios de los indicadores:**
- Pendientes: `RECIBIDO`, `EN DIAGNOSTICO` y `ESPERANDO APROBACION`.
- En proceso: `EN REPARACION`.
- Finalizados: `REPARADO`, `NO REPARABLE` y `ENTREGADO`.
- Actividad mensual: solicitudes agrupadas por `fecha_recepcion` en los seis meses mostrados.
- Actividad reciente: servicios ordenados por `actualizado_en`.

Los gráficos y cifras se actualizan al volver a cargar el panel; no representan una conexión en tiempo real ni se actualizan automáticamente mientras la página permanece abierta.

## Fase 05 — Registro y consulta de servicios
- `registro.php` permite al administrador crear una orden con datos del cliente, tipo de dispositivo, marca y descripción de la falla. El sistema asigna un código `NSC-AAAAMMDD-XXXXXXXX`, inicia el estado `RECIBIDO` y crea el primer movimiento del historial dentro de una transacción.
- Si el correo ingresado ya pertenece a un cliente, el servicio se vincula a ese registro; si no existe, se crea una ficha básica de cliente. El correo es opcional.
- `consulta.php` permite localizar una orden por su número y consultar cliente, equipo, fechas, falla reportada, diagnóstico, estado e historial. Desde el resultado se puede abrir la ficha administrativa para gestionar la reparación.
- Si administración creó previamente la ficha del cliente con un correo, `cliente_registro.php` permite que esa persona active su cuenta utilizando el mismo correo, sin duplicar el registro ni perder sus órdenes.

Esta fase reutiliza las tablas existentes y no requiere ejecutar una migración SQL. Se recomienda probar el registro con un correo nuevo, con un correo ya asociado a una cuenta y sin correo; luego buscar cada orden por su código.


## Fase 06 — Gestión segura de registros
- Se incorpora `gestion_registros.php` para consultar registros activos y archivados.
- El archivo lógico se ejecuta mediante `eliminar_servicio.php` (POST + CSRF) y exige un motivo. No elimina físicamente el servicio ni sus movimientos históricos.
- `restaurar_servicio.php` permite devolver un registro archivado a los módulos activos.
- `auditoria_registros.php` conserva quién archivó o restauró cada servicio, cuándo y el motivo asociado.
- Los módulos normales excluyen automáticamente los registros archivados; el dashboard y el portal del cliente no los cuentan ni muestran.
- La opción de gestión de registros se añadió al COMMAND CENTER.

## Fase 07 — Seguridad y validación
- Se endureció la configuración de cookies y sesiones, se mantuvo la regeneración de ID al autenticar y se cambió el cierre de sesión a POST con CSRF.
- Los formularios administrativos y de clientes mantienen validación CSRF y consultas preparadas.
- Los servicios archivados no pueden consultarse ni modificarse desde los módulos operativos.
- La bandeja `solicitudes_admin.php` distingue ahora las solicitudes creadas desde el portal (`creado_por IS NULL`) de las órdenes creadas directamente por administración.
- Para evitar que cualquier persona que conozca el correo de un cliente active una ficha creada por el personal, los registros administrativos con correo generan un código aleatorio de un solo uso, guardado como hash y válido durante siete días. El código se muestra al personal una sola vez al registrar el servicio. Para fichas V5 anteriores, el administrador puede emitirlo desde el detalle del servicio mediante `generar_codigo_cliente.php`.
- La longitud de texto funciona incluso si la extensión PHP `mbstring` no está habilitada.

## Migración requerida para actualizar desde V5
1. Haz una copia de seguridad de la carpeta del proyecto y exporta la base de datos `nerv_service` desde phpMyAdmin.
2. Actualiza los archivos del proyecto con esta versión.
3. En phpMyAdmin selecciona la base de datos `nerv_service` e importa **una sola vez** `database/migracion_fase06_seguridad.sql`.
4. No vuelvas a importar `nerv_service.sql` sobre una base de datos que ya contiene información.
5. Si instalas desde cero, usa `database/nerv_service.sql`, que ya incluye las columnas de esta fase.

## Limitaciones y verificaciones
- El archivo lógico conserva datos e historial, pero no sustituye una política de copias de seguridad.
- La activación por código protege las fichas de cliente creadas previamente por administración. El registro público de cuentas nuevas no realiza verificación automática de correo electrónico; para un despliegue público se recomienda integrar correo transaccional y verificación de titularidad.
- Se requiere probar las consultas y la migración en el MySQL/MariaDB de XAMPP. La revisión estática de PHP no sustituye una prueba real de integración.
- Antes de publicar en Internet se recomienda HTTPS, limitación de intentos de acceso, verificación de correo, política de respaldo y auditoría de seguridad independiente.
