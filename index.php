<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$estados = [
    'RECIBIDO' => 'Recibidos',
    'EN DIAGNOSTICO' => 'En diagnóstico',
    'ESPERANDO APROBACION' => 'Esperando aprobación',
    'EN REPARACION' => 'En reparación',
    'REPARADO' => 'Reparados',
    'NO REPARABLE' => 'No reparables',
    'ENTREGADO' => 'Entregados',
];

// Resumen general: todos los valores proceden de la base de datos.
$resumen = $pdo->query(
    "SELECT COUNT(*) AS total,
        SUM(estado IN ('RECIBIDO','EN DIAGNOSTICO','ESPERANDO APROBACION')) AS pendientes,
        SUM(estado = 'EN REPARACION') AS en_proceso,
        SUM(estado IN ('REPARADO','NO REPARABLE','ENTREGADO')) AS finalizados
     FROM servicios WHERE eliminado_en IS NULL"
)->fetch() ?: [];

$total = (int)($resumen['total'] ?? 0);
$pendientes = (int)($resumen['pendientes'] ?? 0);
$enProceso = (int)($resumen['en_proceso'] ?? 0);
$finalizados = (int)($resumen['finalizados'] ?? 0);

$consultaEstados = $pdo->query(
    'SELECT estado, COUNT(*) AS cantidad FROM servicios WHERE eliminado_en IS NULL GROUP BY estado'
);
$conteoEstados = array_fill_keys(array_keys($estados), 0);
foreach ($consultaEstados->fetchAll() as $fila) {
    if (isset($conteoEstados[$fila['estado']])) {
        $conteoEstados[$fila['estado']] = (int)$fila['cantidad'];
    }
}
$maxEstado = max(1, ...array_values($conteoEstados));

// Construye los últimos seis meses, incluyendo meses sin solicitudes.
$meses = [];
$cursor = new DateTimeImmutable('first day of this month');
for ($i = 5; $i >= 0; $i--) {
    $mes = $cursor->modify('-' . $i . ' months');
    $clave = $mes->format('Y-m');
    $meses[$clave] = [
        'etiqueta' => strtoupper($mes->format('M')) . ' ' . $mes->format('y'),
        'cantidad' => 0,
    ];
}
$desde = array_key_first($meses) . '-01 00:00:00';
$consultaMeses = $pdo->prepare(
    'SELECT DATE_FORMAT(fecha_recepcion, "%Y-%m") AS periodo, COUNT(*) AS cantidad
     FROM servicios WHERE eliminado_en IS NULL AND fecha_recepcion >= :desde
     GROUP BY DATE_FORMAT(fecha_recepcion, "%Y-%m") ORDER BY periodo'
);
$consultaMeses->execute([':desde' => $desde]);
foreach ($consultaMeses->fetchAll() as $fila) {
    if (isset($meses[$fila['periodo']])) {
        $meses[$fila['periodo']]['cantidad'] = (int)$fila['cantidad'];
    }
}
$maxMes = max(1, ...array_column($meses, 'cantidad'));

// Últimos servicios creados o actualizados.
$recientes = $pdo->query(
    'SELECT s.id_servicio, s.numero_servicio, s.tipo_dispositivo, s.marca,
            s.estado, s.actualizado_en, c.nombre AS cliente_nombre
     FROM servicios s
     INNER JOIN clientes c ON c.id_cliente = s.id_cliente
     WHERE s.eliminado_en IS NULL
     ORDER BY s.actualizado_en DESC, s.id_servicio DESC
     LIMIT 7'
)->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#101112">
  <title>Command Center | NERV SERVICE CONTROL</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a>
  <div class="topbar-right"><span class="status-dot"></span> SYSTEM ONLINE <form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit" class="nav-logout">CERRAR SESIÓN</button></form></div>
