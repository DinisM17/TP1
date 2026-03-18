<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/path.php';

function require_login(): array {
  $u = current_user();
  if (!$u) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
  }
  return $u;
}

function require_role(string ...$roles): array {
  $u = require_login();
  if (!in_array($u['role'], $roles, true)) {
    http_response_code(403);
    echo "403 - Sem permissões.";
    exit;
  }
  return $u;
}