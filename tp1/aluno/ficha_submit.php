<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

$stmt = $pdo->prepare("SELECT id, status FROM student_profiles WHERE user_id=? LIMIT 1");
$stmt->execute([$u['id']]);
$p = $stmt->fetch();

if (!$p) {
  http_response_code(400);
  echo "Cria a ficha primeiro. <a href='" . BASE_URL . "/aluno/ficha.php'>Abrir ficha</a>";
  exit;
}

if (!in_array((string)$p['status'], ['draft','rejected'], true)) {
  http_response_code(400);
  echo "Não podes submeter neste estado. <a href='" . BASE_URL . "/aluno/estado.php'>Ver estado</a>";
  exit;
}

$stmt = $pdo->prepare("
  UPDATE student_profiles
  SET status='submitted',
      submitted_at=NOW(),
      decided_by=NULL,
      decided_at=NULL,
      decision_notes=NULL
  WHERE user_id=?
");
$stmt->execute([$u['id']]);

header('Location: ' . BASE_URL . '/aluno/estado.php');
exit;