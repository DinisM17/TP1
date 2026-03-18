<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('funcionario');
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$stmt = $pdo->prepare("
  SELECT
    er.*,
    stu.name  AS student_name,
    stu.email AS student_email,
    c1.name AS course1_name,
    c2.name AS course2_name,
    c3.name AS course3_name
  FROM enrollment_requests er
  JOIN users stu ON stu.id = er.user_id
  JOIN courses c1 ON c1.id = er.course_id
  LEFT JOIN courses c2 ON c2.id = er.course2_id
  LEFT JOIN courses c3 ON c3.id = er.course3_id
  WHERE er.id=?
  LIMIT 1
");
$stmt->execute([$id]);
$er = $stmt->fetch();
if (!$er) { http_response_code(404); echo "Pedido não encontrado."; exit; }

$err = null;
$ok  = null;

function badge_for(string $status): string {
  return $status === 'approved' ? 'ok' : ($status === 'rejected' ? 'bad' : 'warn');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $notes  = trim((string)($_POST['decision_notes'] ?? ''));

  if (!in_array($action, ['approve','reject'], true)) {
    $err = "Ação inválida.";
  } elseif ((string)$er['status'] !== 'pending') {
    $err = "Este pedido já não está pendente.";
  } else {
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

    $academicYear = trim((string)($_POST['academic_year'] ?? ''));
    if ($newStatus === 'approved' && $academicYear === '') {
      $academicYear = date('Y') . '/' . (date('Y') + 1);
    }

    $approvedChoice = (int)($_POST['approved_choice'] ?? 1);
    if (!in_array($approvedChoice, [1,2,3], true)) $approvedChoice = 1;

    $approvedCourseId = null;
    if ($newStatus === 'approved') {
      if ($approvedChoice === 1) $approvedCourseId = (int)$er['course_id'];
      if ($approvedChoice === 2) $approvedCourseId = $er['course2_id'] ? (int)$er['course2_id'] : null;
      if ($approvedChoice === 3) $approvedCourseId = $er['course3_id'] ? (int)$er['course3_id'] : null;
      if (!$approvedCourseId) $err = "A opção escolhida não existe (2ª/3ª opção vazia).";
    }

    if (!$err) {
      $pdo->beginTransaction();
      try {
        $st = $pdo->prepare("
          UPDATE enrollment_requests
          SET status=?, decided_by=?, decided_at=NOW(), decision_notes=?
          WHERE id=? AND status='pending'
        ");
        $st->execute([$newStatus, $u['id'], $notes, $id]);

        if ($st->rowCount() !== 1) {
          throw new RuntimeException("Não foi possível atualizar (já decidido).");
        }

        if ($newStatus === 'approved') {
          $st = $pdo->prepare("
            INSERT INTO student_course_enrollments (user_id, course_id, academic_year, status)
            VALUES (?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE status='active'
          ");
          $st->execute([(int)$er['user_id'], (int)$approvedCourseId, $academicYear]);
        }

        $pdo->commit();
        $ok = "Decisão guardada.";

        $stmt->execute([$id]);
        $er = $stmt->fetch();
      } catch (Throwable $e) {
        $pdo->rollBack();
        $err = "Erro: " . $e->getMessage();
      }
    }
  }
}

$title = "Decidir pedido";
require __DIR__ . '/../templates/header.php';

$status = (string)$er['status'];
$badge = badge_for($status);

$opt1 = "1ª — " . (string)$er['course1_name'];
$opt2 = $er['course2_id'] ? ("2ª — " . (string)$er['course2_name']) : "2ª — (não indicada)";
$opt3 = $er['course3_id'] ? ("3ª — " . (string)$er['course3_name']) : "3ª — (não indicada)";
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Pedido #<?= (int)$er['id'] ?></h1>
      <div class="subtle"><?= htmlspecialchars((string)$er['student_name']) ?> · <?= htmlspecialchars((string)$er['student_email']) ?></div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pedidos.php">Voltar</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pautas.php">Pautas</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="kpi" style="grid-template-columns: 1fr 1fr 1fr;">
    <div class="box">
      <div class="subtle">Estado</div>
      <div class="big"><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></div>
      <div class="subtle" style="margin-top:8px; font-size:12px;">Criado: <?= htmlspecialchars((string)$er['created_at']) ?></div>
    </div>
    <div class="box">
      <div class="subtle">Opções</div>
      <div class="subtle" style="margin-top:8px;">
        • <?= htmlspecialchars($opt1) ?><br>
        • <?= htmlspecialchars($opt2) ?><br>
        • <?= htmlspecialchars($opt3) ?>
      </div>
    </div>
    <div class="box">
      <div class="subtle">Auditoria</div>
      <div class="subtle" style="margin-top:8px;">
        <?= !empty($er['decided_at']) ? ("Decidido: " . htmlspecialchars((string)$er['decided_at'])) : "Ainda sem decisão" ?>
      </div>
    </div>
  </div>

  <hr>

  <?php if ($status !== 'pending'): ?>
    <div class="alert">
      Este pedido já foi decidido.
      <?php if (!empty($er['decision_notes'])): ?>
        <div class="subtle" style="margin-top:8px; white-space:pre-wrap;"><?= htmlspecialchars((string)$er['decision_notes']) ?></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <form method="post" class="grid">
      <div>
        <label>Ano letivo (se aprovar)</label>
        <input name="academic_year" value="<?= htmlspecialchars(date('Y') . '/' . (date('Y')+1)) ?>" placeholder="ex: 2025/2026">
      </div>

      <div>
        <label>Curso a aprovar</label>
        <select name="approved_choice">
          <option value="1">1ª opção</option>
          <option value="2" <?= $er['course2_id'] ? '' : 'disabled' ?>>2ª opção</option>
          <option value="3" <?= $er['course3_id'] ? '' : 'disabled' ?>>3ª opção</option>
        </select>
      </div>

      <div style="grid-column: 1 / -1;">
        <label>Observações (opcional)</label>
        <textarea name="decision_notes" placeholder="Ex.: Aprovado na 2ª opção / Falta documento..."></textarea>
      </div>

      <div class="row" style="grid-column: 1 / -1; margin-top:6px;">
        <button class="btn" type="submit" name="action" value="approve">Aprovar</button>
        <button class="btn btn-ghost" type="submit" name="action" value="reject">Rejeitar</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>