</header>
<main class="container dashboard-container">
  <section class="hero dashboard-hero">
    <p class="eyebrow">TECHNICAL MAINTENANCE DIVISION // OVERVIEW</p>
    <h1>COMMAND CENTER</h1>
    <p class="muted">Bienvenido, <?= e($_SESSION['admin_name'] ?? 'Administrador') ?>. Estado general de las operaciones técnicas.</p>
  </section>

  <section class="dashboard-section" aria-labelledby="summary-title">
    <div class="section-heading"><div><p class="eyebrow">LIVE SYSTEM DATA</p><h2 id="summary-title">Resumen operativo</h2></div><span class="dashboard-stamp">ACTUALIZADO AL CARGAR</span></div>
    <div class="stats-grid">
      <article class="stat-card stat-total"><span class="stat-label">SOLICITUDES TOTALES</span><strong class="stat-value"><?= number_format($total, 0, ',', '.') ?></strong><span class="stat-foot">Todos los servicios registrados</span></article>
      <article class="stat-card stat-pending"><span class="stat-label">PENDIENTES</span><strong class="stat-value"><?= number_format($pendientes, 0, ',', '.') ?></strong><span class="stat-foot">Por atender o diagnosticar</span></article>
      <article class="stat-card stat-progress"><span class="stat-label">EN PROCESO</span><strong class="stat-value"><?= number_format($enProceso, 0, ',', '.') ?></strong><span class="stat-foot">Actualmente en reparación</span></article>
      <article class="stat-card stat-complete"><span class="stat-label">FINALIZADOS</span><strong class="stat-value"><?= number_format($finalizados, 0, ',', '.') ?></strong><span class="stat-foot">Reparados, no reparables o entregados</span></article>
    </div>
  </section>

  <section class="dashboard-section" aria-labelledby="analytics-title">
    <div class="section-heading"><div><p class="eyebrow">SERVICE ANALYTICS</p><h2 id="analytics-title">Estadísticas</h2></div></div>
    <div class="analytics-grid">
      <article class="dashboard-panel">
        <div class="panel-heading"><h3>Solicitudes por estado</h3><span> DISTRIBUCIÓN ACTUAL </span></div>
        <div class="state-chart">
          <?php foreach ($estados as $codigo => $etiqueta): $cantidad = $conteoEstados[$codigo]; $porcentaje = $total > 0 ? (int)round(($cantidad / $total) * 100) : 0; ?>
            <div class="state-chart-row">
              <div class="state-chart-meta"><span><?= e($etiqueta) ?></span><strong><?= $cantidad ?> <small>(<?= $porcentaje ?>%)</small></strong></div>
              <div class="bar-track"><span class="bar-fill" style="width: <?= $total > 0 ? $porcentaje : 0 ?>%"></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
      <article class="dashboard-panel">
        <div class="panel-heading"><h3>Actividad mensual</h3><span>ÚLTIMOS 6 MESES</span></div>
        <div class="monthly-chart" role="img" aria-label="Cantidad de solicitudes recibidas durante los últimos seis meses">
          <?php foreach ($meses as $mes): $altura = $mes['cantidad'] > 0 ? max(5, (int)round(($mes['cantidad'] / $maxMes) * 100)) : 0; ?>
            <div class="month-column" title="<?= e($mes['etiqueta']) ?>: <?= $mes['cantidad'] ?> solicitudes">
              <strong><?= $mes['cantidad'] ?></strong>
              <div class="month-bar-area"><span class="month-bar" style="height: <?= $altura ?>%"></span></div>
              <small><?= e($mes['etiqueta']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="chart-note">Conteo basado en la fecha de recepción de cada servicio.</p>
      </article>
    </div>
  </section>

  <section class="dashboard-section" aria-labelledby="recent-title">
    <div class="section-heading"><div><p class="eyebrow">LATEST OPERATIONS</p><h2 id="recent-title">Actividad reciente</h2></div><a class="text-link" href="administracion.php">VER TODOS LOS SERVICIOS ↗</a></div>
    <?php if (!$recientes): ?>
      <div class="empty-state"><strong>NO HAY ACTIVIDAD REGISTRADA</strong><p>Cuando se creen o actualicen servicios, aparecerán aquí.</p><a class="button" href="registro.php">Registrar primer servicio</a></div>
    <?php else: ?>
      <div class="table-wrap dashboard-table-wrap"><table class="dashboard-table"><thead><tr><th>N.º servicio</th><th>Cliente</th><th>Dispositivo</th><th>Estado actual</th><th>Última actualización</th><th></th></tr></thead><tbody>
      <?php foreach ($recientes as $servicio): ?>
        <tr>
          <td><strong><?= e($servicio['numero_servicio']) ?></strong></td>
          <td><?= e($servicio['cliente_nombre']) ?></td>
          <td><?= e($servicio['tipo_dispositivo']) ?> / <?= e($servicio['marca']) ?></td>
          <td><span class="state-tag"><?= e($servicio['estado']) ?></span></td>
          <td><?= e(date('d/m/Y H:i', strtotime($servicio['actualizado_en']))) ?></td>
          <td><a class="text-link" href="detalle_servicio.php?id=<?=(int)$servicio['id_servicio']?>">ABRIR</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </section>

  <section class="dashboard-section" aria-labelledby="modules-title">
    <div class="section-heading"><div><p class="eyebrow">SYSTEM MODULES</p><h2 id="modules-title">Accesos directos</h2></div></div>
    <div class="module-grid">
      <a class="module-card" href="registro.php"><span class="module-number">01</span><h2>Registrar servicio</h2><p>Ingresar cliente, dispositivo y daño reportado.</p></a>
      <a class="module-card" href="consulta.php"><span class="module-number">02</span><h2>Consultar servicio</h2><p>Buscar una reparación por número de servicio.</p></a>
      <a class="module-card" href="administracion.php"><span class="module-number">03</span><h2>Administración técnica</h2><p>Gestionar servicios, diagnósticos, estados e historial.</p></a>
      <a class="module-card" href="solicitudes_admin.php"><span class="module-number">04</span><h2>Solicitudes de clientes</h2><p>Revisar solicitudes enviadas desde el portal de clientes.</p></a>
      <a class="module-card" href="gestion_registros.php"><span class="module-number">05</span><h2>Gestión de registros</h2><p>Archivar registros de prueba y restaurar servicios cuando sea necesario.</p></a>
      <a class="module-card" href="cliente_login.php" target="_blank" rel="noopener"><span class="module-number">06</span><h2>Portal del cliente</h2><p>Acceso independiente para crear solicitudes.</p></a>
    </div>
  </section>
  <p class="footer-note">NERV INTERNAL SYSTEMS // AUTHORIZED PERSONNEL ONLY</p>
</main>
</body>
</html>
