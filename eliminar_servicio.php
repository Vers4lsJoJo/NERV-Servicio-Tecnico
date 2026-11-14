<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
verify_csrf();
$id = filter_var($_POST['id_servicio'] ?? null, FILTER_VALIDATE_INT);
$motivo = trim((string)($_POST['motivo'] ?? ''));
if (!$id || $id < 1 || $motivo === '' || text_length($motivo) > 255) { http_response_code(422); exit('Indica un servicio válido y un motivo de archivo de máximo 255 caracteres.'); }
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE servicios SET eliminado_en=NOW(),eliminado_por=:admin,motivo_eliminacion=:motivo WHERE id_servicio=:id AND eliminado_en IS NULL');
    $stmt->execute([':admin' => (int)$_SESSION['admin_id'], ':motivo' => $motivo, ':id' => $id]);
    if ($stmt->rowCount() !== 1) {
        $pdo->rollBack();
        header('Location: gestion_registros.php?vista=activos&resultado=no-encontrado');
        exit;
    }
    $audit = $pdo->prepare("INSERT INTO auditoria_servicios(id_servicio,accion,motivo,id_usuario) VALUES(:id,'ARCHIVADO',:motivo,:usuario)");
    $audit->execute([':id' => $id, ':motivo' => $motivo, ':usuario' => (int)$_SESSION['admin_id']]);
    $pdo->commit();
    header('Location: gestion_registros.php?vista=archivados&resultado=archivado');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($exception->getMessage());
    http_response_code(500);
    exit('No se pudo archivar el servicio. Inténtalo nuevamente.');
}
