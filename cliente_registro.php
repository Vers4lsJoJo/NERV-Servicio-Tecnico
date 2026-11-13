<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['cliente_id'])) { header('Location: cliente/index.php'); exit; }
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
        $error = 'Verifica el nombre, el correo y usa una contraseña de mínimo 10 caracteres.';
    } else {
        try {
            $pdo->beginTransaction();
            $buscar = $pdo->prepare('SELECT id_cliente,password_hash FROM clientes WHERE email=:email LIMIT 1 FOR UPDATE');
            $buscar->execute([':email' => $email]);
            $existente = $buscar->fetch();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($existente) {
                // Permite que un cliente registrado previamente por administración active su cuenta.
                if (!empty($existente['password_hash'])) {
                    $pdo->rollBack();
                    $error = 'Ese correo ya tiene una cuenta registrada. Inicia sesión o recupera el acceso.';
                } else {
                    $idCliente = (int)$existente['id_cliente'];
                    $actualizar = $pdo->prepare('UPDATE clientes SET nombre=:nombre,telefono=:telefono,password_hash=:hash WHERE id_cliente=:id');
                    $actualizar->execute([':nombre' => $nombre, ':telefono' => $telefono !== '' ? $telefono : null, ':hash' => $hash, ':id' => $idCliente]);
                    $pdo->commit();
                    session_regenerate_id(true);
                    $_SESSION['cliente_id'] = $idCliente;
                    $_SESSION['cliente_nombre'] = $nombre;
                    header('Location: cliente/index.php?bienvenida=1');
                    exit;
                }
            } else {
                $crear = $pdo->prepare('INSERT INTO clientes(nombre,email,telefono,password_hash) VALUES(:nombre,:email,:telefono,:hash)');
                $crear->execute([':nombre' => $nombre, ':email' => $email, ':telefono' => $telefono !== '' ? $telefono : null, ':hash' => $hash]);
                $idCliente = (int)$pdo->lastInsertId();
                $pdo->commit();
                session_regenerate_id(true);
                $_SESSION['cliente_id'] = $idCliente;
                $_SESSION['cliente_nombre'] = $nombre;
                header('Location: cliente/index.php?bienvenida=1');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($exception->getMessage());
            $error = $exception->getCode() === '23000' ? 'Ese correo ya tiene una cuenta registrada.' : 'No se pudo crear la cuenta. Inténtalo más tarde.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($exception->getMessage());
            $error = 'No se pudo crear la cuenta. Inténtalo más tarde.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Crear cuenta | NERV</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-page">
<main class="auth-card">
  <p class="eyebrow">NERV // CLIENT REGISTRATION</p>
  <h1>Crear cuenta</h1>
  <p class="muted">Regístrate para solicitar y consultar reparaciones. Si el personal técnico ya registró tus datos, utiliza el mismo correo para activar tu cuenta.</p>
  <?php if ($error !== ''): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Nombre completo<input name="nombre" maxlength="120" required autocomplete="name" value="<?= e($_POST['nombre'] ?? '') ?>"></label>
    <label>Correo electrónico<input type="email" name="email" maxlength="190" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Teléfono (opcional)<input name="telefono" maxlength="30" autocomplete="tel" value="<?= e($_POST['telefono'] ?? '') ?>"></label>
    <label>Contraseña (mínimo 10 caracteres)<input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
    <button class="button" type="submit">Crear cuenta</button>
  </form>
  <p class="muted small-link"><a href="cliente_login.php">Ya tengo una cuenta</a> · <a href="login.php">Acceso administrador</a></p>
</main>
</body>
</html>
