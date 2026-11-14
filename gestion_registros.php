<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$vista = (string)($_GET['vista'] ?? 'activos');
if (!in_array($vista, ['activos', 'archivados'], true)) $vista = 'activos';
$buscar = trim((string)($_GET['buscar'] ?? ''));
$condicion = $vista === 'archivados' ? 's.eliminado_en IS NOT NULL' : 's.eliminado_en IS NULL';
$sql = 'SELECT s.id_servicio,s.numero_servicio,s.tipo_dispositivo,s.marca,s.estado,s.fecha_recepcion,s.eliminado_en,s.motivo_eliminacion,c.nombre AS cliente_nombre
        FROM servicios s INNER JOIN clientes c ON c.id_cliente=s.id_cliente WHERE ' . $condicion;
$params = [];
if ($buscar !== '') {
    $sql .= ' AND (s.numero_servicio LIKE :numero OR c.nombre LIKE :cliente OR s.marca LIKE :marca)';
    $term = '%' . $buscar . '%';
    $params = [':numero' => $term, ':cliente' => $term, ':marca' => $term];
}
$sql .= ' ORDER BY COALESCE(s.eliminado_en,s.fecha_recepcion) DESC,s.id_servicio DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gestión de registros | NERV</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a><div class="topbar-right"><a href="index.php">COMMAND CENTER</a><form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button type="submit" class="nav-logout">CERRAR SESIÓN</button></form></div></header>
<main class="container"><section class="hero"><p class="eyebrow">SYSTEM RECORDS // CONTROL</p><h1>GESTIÓN DE REGISTROS</h1><p class="muted">Archiva registros de prueba sin borrar permanentemente el servicio ni su historial. Los registros archivados pueden restaurarse.</p></section>
<div class="action-row archive-tabs"><a class="text-link" href="auditoria_registros.php">VER AUDITORÍA DE ACCIONES ↗</a><a class="button <?=$vista==='activos'?'':'button-secondary'?>" href="gestion_registros.php?vista=activos">Registros activos</a><a class="button <?=$vista==='archivados'?'':'button-secondary'?>" href="gestion_registros.php?vista=archivados">Registros archivados</a></div>
<form class="form-panel" method="get"><input type="hidden" name="vista" value="<?=e($vista)?>"><div class="lookup-row"><input name="buscar" value="<?=e($buscar)?>" placeholder="Buscar por número, cliente o marca"><button class="button" type="submit">Buscar</button><a class="text-link" href="gestion_registros.php?vista=<?=e($vista)?>">Limpiar</a></div></form>
<?php if (($_GET['resultado'] ?? '') === 'archivado'): ?><p class="notice">El servicio fue archivado. Su información e historial se conservaron.</p><?php elseif (($_GET['resultado'] ?? '') === 'restaurado'): ?><p class="notice">El servicio fue restaurado y volvió a los módulos activos.</p><?php elseif (($_GET['resultado'] ?? '') === 'no-encontrado'): ?><p class="error">No se encontró un registro que requiriera esa acción.</p><?php endif; ?>
<h2><?= $vista==='archivados' ? 'Registros archivados' : 'Registros activos' ?> (<?=count($servicios)?>)</h2>
<?php if (!$servicios): ?><div class="empty-state"><strong>NO HAY REGISTROS</strong><p>No se encontraron servicios en esta vista.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>N.º servicio</th><th>Cliente</th><th>Equipo</th><th>Estado</th><th>Fecha</th><?php if($vista==='archivados'): ?><th>Motivo de archivo</th><?php endif; ?><th>Acción</th></tr></thead><tbody>
<?php foreach($servicios as $s): ?><tr><td><strong><?=e($s['numero_servicio'])?></strong></td><td><?=e($s['cliente_nombre'])?></td><td><?=e($s['tipo_dispositivo'])?> / <?=e($s['marca'])?></td><td><span class="state-tag"><?=e($s['estado'])?></span></td><td><?=e(date('d/m/Y H:i',strtotime($vista==='archivados'?$s['eliminado_en']:$s['fecha_recepcion'])))?></td>
<?php if($vista==='archivados'): ?><td><?=e($s['motivo_eliminacion'] ?: '—')?></td><?php endif; ?><td>
<?php if($vista==='activos'): ?><a class="text-link" href="detalle_servicio.php?id=<?=(int)$s['id_servicio']?>">Abrir</a><form class="inline-action" method="post" action="eliminar_servicio.php" onsubmit="return confirm('¿Archivar este servicio? No se borrará definitivamente y podrás restaurarlo después.');"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id_servicio" value="<?=(int)$s['id_servicio']?>"><input type="text" name="motivo" maxlength="255" required placeholder="Motivo del archivo"><button class="text-button danger-text" type="submit">Archivar</button></form>
<?php else: ?><form class="inline-action" method="post" action="restaurar_servicio.php" onsubmit="return confirm('¿Restaurar este servicio y volver a mostrarlo en los módulos activos?');"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id_servicio" value="<?=(int)$s['id_servicio']?>"><button class="button" type="submit">Restaurar</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<p class="footer-note"><a class="text-link" href="index.php">← Volver al COMMAND CENTER</a></p></main></body></html>
