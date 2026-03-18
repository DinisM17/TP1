<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/path.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  auth_logout();
}
header('Location: ' . BASE_URL . '/public/login.php');
exit;