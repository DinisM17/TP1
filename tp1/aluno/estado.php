<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

$stmt = $pdo->prepare("
  SELECT sp.*, c.name AS desired_course_name
  FROM student_profiles sp
  LEFT JOIN courses c ON c.id = sp.desired_course_id
  WHERE sp.user_id=?
  LIMIT 1
");
$stmt->execute([$u['id']]);
$sp = $stmt->fetch();

$title = "Estado";
require __DIR__ . '/../templates/header.php';

function badge_class(?string $status): string {
  return match ($status) {
    'approved' => 'ok',
    'submitted' => 'warn',
    'rejected' => 'bad',
    'draft' => '',
    default => ''
  };
}
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Estado</h1>
      <div class="subtle">Acompanha aqui o estado da tua ficha e as observações.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/ficha.php">Abrir ficha</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Dashboard</a>
    </div>
  </div>

  <?php if (!$sp): ?>
    <div class="alert">Ainda não tens ficha criada.</div>
    <a class="btn" href="<?= BASE_URL ?>/aluno/ficha.php">Criar ficha</a>

  <?php else: ?>
    <div class="kpi" style="grid-template-columns: 1fr 1fr 1fr;">
      <div class="box">
        <div class="subtle">Estado da ficha</div>
        <div class="big">
          <span class="badge <?= badge_class((string)$sp['status']) ?>">
            <?= htmlspecialchars((string)$sp['status']) ?>
          </span>
        </div>
      </div>

      <div class="box">
        <div class="subtle">Curso pretendido</div>
        <div class="big"><?= htmlspecialchars((string)($sp['desired_course_name'] ?? '—')) ?></div>
      </div>

      <div class="box">
        <div class="subtle">Fotografia</div>
        <div class="big">
          <?= !empty($sp['photo_path']) ? 'Carregada' : 'Não definida' ?>
        </div>
      </div>
    </div>

    <hr>

    <div class="grid">
      <div class="card" style="box-shadow:none; margin:0;">
        <h2 style="margin:0 0 8px;">Datas</h2>

        <div class="subtle">Submetida em</div>
        <div style="font-weight:900;">
          <?= !empty($sp['submitted_at']) ? htmlspecialchars((string)$sp['submitted_at']) : '—' ?>
        </div>

        <div class="subtle" style="margin-top:10px;">Decidida em</div>
        <div style="font-weight:900;">
          <?= !empty($sp['decided_at']) ? htmlspecialchars((string)$sp['decided_at']) : '—' ?>
        </div>
      </div>

      <div class="card" style="box-shadow:none; margin:0;">
        <h2 style="margin:0 0 8px;">Observações</h2>
        <?php if (!empty($sp['decision_notes'])): ?>
          <div style="white-space:pre-wrap; line-height:1.4;">
            <?= htmlspecialchars((string)$sp['decision_notes']) ?>
          </div>
        <?php else: ?>
          <div class="subtle">Sem observações.</div>
        <?php endif; ?>

        <?php if ((string)$sp['status'] === 'rejected'): ?>
          <div class="alert" style="margin-top:12px;">
            A tua ficha foi rejeitada. Corrige e submete novamente.
          </div>
          <a class="btn" href="<?= BASE_URL ?>/aluno/ficha.php">Corrigir ficha</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($sp['photo_path'])): ?>
      <hr>
      <div class="row">
        <img class="avatar" src="<?= htmlspecialchars((string)$sp['photo_path']) ?>" alt="Foto">
        <div class="subtle">
          Foto carregada na ficha.<br>
          Se precisares, podes substituir na página da ficha.
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>