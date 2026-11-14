<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$count = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
if ($count > 0) { http_response_code(404); exit('Recurso no disponible.'); }
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $count === 0) {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || strlen($password) < 10) {
        $message = 'Escribe un usuario y una contraseña de al menos 10 caracteres.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre_usuario, password_hash) VALUES (:username, :hash)'
        );
        $stmt->execute([
            ':username' => $username,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $message = 'Administrador creado. Por seguridad, elimina setup_admin.php y entra desde login.php.';
        $count = 1;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Configuración inicial | NERV</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<main class="auth-card">
  <p class="eyebrow">NERV // INITIALIZATION</p>
  <h1>Crear administrador</h1>
  <?php if ($message !== ''): ?><p class="notice"><?= e($message) ?></p><?php endif; ?>
  <?php if ($count > 0): ?>
    <p>Ya existe una cuenta administrativa. Elimina este archivo del servidor.</p>
    <a class="button" href="login.php">Ir al acceso</a>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <label>Nombre de usuario
        <input name="username" maxlength="80" required autocomplete="username">
      </label>
      <label>Contraseña (mínimo 10 caracteres)
        <input type="password" name="password" minlength="10" required autocomplete="new-password">
      </label>
      <button class="button" type="submit">Crear administrador</button>
    </form>
  <?php endif; ?>
</main>
</body>
</html>
