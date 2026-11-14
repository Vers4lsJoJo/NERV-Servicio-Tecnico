<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
verify_csrf();
$id = filter_var($_POST['id_servicio'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('Identificador de servicio inválido.'); }
try {
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT motivo_eliminacion FROM servicios WHERE id_servicio=:id AND eliminado_en IS NOT NULL FOR UPDATE');
    $lock->execute([':id' => $id]);
    $archivado = $lock->fetch();
    if (!$archivado) {
        $pdo->rollBack();
        header('Location: gestion_registros.php?vista=archivados&resultado=no-encontrado');
        exit;
    }
    $update = $pdo->prepare('UPDATE servicios SET eliminado_en=NULL,eliminado_por=NULL,motivo_eliminacion=NULL WHERE id_servicio=:id');
    $update->execute([':id' => $id]);
    $audit = $pdo->prepare("INSERT INTO auditoria_servicios(id_servicio,accion,motivo,id_usuario) VALUES(:id,'RESTAURADO',:motivo,:usuario)");
    $audit->execute([':id' => $id, ':motivo' => $archivado['motivo_eliminacion'], ':usuario' => (int)$_SESSION['admin_id']]);
    $pdo->commit();
    header('Location: gestion_registros.php?vista=activos&resultado=restaurado');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($exception->getMessage());
    http_response_code(500);
    exit('No se pudo restaurar el servicio. Inténtalo nuevamente.');
}
