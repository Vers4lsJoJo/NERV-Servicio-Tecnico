<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id_usuario, nombre_usuario, password_hash FROM usuarios WHERE nombre_usuario = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        $_SESSION['admin_id'] = (int)$admin['id_usuario'];
        $_SESSION['admin_name'] = $admin['nombre_usuario'];
        header('Location: index.php');
        exit;
    }
    $error = 'Credenciales incorrectas.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Acceso | NERV SERVICE CONTROL</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<main class="auth-card">
  <p class="eyebrow">NERV // TECHNICAL OPERATIONS</p>
  <h1>Service Control</h1>
  <p class="muted">Acceso exclusivo para personal autorizado.</p>
  <p class="muted small-link"><a href="cliente_login.php">¿Eres cliente? Ingresa aquí</a></p>
  <?php if ($error !== ''): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Usuario
      <input name="username" required autocomplete="username">
    </label>
    <label>Contraseña
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="button" type="submit">Iniciar sesión</button>
  </form>
</main>
</body>
</html>
