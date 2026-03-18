<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';
require_once __DIR__ . '/../app/auth.php';

$pdo = db();

$name = trim((string)($_POST['name'] ?? ''));
$email = trim(mb_strtolower((string)($_POST['email'] ?? '')));
$pass = (string)($_POST['password'] ?? '');
$pass2 = (string)($_POST['password2'] ?? '');

$errors = [];
if ($name === '') $errors[] = "Nome é obrigatório.";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";
if (strlen($pass) < 6) $errors[] = "Password deve ter pelo menos 6 caracteres.";
if ($pass !== $pass2) $errors[] = "Passwords não coincidem.";

if ($errors) {
  http_response_code(400);
  echo "<p>Erros:</p><ul>";
  foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
  echo "</ul><p><a href='" . BASE_URL . "/public/register.php'>Voltar</a></p>";
  exit;
}

// role aluno
$roleRow = $pdo->query("SELECT id FROM roles WHERE name='aluno' LIMIT 1")->fetch();
$roleId = (int)($roleRow['id'] ?? 0);
if ($roleId <= 0) {
  http_response_code(500);
  echo "Erro interno: role 'aluno' não existe.";
  exit;
}

try {
  $st = $pdo->prepare("
    INSERT INTO users (role_id, email, password_hash, name, active)
    VALUES (?, ?, ?, ?, 1)
  ");
  $st->execute([$roleId, $email, password_hash($pass, PASSWORD_DEFAULT), $name]);

  // login automático
  auth_login($email, $pass);

  // vai direto para a ficha
  header('Location: ' . BASE_URL . '/aluno/ficha.php');
  exit;

} catch (Throwable $e) {
  http_response_code(400);
  echo "<p>Email já registado.</p>";
  echo "<p><a href='" . BASE_URL . "/public/register.php'>Voltar</a></p>";
  exit;
}