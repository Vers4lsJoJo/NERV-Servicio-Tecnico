<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$idCliente = filter_var($_POST['id_cliente'] ?? $_GET['id_cliente'] ?? null, FILTER_VALIDATE_INT);
if (!$idCliente || $idCliente < 1) {
    http_response_code(400);
    exit('Identificador de cliente inválido.');
}
$codigo = '';
$error = '';
$clienteQuery = $pdo->prepare('SELECT id_cliente,nombre,email,password_hash FROM clientes WHERE id_cliente=:id LIMIT 1');
$clienteQuery->execute([':id' => $idCliente]);
$cliente = $clienteQuery->fetch();
if (!$cliente) {
    http_response_code(404);
    exit('No se encontró el cliente.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!empty($cliente['password_hash'])) {
        $error = 'Este cliente ya tiene una cuenta activada.';
    } elseif (empty($cliente['email'])) {
        $error = 'El cliente no tiene correo electrónico. Actualiza sus datos antes de generar un código.';
    } else {
        $codigo = strtoupper(bin2hex(random_bytes(5)));
        $update = $pdo->prepare('UPDATE clientes SET codigo_activacion_hash=:hash,codigo_activacion_expira=DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id_cliente=:id AND password_hash IS NULL');
        $update->execute([':hash' => hash('sha256', $codigo), ':id' => $idCliente]);
        if ($update->rowCount() !== 1) {
            $codigo = '';
            $error = 'No se pudo generar el código. Comprueba el estado de la cuenta.';
        }
    }
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Activación de cliente | NERV</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a><div class="topbar-right"><a href="administracion.php">SERVICIOS</a></div></header>
<main class="container"><section class="hero"><p class="eyebrow">CUSTOMER ACCOUNT // ACTIVATION</p><h1>Código de activación</h1><p class="muted">Entrega el código directamente al titular del correo. Solo se muestra en esta pantalla y vence en siete días.</p></section>
<div class="form-panel"><p><strong>Cliente:</strong> <?=e($cliente['nombre'])?></p><p><strong>Correo:</strong> <?=e($cliente['email'])?></p>
<?php if ($codigo !== ''): ?><div class="activation-code-panel"><strong>CÓDIGO DE UN SOLO USO</strong><p class="activation-code"><?=e($codigo)?></p><p>El cliente debe introducirlo en el formulario de creación de cuenta. No compartas este código públicamente.</p></div>
<?php elseif ($error !== ''): ?><p class="error" role="alert"><?=e($error)?></p>
<?php elseif (!empty($cliente['password_hash'])): ?><p class="notice">La cuenta de este cliente ya está activada.</p>
<?php else: ?><p>Genera un código nuevo. Cualquier código anterior quedará invalidado.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id_cliente" value="<?=(int)$idCliente?>"><button class="button" type="submit">Generar código</button></form><?php endif; ?>
<p class="footer-note"><a class="text-link" href="javascript:history.back()">← Volver</a></p></div></main></body></html>
