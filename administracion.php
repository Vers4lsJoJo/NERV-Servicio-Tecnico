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
  <title>Administración técnica | NERV</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">N</span>NERV <span class="muted">/ SERVICE CONTROL</span></a>
  <div class="topbar-right"><a href="index.php">COMMAND CENTER</a><a href="logout.php">CERRAR SESIÓN</a></div>
</header>
<main class="container">
  <section class="hero">
    <p class="eyebrow">TECHNICAL OPERATIONS</p>
    <h1>Administración técnica</h1>
    <p class="muted">La edición de diagnóstico y estado se implementará en la siguiente fase.</p>
  </section>
  <a class="button" href="index.php">Volver al panel</a>
</main>
</body>
</html>
