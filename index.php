<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_admin();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Command Center | NERV SERVICE CONTROL</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a>
  <div class="topbar-right"><span class="status-dot"></span> SYSTEM ONLINE <a href="logout.php">CERRAR SESIÓN</a></div>
</header>
<main class="container">
  <section class="hero">
    <p class="eyebrow">TECHNICAL MAINTENANCE DIVISION</p>
    <h1>COMMAND CENTER</h1>
    <p class="muted">Bienvenido, <?= e($_SESSION['admin_name'] ?? 'Administrador') ?>. Selecciona una operación.</p>
  </section>
  <section class="module-grid">
    <a class="module-card" href="registro.php"><span class="module-number">01</span><h2>Registrar servicio</h2><p>Ingresar cliente, dispositivo y daño reportado.</p></a>
    <a class="module-card" href="consulta.php"><span class="module-number">02</span><h2>Consultar servicio</h2><p>Buscar una reparación por número de servicio.</p></a>
    <a class="module-card" href="administracion.php"><span class="module-number">03</span><h2>Administración técnica</h2><p>Actualizar diagnósticos y estados de reparación.</p></a>
    <a class="module-card" href="solicitudes_admin.php"><span class="module-number">04</span><h2>Solicitudes de clientes</h2><p>Revisar solicitudes enviadas desde el portal de clientes.</p></a>
    <a class="module-card" href="cliente_login.php" target="_blank" rel="noopener"><span class="module-number">05</span><h2>Portal del cliente</h2><p>Acceso independiente para crear solicitudes.</p></a>
  </section>
  <p class="footer-note">NERV INTERNAL SYSTEMS // AUTHORIZED PERSONNEL ONLY</p>
</main>
</body>
</html>
