<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

$err = null;
$ok  = null;

// ano letivo selecionado
$academicYear = trim((string)($_GET['academic_year'] ?? ($_POST['academic_year'] ?? (date('Y') . '/' . (date('Y') + 1)))));

// 1) Bloqueio: só permitir se tiver matrícula ativa no ano letivo
$st = $pdo->prepare("
  SELECT COUNT(*) AS c
  FROM student_course_enrollments
  WHERE user_id=? AND academic_year=? AND status='active'
");
$st->execute([$u['id'], $academicYear]);
$hasActiveEnrollment = ((int)$st->fetch()['c'] > 0);

// UCs ativas (para o dropdown; só usado se desbloqueado)
$ucs = $pdo->query("SELECT id, code, name FROM ucs WHERE active=1 ORDER BY name")->fetchAll();

// inscrições atuais do aluno nesse ano
$stmt = $pdo->prepare("
  SELECT sue.id, sue.uc_id, sue.academic_year, sue.status, ucs.code, ucs.name
  FROM student_uc_enrollments sue
  JOIN ucs ON ucs.id = sue.uc_id
  WHERE sue.user_id=? AND sue.academic_year=?
  ORDER BY ucs.name
");
$stmt->execute([$u['id'], $academicYear]);
$current = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$hasActiveEnrollment) {
    $err = "Não podes inscrever UCs sem matrícula ativa no ano letivo selecionado.";
  } else {
    $action = (string)($_POST['action'] ?? '');
    $ucId = (int)($_POST['uc_id'] ?? 0);
    $academicYear = trim((string)($_POST['academic_year'] ?? $academicYear));

    if ($academicYear === '') {
      $err = "Ano letivo inválido.";
    } elseif ($ucId <= 0) {
      $err = "Seleciona uma UC.";
    } elseif (!in_array($action, ['enroll','cancel'], true)) {
      $err = "Ação inválida.";
    } else {
      if ($action === 'enroll') {
        // cria inscrição ou reativa
        $st = $pdo->prepare("
          INSERT INTO student_uc_enrollments (user_id, uc_id, academic_year, status)
          VALUES (?, ?, ?, 'enrolled')
          ON DUPLICATE KEY UPDATE status='enrolled'
        ");
        $st->execute([$u['id'], $ucId, $academicYear]);
        $ok = "Inscrição na UC guardada.";
      } else {
        // cancelar (não apaga, mantém histórico)
        $st = $pdo->prepare("
          UPDATE student_uc_enrollments
          SET status='cancelled'
          WHERE user_id=? AND uc_id=? AND academic_year=?
        ");
        $st->execute([$u['id'], $ucId, $academicYear]);
        $ok = "Inscrição cancelada.";
      }

      header('Location: ' . BASE_URL . '/aluno/inscricoes_uc.php?academic_year=' . urlencode($academicYear));
      exit;
    }
  }
}

$title = "Inscrições em UCs";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Inscrições em UCs</h1>
      <div class="subtle">Inscrições para elegibilidade automática nas pautas (RF5).</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Painel</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Estado</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
      <h2 style="margin:0 0 10px;">Ano letivo</h2>
      <form method="get">
        <label>Ano letivo</label>
        <input name="academic_year" value="<?= htmlspecialchars($academicYear) ?>" placeholder="ex: 2025/2026" required>
        <div class="row" style="margin-top:12px;">
          <button class="btn btn-ghost" type="submit">Carregar</button>
        </div>
      </form>

      <hr>

      <div class="subtle">
        Matrícula ativa neste ano letivo:
        <strong><?= $hasActiveEnrollment ? 'Sim' : 'Não' ?></strong>
      </div>
      <?php if (!$hasActiveEnrollment): ?>
        <div class="alert" style="margin-top:10px;">
          Para inscrever UCs, tens de ter um pedido de matrícula <strong>aprovado</strong> e matrícula <strong>ativa</strong> neste ano letivo.
        </div>
        <div class="row">
          <a class="btn" href="<?= BASE_URL ?>/aluno/pedido_matricula.php">Pedido de matrícula</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="card" style="box-shadow:none; margin:0;">
      <h2 style="margin:0 0 10px;">Inscrever numa UC</h2>

      <?php if (!$hasActiveEnrollment): ?>
        <div class="subtle">Função indisponível (sem matrícula ativa).</div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action" value="enroll">
          <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>">

          <label>UC</label>
          <select name="uc_id" required>
            <option value="">-- selecionar --</option>
            <?php foreach ($ucs as $uc): ?>
              <option value="<?= (int)$uc['id'] ?>">
                <?= htmlspecialchars($uc['code'] . ' — ' . $uc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div class="row" style="margin-top:12px;">
            <button class="btn" type="submit">Inscrever</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <hr>

  <h2 style="margin:0 0 10px;">As tuas UCs em <?= htmlspecialchars($academicYear) ?></h2>

  <?php if (!$current): ?>
    <div class="alert">Ainda não tens inscrições em UCs neste ano letivo.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>UC</th>
            <th>Estado</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($current as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['code'] . ' — ' . $r['name']) ?></td>
            <td>
              <span class="badge <?= $r['status']==='enrolled' ? 'ok' : 'warn' ?>">
                <?= htmlspecialchars($r['status']) ?>
              </span>
            </td>
            <td>
              <?php if (!$hasActiveEnrollment): ?>
                <span class="subtle">—</span>
              <?php else: ?>
                <?php if ($r['status'] === 'enrolled'): ?>
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>">
                    <input type="hidden" name="uc_id" value="<?= (int)$r['uc_id'] ?>">
                    <button class="btn btn-ghost" type="submit">Cancelar</button>
                  </form>
                <?php else: ?>
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>">
                    <input type="hidden" name="uc_id" value="<?= (int)$r['uc_id'] ?>">
                    <button class="btn" type="submit">Reativar</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>