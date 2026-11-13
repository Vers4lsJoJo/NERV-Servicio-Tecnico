<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$estados = ['RECIBIDO','EN DIAGNOSTICO','ESPERANDO APROBACION','EN REPARACION','REPARADO','NO REPARABLE','ENTREGADO'];
$filtro = trim((string)($_GET['estado'] ?? ''));
$buscar = trim((string)($_GET['buscar'] ?? ''));
if ($filtro !== '' && !in_array($filtro, $estados, true)) $filtro = '';
$sql = 'SELECT s.id_servicio,s.numero_servicio,c.nombre,s.tipo_dispositivo,s.marca,s.estado,s.fecha_recepcion FROM servicios s INNER JOIN clientes c ON c.id_cliente=s.id_cliente WHERE 1=1';
$params = [];
if ($filtro !== '') { $sql .= ' AND s.estado=:estado'; $params[':estado'] = $filtro; }
if ($buscar !== '') { $sql .= ' AND (s.numero_servicio LIKE :buscar_numero OR c.nombre LIKE :buscar_cliente OR s.marca LIKE :buscar_marca)'; $term = '%'.$buscar.'%'; $params[':buscar_numero'] = $term; $params[':buscar_cliente'] = $term; $params[':buscar_marca'] = $term; }
$sql .= ' ORDER BY s.fecha_recepcion DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administración técnica | NERV</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a><div class="topbar-right"><a href="index.php">COMMAND CENTER</a><a href="logout.php">CERRAR SESIÓN</a></div></header>
<main class="container">
<section class="hero"><p class="eyebrow">TECHNICAL OPERATIONS</p><h1>ADMINISTRACIÓN TÉCNICA</h1><p class="muted">Consulta las reparaciones, registra diagnósticos y actualiza su estado.</p></section>
<?php if (isset($_GET['actualizado'])): ?><p class="notice">Los cambios del servicio se guardaron correctamente.</p><?php endif; ?>
<form class="form-panel" method="get" style="max-width:none"><div class="action-row"><label style="flex:2">Buscar servicio, cliente o marca<input name="buscar" value="<?=e($buscar)?>" placeholder="Ej. NSC-20261112 o Lenovo"></label><label style="flex:1">Filtrar por estado<select name="estado"><option value="">Todos los estados</option><?php foreach ($estados as $estado): ?><option value="<?=e($estado)?>" <?=$filtro===$estado?'selected':''?>><?=e($estado)?></option><?php endforeach; ?></select></label><button class="button" type="submit">Buscar</button><a class="text-link" href="administracion.php">Limpiar</a></div></form>
<h2>Servicios registrados (<?=count($servicios)?>)</h2>
<?php if (!$servicios): ?><p class="muted">No se encontraron servicios con esos criterios.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>N.º servicio</th><th>Cliente</th><th>Dispositivo</th><th>Estado</th><th>Recepción</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($servicios as $s): ?><tr><td><?=e($s['numero_servicio'])?></td><td><?=e($s['nombre'])?></td><td><?=e($s['tipo_dispositivo'])?> / <?=e($s['marca'])?></td><td><span class="state-tag"><?=e($s['estado'])?></span></td><td><?=e($s['fecha_recepcion'])?></td><td><a class="text-link" href="detalle_servicio.php?id=<?=(int)$s['id_servicio']?>">Abrir / actualizar</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<p class="footer-note"><a class="text-link" href="index.php">← Volver al panel principal</a></p>
</main></body></html>
