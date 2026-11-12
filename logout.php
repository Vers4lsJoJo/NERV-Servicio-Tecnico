
<?php
session_start();

// Obtener los parámetros de la cookie
$params = session_get_cookie_params();

// Eliminar la cookie de sesión
setcookie(
    session_name(),
    '',
    [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]
);

// Destruir la sesión
$_SESSION = [];
session_destroy();

// Redirigir al inicio de sesión
header('Location: login.php');
exit;
