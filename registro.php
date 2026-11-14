<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$error = '';
$numeroCreado = '';
$codigoActivacion = '';
if (isset($_GET['registrado']) && !empty($_SESSION['flash_registro']) && is_array($_SESSION['flash_registro'])) {
    $numeroCreado = (string)($_SESSION['flash_registro']['numero'] ?? '');
    $codigoActivacion = (string)($_SESSION['flash_registro']['codigo'] ?? '');
    unset($_SESSION['flash_registro']);
}
$tipos = ['Computador de escritorio', 'Portátil', 'Tablet', 'Teléfono móvil', 'Consola', 'Otro dispositivo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre = trim((string)($_POST['nombre_cliente'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $tipo = trim((string)($_POST['tipo_dispositivo'] ?? ''));
    $marca = trim((string)($_POST['marca'] ?? ''));
    $descripcion = trim((string)($_POST['descripcion_dano'] ?? ''));

    if ($nombre === '' || text_length($nombre) > 120 || !in_array($tipo, $tipos, true) || $marca === '' || text_length($marca) > 80 || text_length($descripcion) < 10 || text_length($descripcion) > 5000 || text_length($telefono) > 30) {
        $error = 'Completa los campos obligatorios y describe el daño con al menos 10 caracteres.';
    } elseif ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || text_length($email) > 190)) {
        $error = 'Ingresa un correo electrónico válido o deja el campo vacío.';
    } else {
        try {
            $pdo->beginTransaction();
            $idCliente = null;

            // Si el correo ya existe, se reutiliza el registro del cliente.
            if ($email !== '') {
                $buscarCliente = $pdo->prepare('SELECT id_cliente,password_hash FROM clientes WHERE email = :email LIMIT 1 FOR UPDATE');
                $buscarCliente->execute([':email' => $email]);
                $clienteExistente = $buscarCliente->fetch();
                if ($clienteExistente) {
                    $idCliente = (int)$clienteExistente['id_cliente'];
                    if (empty($clienteExistente['password_hash'])) {
                        $codigoActivacion = strtoupper(bin2hex(random_bytes(12)));
                        $actualizarCliente = $pdo->prepare('UPDATE clientes SET nombre=:nombre, telefono=:telefono, codigo_activacion_hash=:hash, codigo_activacion_expira=DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id_cliente=:id');
                        $actualizarCliente->execute([
                            ':nombre' => $nombre,
                            ':telefono' => $telefono !== '' ? $telefono : null,
                            ':hash' => hash('sha256', $codigoActivacion),
                            ':id' => $idCliente,
                        ]);
                    }
                }
            }

            if ($idCliente === null) {
                if ($email !== '') {
                    $codigoActivacion = strtoupper(bin2hex(random_bytes(12)));
                    $crearCliente = $pdo->prepare('INSERT INTO clientes(nombre,email,telefono,codigo_activacion_hash,codigo_activacion_expira) VALUES(:nombre,:email,:telefono,:hash,DATE_ADD(NOW(), INTERVAL 7 DAY))');
                    $crearCliente->execute([
                        ':nombre' => $nombre,
                        ':email' => $email,
                        ':telefono' => $telefono !== '' ? $telefono : null,
                        ':hash' => hash('sha256', $codigoActivacion),
                    ]);
                } else {
                    $crearCliente = $pdo->prepare('INSERT INTO clientes(nombre,email,telefono) VALUES(:nombre,NULL,:telefono)');
                    $crearCliente->execute([':nombre' => $nombre, ':telefono' => $telefono !== '' ? $telefono : null]);
                }
                $idCliente = (int)$pdo->lastInsertId();
            }

            $numeroCreado = 'NSC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $crearServicio = $pdo->prepare("INSERT INTO servicios(numero_servicio,id_cliente,tipo_dispositivo,marca,descripcion_dano,estado,creado_por) VALUES(:numero,:cliente,:tipo,:marca,:descripcion,'RECIBIDO',:admin)");
            $crearServicio->execute([
                ':numero' => $numeroCreado,
                ':cliente' => $idCliente,
                ':tipo' => $tipo,
                ':marca' => $marca,
                ':descripcion' => $descripcion,
                ':admin' => (int)$_SESSION['admin_id'],
            ]);
            $idServicio = (int)$pdo->lastInsertId();

            $historial = $pdo->prepare("INSERT INTO historial_servicios(id_servicio,estado_anterior,estado_nuevo,diagnostico,cambiado_por) VALUES(:servicio,NULL,'RECIBIDO',NULL,:admin)");
            $historial->execute([':servicio' => $idServicio, ':admin' => (int)$_SESSION['admin_id']]);
            $pdo->commit();
            $_SESSION['flash_registro'] = ['numero' => $numeroCreado, 'codigo' => $codigoActivacion];
            header('Location: registro.php?registrado=1');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($exception->getMessage());
            $numeroCreado = '';
            $error = 'No se pudo registrar el servicio. Verifica si el correo ya está asociado a otro registro e inténtalo nuevamente.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrar servicio | NERV SERVICE CONTROL</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a>
  <div class="topbar-right"><a href="index.php">COMMAND CENTER</a><form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit" class="nav-logout">CERRAR SESIÓN</button></form></div>
</header>
<main class="container">
  <section class="hero">
    <p class="eyebrow">SERVICE INTAKE // NEW RECORD</p>
    <h1>Registrar servicio</h1>
    <p class="muted">Registra los datos del cliente, el equipo y la falla reportada para iniciar una orden de servicio.</p>
  </section>

  <?php if ($numeroCreado !== ''): ?>
    <div class="notice registration-success">
      <strong>ORDEN REGISTRADA CORRECTAMENTE</strong>
      <p>Número de servicio asignado:</p>
      <strong class="service-number"><?= e($numeroCreado) ?></strong>
      <p>El servicio quedó registrado con estado <strong>RECIBIDO</strong>.</p>
      <?php if ($codigoActivacion !== ''): ?>
        <div class="activation-code-panel"><strong>CÓDIGO DE ACTIVACIÓN DEL PORTAL</strong><p class="activation-code"><?= e($codigoActivacion) ?></p><p>Entrega este código directamente al cliente. Es válido durante 7 días y solo puede utilizarse una vez. No lo publiques ni lo compartas con terceros.</p></div>
      <?php endif; ?>
      <div class="action-row">
        <a class="button" href="consulta.php?numero=<?= rawurlencode($numeroCreado) ?>">Consultar este servicio</a>
        <a class="text-link" href="registro.php">Registrar otro</a>
      </div>
    </div>
  <?php else: ?>
    <?php if ($error !== ''): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form class="form-panel service-form" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="form-section-title"><span>01</span><div><h2>Información del cliente</h2><p>Si el correo ya está registrado, el sistema vinculará el servicio a ese cliente.</p></div></div>
      <label>Nombre completo <span class="required-mark">*</span><input name="nombre_cliente" maxlength="120" required autocomplete="name" value="<?= e($_POST['nombre_cliente'] ?? '') ?>" placeholder="Nombre y apellidos"></label>
      <div class="form-grid">
        <label>Correo electrónico<input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="cliente@correo.com"></label>
        <label>Teléfono<input name="telefono" maxlength="30" autocomplete="tel" value="<?= e($_POST['telefono'] ?? '') ?>" placeholder="Número de contacto"></label>
      </div>
      <div class="form-section-title"><span>02</span><div><h2>Información del dispositivo</h2><p>Identifica el equipo que ingresa al área técnica.</p></div></div>
      <div class="form-grid">
        <label>Tipo de dispositivo <span class="required-mark">*</span><select name="tipo_dispositivo" required><option value="">Selecciona el tipo</option><?php foreach ($tipos as $opcion): ?><option value="<?= e($opcion) ?>" <?= (($_POST['tipo_dispositivo'] ?? '') === $opcion) ? 'selected' : '' ?>><?= e($opcion) ?></option><?php endforeach; ?></select></label>
        <label>Marca / fabricante <span class="required-mark">*</span><input name="marca" maxlength="80" required value="<?= e($_POST['marca'] ?? '') ?>" placeholder="Ej. HP, Lenovo, Samsung"></label>
      </div>
      <label>Descripción del daño <span class="required-mark">*</span><textarea name="descripcion_dano" rows="5" minlength="10" maxlength="5000" required placeholder="Describe el problema indicado por el cliente..."><?= e($_POST['descripcion_dano'] ?? '') ?></textarea><span class="field-hint">Mínimo 10 caracteres. Incluye síntomas, golpes, fallas o antecedentes conocidos.</span></label>
      <div class="form-footer"><span class="field-hint">* Campos obligatorios · El estado inicial será RECIBIDO.</span><div class="action-row"><a class="text-link" href="index.php">Cancelar</a><button class="button" type="submit">Crear orden de servicio</button></div></div>
    </form>
  <?php endif; ?>
  <p class="footer-note"><a class="text-link" href="index.php">← Volver al COMMAND CENTER</a></p>
</main>
</body>
</html>
