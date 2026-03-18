<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

// cursos ativos
$courses = $pdo->query("SELECT id, name FROM courses WHERE active=1 ORDER BY name")->fetchAll();

// ficha atual (se existir)
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id=? LIMIT 1");
$stmt->execute([$u['id']]);
$profile = $stmt->fetch();

$profile = $profile ?: [
  'full_name' => $u['name'],
  'birth_date' => '',
  'phone' => '',
  'address' => '',
  'photo_path' => '',
  'desired_course_id' => '',
  'status' => 'draft',
  'submitted_at' => null,
];

$status = (string)$profile['status'];
$badgeClass = $status === 'approved' ? 'ok' : ($status === 'rejected' ? 'bad' : ($status === 'submitted' ? 'warn' : ''));

$title = "Ficha do Aluno";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Ficha do aluno</h1>
      <div class="subtle">Preenche os dados e submete para validação pelo Gestor Pedagógico.</div>
    </div>
    <div style="text-align:right;">
      <div class="subtle">Estado</div>
      <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
      <?php if (!empty($profile['submitted_at'])): ?>
        <div class="subtle" style="margin-top:6px; font-size:12px;">
          Submetida: <?= htmlspecialchars((string)$profile['submitted_at']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($status !== 'draft' && $status !== 'rejected'): ?>
    <div class="alert">
      A ficha já foi submetida. Nesta versão, só podes editar em <strong>rascunho</strong> (ou após rejeição).
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Ver estado</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Voltar</a>
    </div>

  <?php else: ?>
    <form class="card"
          action="<?= BASE_URL ?>/aluno/ficha_save.php"
          method="post"
          enctype="multipart/form-data"
          style="box-shadow:none; margin:0; border-style:dashed;">
      <div class="grid">
        <div>
          <label>Nome completo</label>
          <input name="full_name" required value="<?= htmlspecialchars((string)$profile['full_name']) ?>">
        </div>
        <div>
          <label>Data de nascimento</label>
          <input type="date" name="birth_date" value="<?= htmlspecialchars((string)$profile['birth_date']) ?>">
        </div>
      </div>

      <div class="grid">
        <div>
          <label>Telefone</label>
          <input name="phone" value="<?= htmlspecialchars((string)$profile['phone']) ?>" placeholder="ex: 912345678">
        </div>
        <div>
          <label>Curso pretendido</label>
          <select name="desired_course_id" required>
            <option value="">-- selecionar --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((string)$profile['desired_course_id'] === (string)$c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label>Morada</label>
      <input name="address" value="<?= htmlspecialchars((string)$profile['address']) ?>" placeholder="ex: Rua..., nº..., localidade">

      <label>Fotografia (JPG/PNG, máx 2MB)</label>
      <input type="file" name="photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png">

      <?php if (!empty($profile['photo_path'])): ?>
        <div class="row" style="margin-top:12px;">
          <img class="avatar" src="<?= htmlspecialchars((string)$profile['photo_path']) ?>" alt="Foto">
          <div class="subtle">
            Foto atual carregada.<br>
            Podes substituir escolhendo outro ficheiro.
          </div>
        </div>
      <?php endif; ?>

      <div class="row" style="margin-top:14px;">
        <button class="btn" type="submit">Guardar rascunho</button>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Ver estado</a>
      </div>
    </form>

    <div class="sticky-actions" style="margin-top:12px;">
  <form action="<?= BASE_URL ?>/aluno/ficha_submit.php" method="post" style="margin:0;">
    <button class="btn btn-ghost" type="submit">Submeter para validação</button>
  </form>
  <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Voltar</a>
</div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>