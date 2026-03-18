<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

$courses = $pdo->query("SELECT id, name FROM courses WHERE active=1 ORDER BY name")->fetchAll();

// buscar ficha e curso pretendido
$st = $pdo->prepare("
  SELECT status, desired_course_id
  FROM student_profiles
  WHERE user_id=?
  LIMIT 1
");
$st->execute([$u['id']]);
$sp = $st->fetch();

$profileStatus = $sp['status'] ?? null;
$desiredCourseId = isset($sp['desired_course_id']) ? (int)$sp['desired_course_id'] : 0;

$canRequest = in_array((string)$profileStatus, ['submitted','approved'], true) && $desiredCourseId > 0;

$err = null;
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$canRequest) {
    $err = "Para criar o pedido, tens de ter a ficha submetida (ou aprovada) e com curso pretendido selecionado.";
  } else {
    $c2 = (int)($_POST['course2_id'] ?? 0);
    $c3 = (int)($_POST['course3_id'] ?? 0);

    // 1ª opção é sempre o curso pretendido
    $c1 = $desiredCourseId;

    if (($c2 && $c2 === $c1) || ($c3 && ($c3 === $c1 || $c3 === $c2))) {
      $err = "As opções não podem repetir o mesmo curso.";
    } else {
      // impedir pedido pendente duplicado
      $st = $pdo->prepare("SELECT COUNT(*) AS c FROM enrollment_requests WHERE user_id=? AND status='pending'");
      $st->execute([$u['id']]);
      if ((int)$st->fetch()['c'] > 0) {
        $err = "Já tens um pedido pendente. Aguarda decisão.";
      } else {
        $st = $pdo->prepare("
          INSERT INTO enrollment_requests (user_id, course_id, course2_id, course3_id, status)
          VALUES (?, ?, ?, ?, 'pending')
        ");
        $st->execute([$u['id'], $c1, $c2 ?: null, $c3 ?: null]);
        $ok = "Pedido criado com sucesso.";
      }
    }
  }
}

$title = "Pedido de matrícula";
require __DIR__ . '/../templates/header.php';

// nome do curso pretendido
$desiredName = '—';
if ($desiredCourseId > 0) {
  foreach ($courses as $c) {
    if ((int)$c['id'] === $desiredCourseId) { $desiredName = (string)$c['name']; break; }
  }
}
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Pedido de matrícula/inscrição</h1>
      <div class="subtle">A 1ª opção é o curso pretendido indicado na tua ficha.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Painel</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Estado</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <?php if (!$canRequest): ?>
    <div class="alert">
      Requisitos para criar pedido:
      <div class="subtle" style="margin-top:8px;">
        • Ficha em <strong>Submetida</strong> ou <strong>Aprovada</strong><br>
        • Curso pretendido selecionado na ficha
      </div>
    </div>
    <div class="row">
      <a class="btn" href="<?= BASE_URL ?>/aluno/ficha.php">Ir para a ficha</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Ver estado</a>
    </div>
  <?php else: ?>
    <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
      <div class="subtle">1ª opção (da ficha)</div>
      <div style="font-weight:950; font-size:18px; margin-top:6px;">
        <?= htmlspecialchars($desiredName) ?>
      </div>
    </div>

    <form method="post" class="grid" style="margin-top:12px;">
      <div>
        <label>2ª opção (opcional)</label>
        <select name="course2_id">
          <option value="">-- nenhum --</option>
          <?php foreach ($courses as $c): ?>
            <?php if ((int)$c['id'] === $desiredCourseId) continue; ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>3ª opção (opcional)</label>
        <select name="course3_id">
          <option value="">-- nenhum --</option>
          <?php foreach ($courses as $c): ?>
            <?php if ((int)$c['id'] === $desiredCourseId) continue; ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex; align-items:flex-end;">
        <button class="btn" type="submit" style="width:100%;">Submeter pedido</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>