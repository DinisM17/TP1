<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$cfg = require __DIR__ . '/../app/config.php';
$pdo = db();

// buscar ficha atual
$stmt = $pdo->prepare("SELECT id, status, photo_path FROM student_profiles WHERE user_id=? LIMIT 1");
$stmt->execute([$u['id']]);
$current = $stmt->fetch();

$status = $current['status'] ?? 'draft';
if (!in_array($status, ['draft','rejected'], true)) {
  http_response_code(400);
  echo "Não podes editar a ficha neste estado.";
  exit;
}

$full_name = trim((string)($_POST['full_name'] ?? ''));
$birth_date = (string)($_POST['birth_date'] ?? '');
$phone = trim((string)($_POST['phone'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$desired_course_id = (int)($_POST['desired_course_id'] ?? 0);

$errors = [];
if ($full_name === '') $errors[] = "Nome é obrigatório.";
if ($desired_course_id <= 0) $errors[] = "Seleciona um curso pretendido.";

$photo_path = $current['photo_path'] ?? null;

// upload foto (opcional)
if (!empty($_FILES['photo']) && (int)($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  if ((int)$_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Erro no upload da fotografia.";
  } else {
    if ((int)$_FILES['photo']['size'] > (int)$cfg['upload']['max_bytes']) {
      $errors[] = "A fotografia excede o tamanho máximo (2MB).";
    } else {
      $tmp = (string)$_FILES['photo']['tmp_name'];
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime = $finfo->file($tmp) ?: '';

      $allowed = $cfg['upload']['allowed_mime'];
      if (!isset($allowed[$mime])) {
        $errors[] = "Formato inválido. Só JPG/PNG.";
      } else {
        $ext = $allowed[$mime];

        if (!is_dir($cfg['upload']['photos_dir'])) {
          @mkdir($cfg['upload']['photos_dir'], 0777, true);
        }

        $filename = 'u' . $u['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest_fs = rtrim($cfg['upload']['photos_dir'], '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmp, $dest_fs)) {
          $errors[] = "Não foi possível guardar a fotografia.";
        } else {
          $photo_path = BASE_URL . "/public/uploads/photos/" . $filename;
        }
      }
    }
  }
}

if ($errors) {
  http_response_code(400);
  echo "<p>Erros:</p><ul>";
  foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
  echo "</ul><p><a href='" . BASE_URL . "/aluno/ficha.php'>Voltar</a></p>";
  exit;
}

if ($current) {
  $stmt = $pdo->prepare("
    UPDATE student_profiles
    SET full_name=?, birth_date=NULLIF(?,''), phone=?, address=?, desired_course_id=?, photo_path=?
    WHERE user_id=?
  ");
  $stmt->execute([$full_name, $birth_date, $phone, $address, $desired_course_id, $photo_path, $u['id']]);
} else {
  $stmt = $pdo->prepare("
    INSERT INTO student_profiles
      (user_id, full_name, birth_date, phone, address, desired_course_id, photo_path, status)
    VALUES
      (?, ?, NULLIF(?,''), ?, ?, ?, ?, 'draft')
  ");
  $stmt->execute([$u['id'], $full_name, $birth_date, $phone, $address, $desired_course_id, $photo_path]);
}

header('Location: ' . BASE_URL . '/aluno/ficha.php');
exit;