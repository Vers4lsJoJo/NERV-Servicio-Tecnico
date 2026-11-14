<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$numero = strtoupper(trim((string)($_GET['numero'] ?? '')));
$servicio = null;
$historial = [];
$error = '';

if ($numero !== '') {
    if (text_length($numero) > 30) {
        $error = 'El número de servicio no es válido.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT s.id_servicio,s.numero_servicio,s.tipo_dispositivo,s.marca,s.descripcion_dano,
                    s.diagnostico,s.estado,s.fecha_recepcion,s.fecha_finalizacion,s.actualizado_en,
                    c.nombre AS cliente_nombre,c.email,c.telefono
             FROM servicios s INNER JOIN clientes c ON c.id_cliente=s.id_cliente
             WHERE s.numero_servicio=:numero AND s.eliminado_en IS NULL LIMIT 1'
        );
        $stmt->execute([':numero' => $numero]);
        $servicio = $stmt->fetch() ?: null;
        if (!$servicio) {
            $error = 'No se encontró ningún servicio con ese número. Verifica el código e inténtalo nuevamente.';
        } else {
            $movimientos = $pdo->prepare(
                'SELECT h.estado_anterior,h.estado_nuevo,h.diagnostico,h.fecha_cambio,u.nombre_usuario
                 FROM historial_servicios h LEFT JOIN usuarios u ON u.id_usuario=h.cambiado_por
                 WHERE h.id_servicio=:id ORDER BY h.fecha_cambio DESC,h.id_historial DESC'
            );
            $movimientos->execute([':id' => $servicio['id_servicio']]);
            $historial = $movimientos->fetchAll();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consultar servicio | NERV SERVICE CONTROL</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a>
  <div class="topbar-right"><a href="index.php">COMMAND CENTER</a><form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit" class="nav-logout">CERRAR SESIÓN</button></form></div>
</header>
<main class="container">
  <section class="hero">
    <p class="eyebrow">SERVICE DATABASE // LOOKUP</p>
    <h1>Consultar servicio</h1>
    <p class="muted">Localiza una orden por su número de servicio y revisa la información técnica y los movimientos registrados.</p>
  </section>

  <form class="form-panel lookup-form" method="get" action="consulta.php">
    <label for="numero">Número de servicio</label>
    <div class="lookup-row"><input id="numero" name="numero" maxlength="30" required value="<?= e($numero) ?>" placeholder="Ej. NSC-20261113-A1B2C3D4"><button class="button" type="submit">Buscar servicio</button></div>
    <span class="field-hint">Introduce el código completo que aparece en el comprobante de recepción.</span>
  </form>

  <?php if ($error !== ''): ?><p class="error lookup-message" role="alert"><?= e($error) ?></p><?php endif; ?>

  <?php if ($servicio): ?>
    <section class="service-result">
      <div class="result-heading"><div><p class="eyebrow">RECORD FOUND // <?= e($servicio['numero_servicio']) ?></p><h2>Ficha del servicio</h2></div><span class="state-tag"><?= e($servicio['estado']) ?></span></div>
      <div class="result-grid">
        <article class="result-card"><span>CLIENTE</span><strong><?= e($servicio['cliente_nombre']) ?></strong><p><?= e($servicio['email'] ?: 'Sin correo registrado') ?></p><p><?= e($servicio['telefono'] ?: 'Sin teléfono registrado') ?></p></article>
        <article class="result-card"><span>DISPOSITIVO</span><strong><?= e($servicio['tipo_dispositivo']) ?></strong><p><?= e($servicio['marca']) ?></p></article>
        <article class="result-card"><span>RECEPCIÓN</span><strong><?= e(date('d/m/Y', strtotime($servicio['fecha_recepcion']))) ?></strong><p><?= e(date('H:i', strtotime($servicio['fecha_recepcion']))) ?></p></article>
        <article class="result-card"><span>ÚLTIMA ACTUALIZACIÓN</span><strong><?= e(date('d/m/Y', strtotime($servicio['actualizado_en']))) ?></strong><p><?= e(date('H:i', strtotime($servicio['actualizado_en']))) ?></p></article>
      </div>
      <div class="result-description"><h3>Falla reportada</h3><p><?= nl2br(e($servicio['descripcion_dano'])) ?></p></div>
      <div class="result-description"><h3>Diagnóstico técnico</h3><?php if (trim((string)$servicio['diagnostico']) !== ''): ?><p><?= nl2br(e($servicio['diagnostico'])) ?></p><?php else: ?><p class="muted">Todavía no se ha registrado un diagnóstico.</p><?php endif; ?></div>
      <?php if ($servicio['fecha_finalizacion']): ?><p class="field-hint">Fecha de finalización: <?= e(date('d/m/Y H:i', strtotime($servicio['fecha_finalizacion']))) ?></p><?php endif; ?>
    </section>

    <section class="dashboard-section history-section">
      <div class="section-heading"><div><p class="eyebrow">TRACE LOG</p><h2>Historial de movimientos</h2></div><a class="text-link" href="detalle_servicio.php?id=<?=(int)$servicio['id_servicio']?>">GESTIONAR SERVICIO ↗</a></div>
      <?php if (!$historial): ?><p class="muted">No hay movimientos registrados.</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Estado anterior</th><th>Estado nuevo</th><th>Diagnóstico / observación</th><th>Registrado por</th></tr></thead><tbody>
        <?php foreach ($historial as $movimiento): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($movimiento['fecha_cambio']))) ?></td><td><?= e($movimiento['estado_anterior'] ?: '—') ?></td><td><span class="state-tag"><?= e($movimiento['estado_nuevo']) ?></span></td><td><?= $movimiento['diagnostico'] ? nl2br(e($movimiento['diagnostico'])) : '<span class="muted">Sin observaciones</span>' ?></td><td><?= e($movimiento['nombre_usuario'] ?: 'Sistema / cliente') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
  <p class="footer-note"><a class="text-link" href="index.php">← Volver al COMMAND CENTER</a></p>
</main>
</body>
</html>
