<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

function auth_login(string $email, string $password): bool {
  session_boot();

  $email = trim(mb_strtolower($email));
  if ($email === '' || $password === '') return false;

  $stmt = db()->prepare("
    SELECT u.id, u.email, u.password_hash, u.name, u.active, r.name AS role
    FROM users u
    JOIN roles r ON r.id = u.role_id
    WHERE u.email = ?
    LIMIT 1
  ");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if (!$u || (int)$u['active'] !== 1) return false;
  if (!password_verify($password, (string)$u['password_hash'])) return false;

  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id' => (int)$u['id'],
    'email' => (string)$u['email'],
    'name' => (string)$u['name'],
    'role' => (string)$u['role'],
  ];
  return true;
}

function auth_logout(): void {
  session_boot();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
  }
  session_destroy();
}

function current_user(): ?array {
  session_boot();
  return $_SESSION['user'] ?? null;
}