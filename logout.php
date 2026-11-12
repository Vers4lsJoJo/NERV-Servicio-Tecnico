<?php
declare(strict_types=1); require_once __DIR__.'/includes/auth.php';
$dest=!empty($_SESSION['cliente_id'])?'cliente_login.php':'login.php'; $_SESSION=[];
if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,['path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>'Lax']); }
session_destroy(); header('Location: '.$dest); exit;
