<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
 $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
 session_set_cookie_params(['httponly'=>true,'secure'=>$secure,'samesite'=>'Lax']); session_start();
}
function require_admin(): void { if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; } }
function require_cliente(): void { if (empty($_SESSION['cliente_id'])) { header('Location: ../cliente_login.php'); exit; } }
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf(): void { $t=(string)($_POST['csrf_token']??''); if ($t==='' || !hash_equals((string)($_SESSION['csrf_token']??''),$t)) { http_response_code(419); exit('La sesión del formulario expiró. Regresa e inténtalo nuevamente.'); } }
