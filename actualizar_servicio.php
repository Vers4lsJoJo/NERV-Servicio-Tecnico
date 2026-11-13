<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
verify_csrf();
$id = filter_var($_POST['id_servicio'] ?? null, FILTER_VALIDATE_INT);
$diagnostico = trim((string)($_POST['diagnostico'] ?? ''));
$estado = trim((string)($_POST['estado'] ?? ''));
$estados = ['RECIBIDO','EN DIAGNOSTICO','ESPERANDO APROBACION','EN REPARACION','REPARADO','NO REPARABLE','ENTREGADO'];
if (!$id || $id < 1 || !in_array($estado, $estados, true)) { http_response_code(400); exit('Los datos enviados no son válidos.'); }
$finales = ['REPARADO','NO REPARABLE','ENTREGADO'];
if (in_array($estado, $finales, true) && $diagnostico === '') { http_response_code(422); exit('Debes registrar un diagnóstico antes de cerrar o entregar una reparación. Regresa al detalle del servicio e inténtalo nuevamente.'); }
try {
    $pdo->beginTransaction();
    $q = $pdo->prepare('SELECT estado,diagnostico FROM servicios WHERE id_servicio=:id FOR UPDATE');
    $q->execute([':id'=>$id]);
    $actual = $q->fetch();
    if (!$actual) { $pdo->rollBack(); http_response_code(404); exit('No se encontró el servicio.'); }
    $estadoAnterior = (string)$actual['estado'];
    $diagnosticoAnterior = (string)($actual['diagnostico'] ?? '');
    if ($estadoAnterior !== $estado || $diagnosticoAnterior !== $diagnostico) {
        $u = $pdo->prepare("UPDATE servicios SET diagnostico=:diagnostico, estado=:estado, fecha_finalizacion=CASE WHEN :es_final=1 THEN COALESCE(fecha_finalizacion,NOW()) ELSE NULL END WHERE id_servicio=:id");
        $u->execute([':diagnostico'=>$diagnostico !== '' ? $diagnostico : null, ':estado'=>$estado, ':es_final'=>in_array($estado,$finales,true)?1:0, ':id'=>$id]);
        $h = $pdo->prepare('INSERT INTO historial_servicios(id_servicio,estado_anterior,estado_nuevo,diagnostico,cambiado_por) VALUES(:id,:anterior,:nuevo,:diagnostico,:usuario)');
        $h->execute([':id'=>$id, ':anterior'=>$estadoAnterior, ':nuevo'=>$estado, ':diagnostico'=>$diagnostico !== '' ? $diagnostico : null, ':usuario'=>(int)$_SESSION['admin_id']]);
    }
    $pdo->commit();
    header('Location: detalle_servicio.php?id='.$id.'&actualizado=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudieron guardar los cambios. Inténtalo nuevamente.');
}
