<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

// Ano letivo “corrente” (ajusta se quiseres seleção manual)
$academicYear = date('Y') . '/' . (date('Y') + 1);

// Ficha
$st = $pdo->prepare("
  SELECT status, desired_course_id, submitted_at, decided_at
  FROM student_profiles
  WHERE user_id=?
  LIMIT 1
");
$st->execute([$u['id']]);
$sp = $st->fetch();

$profileStatus   = $sp['status'] ?? null;
$desiredCourseId = isset($sp['desired_course_id']) ? (int)$sp['desired_course_id'] : 0;

// Nome do curso pretendido
$desiredCourseName = null;
if ($desiredCourseId > 0) {
  $st = $pdo->prepare("SELECT name FROM courses WHERE id=? LIMIT 1");
  $st->execute([$desiredCourseId]);
  $desiredCourseName = $st->fetchColumn() ?: null;
}

// Pedido matrícula (último)
$st = $pdo->prepare("
  SELECT id, status, created_at, decided_at, course_id, course2_id, course3_id
  FROM enrollment_requests
  WHERE user_id=?
  ORDER BY created_at DESC
  LIMIT 1
");
$st->execute([$u['id']]);
$er = $st->fetch();

$enrollStatus = $er['status'] ?? null;

// Matrícula ativa no ano letivo corrente
$st = $pdo->prepare("
  SELECT COUNT(*) FROM student_course_enrollments
  WHERE user_id=? AND academic_year=? AND status='active'
");
$st->execute([$u['id'], $academicYear]);
$hasActiveEnrollment = ((int)$st->fetchColumn() > 0);

// Contagem de notas (linhas na pauta) para o aluno
$st = $pdo->prepare("
  SELECT COUNT(*)
  FROM grade_sheet_rows r
  JOIN grade_sheets gs ON gs.id = r.grade_sheet_id
  WHERE r.student_user_id=?
");
$st->execute([$u['id']]);
$gradesCount = (int)$st->fetchColumn();

function badge_class(?string $status): string {
  return match ($status) {
    'approved' => 'ok',
    'submitted' => 'warn',
    'rejected' => 'bad',
    'draft' => '',
    default => ''
  };
}

function step_state(bool $done, bool $blocked): array {
  if ($done) return ['ok', 'Concluído'];
  if ($blocked) return ['bad', 'Bloqueado'];
  return ['warn', 'Pendente'];
}

// Regras de desbloqueio (fluxo realista)
$step1_done = in_array((string)$profileStatus, ['submitted','approved'], true);
$step1_blocked = false;

// Pedido só deve ser possível se ficha submetida/aprovada e com curso pretendido
$step2_done = (bool)$er; // existe pedido (qualquer estado)
$step2_blocked = !$step1_done || $desiredCourseId <= 0;

// Decisão/matrícula: concluído se matrícula ativa (ou pedido aprovado)
$step3_done = $hasActiveEnrollment || ($enrollStatus === 'approved');
$step3_blocked = !$step2_done;

// Inscrições em UCs: só com matrícula ativa
$step4_done = false; // operacional
$step4_blocked = !$hasActiveEnrollment;

// Notas: disponível quando houver pelo menos 1 linha em pautas
$step5_done = ($gradesCount > 0);
$step5_blocked = ($gradesCount === 0);

$title = "Área do Aluno";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Área do Aluno</h1>
      <div class="subtle">Segue estes passos para completares o processo.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Estado</a>
    </div>
  </div>

  <div class="kpi" style="margin-top:14px;">
    <div class="box">
      <div class="subtle">Ano letivo</div>
      <div class="big"><?= htmlspecialchars($academicYear) ?></div>
    </div>
    <div class="box">
      <div class="subtle">Curso pretendido</div>
      <div class="big"><?= htmlspecialchars($desiredCourseName ?? '—') ?></div>
      <div class="subtle" style="margin-top:8px; font-size:12px;">
        (definido na ficha do aluno)
      </div>
    </div>
    <div class="box">
      <div class="subtle">Matrícula ativa</div>
      <div class="big">
        <span class="badge <?= $hasActiveEnrollment ? 'ok' : 'warn' ?>">
          <?= $hasActiveEnrollment ? 'Sim' : 'Não' ?>
        </span>
      </div>
    </div>
  </div>

  <hr>

  <!-- Passo 1 -->
  <?php [$b1,$l1] = step_state($step1_done, $step1_blocked); ?>
  <div class="card" style="box-shadow:none; margin:0 0 12px; border-style:dashed;">
    <div class="row" style="justify-content:space-between;">
      <div>
        <div class="subtle">Passo 1</div>
        <div style="font-weight:950; font-size:18px;">Ficha do aluno</div>
        <div class="subtle" style="margin-top:6px;">
          Estado:
          <span class="badge <?= badge_class($profileStatus) ?>"><?= htmlspecialchars((string)($profileStatus ?? 'não iniciada')) ?></span>
          <?php if (!empty($sp['submitted_at'])): ?>
            <span class="subtle" style="margin-left:8px; font-size:12px;">
              Submetida: <?= htmlspecialchars((string)$sp['submitted_at']) ?>
            </span>
          <?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= $b1 ?>"><?= $l1 ?></span>
        <div class="row" style="margin-top:10px; justify-content:flex-end;">
          <a class="btn" href="<?= BASE_URL ?>/aluno/ficha.php">Abrir ficha</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Passo 2 -->
  <?php [$b2,$l2] = step_state($step2_done, $step2_blocked); ?>
  <div class="card" style="box-shadow:none; margin:0 0 12px; border-style:dashed;">
    <div class="row" style="justify-content:space-between;">
      <div>
        <div class="subtle">Passo 2</div>
        <div style="font-weight:950; font-size:18px;">Pedido de matrícula/inscrição</div>
        <div class="subtle" style="margin-top:6px;">
          <?= $er ? ("Último pedido: #" . (int)$er['id'] . " · " . htmlspecialchars((string)$er['status'])) : "Ainda não existe pedido." ?>
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= $b2 ?>"><?= $l2 ?></span>
        <div class="row" style="margin-top:10px; justify-content:flex-end;">
          <?php if ($step2_blocked): ?>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/ficha.php">Submeter ficha primeiro</a>
          <?php else: ?>
            <a class="btn" href="<?= BASE_URL ?>/aluno/pedido_matricula.php">Abrir pedido</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Passo 3 -->
  <?php [$b3,$l3] = step_state($step3_done, $step3_blocked); ?>
  <div class="card" style="box-shadow:none; margin:0 0 12px; border-style:dashed;">
    <div class="row" style="justify-content:space-between;">
      <div>
        <div class="subtle">Passo 3</div>
        <div style="font-weight:950; font-size:18px;">Decisão e matrícula</div>
        <div class="subtle" style="margin-top:6px;">
          <?= $hasActiveEnrollment ? "A tua matrícula está ativa no ano letivo corrente." : "Aguarda decisão dos Serviços Académicos." ?>
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= $b3 ?>"><?= $l3 ?></span>
        <div class="row" style="margin-top:10px; justify-content:flex-end;">
          <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Ver estado</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Passo 4 -->
  <div class="card" style="box-shadow:none; margin:0 0 12px; border-style:dashed;">
    <div class="row" style="justify-content:space-between;">
      <div>
        <div class="subtle">Passo 4</div>
        <div style="font-weight:950; font-size:18px;">Inscrição em UCs</div>
        <div class="subtle" style="margin-top:6px;">
          Inscreve-te nas UCs para apareceres automaticamente nas pautas.
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= $step4_blocked ? 'bad' : 'warn' ?>">
          <?= $step4_blocked ? 'Bloqueado' : 'Disponível' ?>
        </span>
        <div class="row" style="margin-top:10px; justify-content:flex-end;">
          <?php if ($step4_blocked): ?>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/pedido_matricula.php">Aguardar matrícula</a>
          <?php else: ?>
            <a class="btn" href="<?= BASE_URL ?>/aluno/inscricoes_uc.php">Inscrever UCs</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Passo 5 -->
  <?php [$b5,$l5] = step_state($step5_done, $step5_blocked); ?>
  <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
    <div class="row" style="justify-content:space-between;">
      <div>
        <div class="subtle">Passo 5</div>
        <div style="font-weight:950; font-size:18px;">Notas</div>
        <div class="subtle" style="margin-top:6px;">
          Registos encontrados: <strong><?= $gradesCount ?></strong>
        </div>
      </div>
      <div style="text-align:right;">
        <span class="badge <?= $b5 ?>"><?= $l5 ?></span>
        <div class="row" style="margin-top:10px; justify-content:flex-end;">
          <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/notas.php">Ver notas</a>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>