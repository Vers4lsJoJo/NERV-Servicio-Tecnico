<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('Identificador de servicio inválido.'); }
$stmt = $pdo->prepare('SELECT s.*,c.id_cliente,c.nombre AS cliente_nombre,c.email,c.telefono,c.password_hash FROM servicios s INNER JOIN clientes c ON c.id_cliente=s.id_cliente WHERE s.id_servicio=:id AND s.eliminado_en IS NULL LIMIT 1');
$stmt->execute([':id'=>$id]);
$servicio = $stmt->fetch();
if (!$servicio) { http_response_code(404); exit('No se encontró el servicio solicitado.'); }
$h = $pdo->prepare('SELECT hs.estado_anterior,hs.estado_nuevo,hs.diagnostico,hs.fecha_cambio,u.nombre_usuario FROM historial_servicios hs LEFT JOIN usuarios u ON u.id_usuario=hs.cambiado_por WHERE hs.id_servicio=:id ORDER BY hs.fecha_cambio DESC,hs.id_historial DESC');
$h->execute([':id'=>$id]);
$historial = $h->fetchAll();
$estados = ['RECIBIDO','EN DIAGNOSTICO','ESPERANDO APROBACION','EN REPARACION','REPARADO','NO REPARABLE','ENTREGADO'];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Detalle de servicio | NERV</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a><div class="topbar-right"><a href="administracion.php">SERVICIOS</a><form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit" class="nav-logout">CERRAR SESIÓN</button></form></div></header>
<main class="container"><section class="hero"><p class="eyebrow">SERVICE FILE // <?=e($servicio['numero_servicio'])?></p><h1>DETALLE DE REPARACIÓN</h1><p class="muted">Consulta los datos y actualiza el diagnóstico o el estado del servicio.</p></section>
<div class="form-panel"><h2>Datos del cliente y del equipo</h2><p><strong>Cliente:</strong> <?=e($servicio['cliente_nombre'])?></p><p><strong>Correo:</strong> <?=e($servicio['email'])?></p><p><strong>Teléfono:</strong> <?=e($servicio['telefono'])?></p><?php if (empty($servicio['password_hash']) && !empty($servicio['email'])): ?><p><a class="text-link" href="generar_codigo_cliente.php?id_cliente=<?=(int)$servicio['id_cliente']?>">GENERAR CÓDIGO DE ACTIVACIÓN DEL PORTAL</a></p><?php endif; ?><p><strong>Dispositivo:</strong> <?=e($servicio['tipo_dispositivo'])?> / <?=e($servicio['marca'])?></p><p><strong>Fecha de recepción:</strong> <?=e($servicio['fecha_recepcion'])?></p><p><strong>Daño reportado:</strong><br><?=nl2br(e($servicio['descripcion_dano']))?></p><p><strong>Estado actual:</strong> <span class="state-tag"><?=e($servicio['estado'])?></span></p></div>
<?php if(isset($_GET['actualizado'])): ?><p class="notice">Los cambios se guardaron correctamente.</p><?php endif; ?><h2>Actualizar reparación</h2>
<form class="form-panel" method="post" action="actualizar_servicio.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id_servicio" value="<?=(int)$servicio['id_servicio']?>"><label>Diagnóstico técnico<textarea name="diagnostico" rows="6" maxlength="10000" placeholder="Describe la causa del daño, pruebas realizadas y solución propuesta..."><?=e($servicio['diagnostico'])?></textarea></label><label>Estado de la reparación<select name="estado" required><?php foreach($estados as $estado): ?><option value="<?=e($estado)?>" <?=$servicio['estado']===$estado?'selected':''?>><?=e($estado)?></option><?php endforeach; ?></select></label><p class="muted">Para marcar un servicio como REPARADO, NO REPARABLE o ENTREGADO, debes registrar primero un diagnóstico.</p><div class="action-row"><button class="button" type="submit">Guardar cambios</button><a class="text-link" href="administracion.php">Volver a servicios</a></div></form>
<h2>Historial de movimientos</h2><?php if(!$historial): ?><p class="muted">Todavía no hay movimientos registrados.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Estado anterior</th><th>Estado nuevo</th><th>Diagnóstico registrado</th><th>Usuario</th></tr></thead><tbody><?php foreach($historial as $mov): ?><tr><td><?=e($mov['fecha_cambio'])?></td><td><?=e($mov['estado_anterior']??'—')?></td><td><?=e($mov['estado_nuevo'])?></td><td><?= $mov['diagnostico']!==null && $mov['diagnostico']!=='' ? nl2br(e($mov['diagnostico'])) : '<span class="muted">Sin diagnóstico</span>' ?></td><td><?=e($mov['nombre_usuario']??'Sistema / cliente')?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</main></body></html>
