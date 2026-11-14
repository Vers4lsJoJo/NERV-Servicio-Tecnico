<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
$q = $pdo->query('SELECT a.accion,a.motivo,a.fecha_accion,s.numero_servicio,u.nombre_usuario FROM auditoria_servicios a INNER JOIN servicios s ON s.id_servicio=a.id_servicio LEFT JOIN usuarios u ON u.id_usuario=a.id_usuario ORDER BY a.fecha_accion DESC,a.id_auditoria DESC LIMIT 200');
$eventos = $q->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Auditoría de registros | NERV</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a><div class="topbar-right"><a href="gestion_registros.php">GESTIÓN DE REGISTROS</a><form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button type="submit" class="nav-logout">CERRAR SESIÓN</button></form></div></header>
<main class="container"><section class="hero"><p class="eyebrow">SYSTEM AUDIT // RECORDS</p><h1>AUDITORÍA DE REGISTROS</h1><p class="muted">Registro cronológico de archivos y restauraciones. Se muestran los últimos 200 movimientos.</p></section>
<?php if (!$eventos): ?><div class="empty-state"><strong>SIN MOVIMIENTOS</strong><p>Las acciones de archivo y restauración aparecerán aquí.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Acción</th><th>Servicio</th><th>Motivo</th><th>Administrador</th></tr></thead><tbody><?php foreach($eventos as $evento): ?><tr><td><?=e(date('d/m/Y H:i',strtotime($evento['fecha_accion'])))?></td><td><span class="state-tag"><?=e($evento['accion'])?></span></td><td><?=e($evento['numero_servicio'])?></td><td><?=e($evento['motivo'] ?: '—')?></td><td><?=e($evento['nombre_usuario'] ?: 'Usuario eliminado')?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<p class="footer-note"><a class="text-link" href="gestion_registros.php">← Volver a gestión de registros</a></p></main></body></html>
