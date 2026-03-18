<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('gestor');
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "ID inválido."; exit; }

$stmt = $pdo->prepare("
  SELECT
    sp.*,
    usr.email,
    usr.name AS user_name,
    c.name AS desired_course_name
  FROM student_profiles sp
  JOIN users usr ON usr.id = sp.user_id
  LEFT JOIN courses c ON c.id = sp.desired_course_id
  WHERE sp.id=?
  LIMIT 1
");
$stmt->execute([$id]);
$sp = $stmt->fetch();
if (!$sp) { http_response_code(404); echo "Ficha não encontrada."; exit; }

$err = null; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $notes  = trim((string)($_POST['decision_notes'] ?? ''));

  if (!in_array($action, ['approve','reject'], true)) {
    $err = "Ação inválida.";
  } elseif ((string)$sp['status'] !== 'submitted') {
    $err = "Esta ficha já não está submetida.";
  } else {
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';

    $st = $pdo->prepare("
      UPDATE student_profiles
      SET status=?,
          decided_by=?,
          decided_at=NOW(),
          decision_notes=?
      WHERE id=? AND status='submitted'
    ");
    $st->execute([$newStatus, $u['id'], $notes, $id]);

    if ($st->rowCount() !== 1) {
      $err = "Não foi possível guardar (talvez já tenha sido decidida).";
    } else {
      $ok = "Decisão guardada.";
      $stmt->execute([$id]);
      $sp = $stmt->fetch();
    }
  }
}

$title = "Decidir ficha";
require __DIR__ . '/../templates/header.php';

$status = (string)$sp['status'];
$badge = $status === 'approved' ? 'ok' : ($status === 'rejected' ? 'bad' : 'warn');
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Decidir ficha #<?= (int)$sp['id'] ?></h1>
      <div class="subtle"><?= htmlspecialchars((string)$sp['full_name']) ?> · <?= htmlspecialchars((string)$sp['email']) ?></div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/fichas_submetidas.php">Voltar</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Dashboard</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="kpi" style="grid-template-columns: 1fr 1fr 1fr;">
    <div class="box">
      <div class="subtle">Curso pretendido</div>
      <div class="big"><?= htmlspecialchars((string)($sp['desired_course_name'] ?? '—')) ?></div>
    </div>
    <div class="box">
      <div class="subtle">Estado</div>
      <div class="big"><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></div>
    </div>
    <div class="box">
      <div class="subtle">Submetida em</div>
      <div class="big"><?= htmlspecialchars((string)($sp['submitted_at'] ?? '—')) ?></div>
    </div>
  </div>

  <hr>

  <?php if (!empty($sp['photo_path'])): ?>
    <div class="row">
      <img class="avatar" src="<?= htmlspecialchars((string)$sp['photo_path']) ?>" alt="Foto">
      <div class="subtle">
        Foto carregada.<br>
        (Caminho: <?= htmlspecialchars((string)$sp['photo_path']) ?>)
      </div>
    </div>
    <hr>
  <?php endif; ?>

  <?php if ($status !== 'submitted'): ?>
    <div class="alert">
      Esta ficha já foi decidida.
      <?php if (!empty($sp['decided_at'])): ?>
        <div class="subtle" style="margin-top:6px;">Decidida em: <?= htmlspecialchars((string)$sp['decided_at']) ?></div>
      <?php endif; ?>
      <?php if (!empty($sp['decision_notes'])): ?>
        <div class="subtle" style="margin-top:6px; white-space:pre-wrap;"><?= htmlspecialchars((string)$sp['decision_notes']) ?></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <form method="post">
      <label>Observações / justificação (opcional)</label>
      <textarea name="decision_notes" placeholder="Ex.: Dados corretos / Falta documento X..."></textarea>

      <div class="row" style="margin-top:14px;">
        <button class="btn" type="submit" name="action" value="approve">Aprovar</button>
        <button class="btn btn-ghost" type="submit" name="action" value="reject">Rejeitar</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>