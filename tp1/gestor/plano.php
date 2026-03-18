<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('gestor');
$pdo = db();

$err = null;
$ok  = null;

// filtros
$courseId = (int)($_GET['course_id'] ?? ($_POST['course_id'] ?? 0));

// listas para dropdowns
$courses = $pdo->query("SELECT id, code, name FROM courses WHERE active=1 ORDER BY name")->fetchAll();
$ucs     = $pdo->query("SELECT id, code, name FROM ucs WHERE active=1 ORDER BY name")->fetchAll();

// ação: adicionar ao plano
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'add') {
  $courseId = (int)($_POST['course_id'] ?? 0);
  $ucId = (int)($_POST['uc_id'] ?? 0);
  $yearNo = (int)($_POST['year_no'] ?? 0);
  $semNo  = (int)($_POST['semester_no'] ?? 0);

  if ($courseId <= 0) $err = "Seleciona um curso.";
  elseif ($ucId <= 0) $err = "Seleciona uma UC.";
  elseif ($yearNo < 1 || $yearNo > 10) $err = "Ano inválido.";
  elseif ($semNo < 1 || $semNo > 4) $err = "Semestre inválido.";
  else {
    try {
      $st = $pdo->prepare("
        INSERT INTO study_plans (course_id, uc_id, year_no, semester_no, active)
        VALUES (?, ?, ?, ?, 1)
      ");
      $st->execute([$courseId, $ucId, $yearNo, $semNo]);
      $ok = "UC adicionada ao plano de estudos.";
    } catch (Throwable $e) {
      // normalmente: duplicate key por UNIQUE(course_id, uc_id, year_no, semester_no)
      $err = "Não foi possível adicionar. (Já existe esta UC no mesmo curso/ano/semestre.)";
    }
  }
}

// ação: toggle active (ativar/desativar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'toggle') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    $err = "Registo inválido.";
  } else {
    $st = $pdo->prepare("UPDATE study_plans SET active = IF(active=1,0,1) WHERE id=?");
    $st->execute([$id]);
    $ok = "Estado atualizado.";
  }
}

// carregar lista do plano (filtrada por curso se selecionado)
$args = [];
$where = "";
if ($courseId > 0) {
  $where = "WHERE sp.course_id = ?";
  $args[] = $courseId;
}

$st = $pdo->prepare("
  SELECT
    sp.id,
    sp.course_id,
    sp.uc_id,
    sp.year_no,
    sp.semester_no,
    sp.active,

    c.code AS course_code,
    c.name AS course_name,

    u.code AS uc_code,
    u.name AS uc_name

  FROM study_plans sp
  JOIN courses c ON c.id = sp.course_id
  JOIN ucs u     ON u.id = sp.uc_id
  $where
  ORDER BY c.name, sp.year_no, sp.semester_no, u.name
");
$st->execute($args);
$rows = $st->fetchAll();

$title = "Plano de Estudos";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Plano de Estudos</h1>
      <div class="subtle">Associar UCs a cursos com ano/semestre e gerir o plano.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/area.php">Área do Gestor</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/cursos.php">Cursos</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/ucs.php">UCs</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
      <h2 style="margin:0 0 10px;">Adicionar ao plano</h2>

      <form method="post" class="grid" style="grid-template-columns: 1fr 1fr;">
        <input type="hidden" name="action" value="add">

        <div style="grid-column: 1 / -1;">
          <label>Curso</label>
          <select name="course_id" required>
            <option value="">-- selecionar --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $courseId === (int)$c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['code'] . ' — ' . $c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="grid-column: 1 / -1;">
          <label>UC</label>
          <select name="uc_id" required>
            <option value="">-- selecionar --</option>
            <?php foreach ($ucs as $uc): ?>
              <option value="<?= (int)$uc['id'] ?>">
                <?= htmlspecialchars($uc['code'] . ' — ' . $uc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label>Ano</label>
          <input type="number" name="year_no" min="1" max="10" value="1" required>
        </div>

        <div>
          <label>Semestre</label>
          <input type="number" name="semester_no" min="1" max="4" value="1" required>
        </div>

        <div style="grid-column: 1 / -1;">
          <button class="btn" type="submit" style="width:100%;">Adicionar</button>
        </div>

        <div class="subtle" style="grid-column: 1 / -1; font-size:12px;">
          Regra: não é permitida duplicação da mesma UC no mesmo curso/ano/semestre.
        </div>
      </form>
    </div>

    <div class="card" style="box-shadow:none; margin:0;">
      <h2 style="margin:0 0 10px;">Filtro</h2>
      <form method="get">
        <label>Curso</label>
        <select name="course_id">
          <option value="">-- todos --</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $courseId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['code'] . ' — ' . $c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="row" style="margin-top:12px;">
          <button class="btn btn-ghost" type="submit">Aplicar</button>
          <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/plano.php">Limpar</a>
          <span class="badge warn">Registos: <?= count($rows) ?></span>
        </div>
      </form>
    </div>
  </div>

  <hr>

  <h2 style="margin:0 0 10px;">Registos do plano</h2>

  <?php if (!$rows): ?>
    <div class="alert">Sem registos para o filtro atual.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Curso</th>
            <th>UC</th>
            <th>Ano</th>
            <th>Semestre</th>
            <th>Ativo</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <div style="font-weight:900;"><?= htmlspecialchars($r['course_code']) ?></div>
              <div class="subtle" style="font-size:12px;"><?= htmlspecialchars($r['course_name']) ?></div>
            </td>
            <td>
              <div style="font-weight:900;"><?= htmlspecialchars($r['uc_code']) ?></div>
              <div class="subtle" style="font-size:12px;"><?= htmlspecialchars($r['uc_name']) ?></div>
            </td>
            <td><?= (int)$r['year_no'] ?></td>
            <td><?= (int)$r['semester_no'] ?></td>
            <td>
              <span class="badge <?= ((int)$r['active'] === 1) ? 'ok' : 'bad' ?>">
                <?= ((int)$r['active'] === 1) ? 'Sim' : 'Não' ?>
              </span>
            </td>
            <td>
              <form method="post" style="margin:0;">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <?php if ($courseId > 0): ?>
                  <input type="hidden" name="course_id" value="<?= (int)$courseId ?>">
                <?php endif; ?>
                <button class="btn btn-ghost" type="submit">
                  <?= ((int)$r['active'] === 1) ? 'Desativar' : 'Ativar' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>