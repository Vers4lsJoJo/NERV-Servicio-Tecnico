<?php
session_start();

// Obtener los parámetros actuales de la cookie
$params = session_get_cookie_params();

// Eliminar la cookie usando los argumentos individuales
setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
);

// Vaciar los datos de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();

// Redirigir al inicio de sesión
header('Location: login.php');
exit;
