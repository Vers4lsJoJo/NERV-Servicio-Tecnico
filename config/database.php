<?php
declare(strict_types=1);

/*
 * Configure these values for your local XAMPP installation.
 * For a real deployment, keep credentials outside the public web root.
 */
$host = '127.0.0.1';
$db   = 'nerv_service';
$user = 'root';
$pass = ''; // Default XAMPP MySQL password is often empty. Set yours if different.
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Verifica config/database.php y que MySQL esté iniciado.');
}
