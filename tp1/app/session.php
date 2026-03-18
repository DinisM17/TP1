<?php
declare(strict_types=1);

function session_boot(): void {
  $cfg = require __DIR__ . '/config.php';
  $s = $cfg['session'];

  if (session_status() === PHP_SESSION_ACTIVE) return;

  session_name($s['name']);
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => false, // em HTTPS mete true
  ]);

  session_start();

  // expiração por inatividade
  $now = time();
  if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > (int)$s['idle_timeout']) {
    session_unset();
    session_destroy();
    session_start();
  }
  $_SESSION['last_activity'] = $now;
}