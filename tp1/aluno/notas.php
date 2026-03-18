<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('aluno');
$pdo = db();

$academicYear = trim((string)($_GET['academic_year'] ?? ''));
$season = trim((string)($_GET['season'] ?? ''));

$where = ["r.student_user_id = ?"];
$args = [$u['id']];

if ($academicYear !== '') {
  $where[] = "gs.academic_year = ?";
  $args[] = $academicYear;
}
if ($season !== '' && in_array($season, ['normal','recurso','especial'], true)) {
  $where[] = "gs.season = ?";
  $args[] = $season;
}

$whereSql = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
  SELECT
    gs.academic_year,
    gs.season,
    gs.created_at,
    ucs.code AS uc_code,
    ucs.name AS uc_name,
    r.final_grade,
    r.updated_at
  FROM grade_sheet_rows r
  JOIN grade_sheets gs ON gs.id = r.grade_sheet_id
  JOIN ucs ON ucs.id = gs.uc_id
  $whereSql
  ORDER BY gs.academic_year DESC, gs.season ASC, ucs.name ASC
");
$stmt->execute($args);
$rows = $stmt->fetchAll();

// para dropdown de anos (só anos onde o aluno tem registos)
$years = $pdo->prepare("
  SELECT DISTINCT gs.academic_year
  FROM grade_sheet_rows r
  JOIN grade_sheets gs ON gs.id = r.grade_sheet_id
  WHERE r.student_user_id=?
  ORDER BY gs.academic_year DESC
");
$years->execute([$u['id']]);
$years = array_map(fn($x) => $x['academic_year'], $years->fetchAll());

$title = "Notas";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Notas</h1>
      <div class="subtle">Consulta as tuas notas finais por UC e época.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/area.php">Área do Aluno</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/estado.php">Estado</a>
    </div>
  </div>

  <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
    <h2 style="margin:0 0 10px;">Filtros</h2>
    <form method="get" class="grid" style="grid-template-columns: 1fr 1fr;">
      <div>
        <label>Ano letivo</label>
        <select name="academic_year">
          <option value="">-- todos --</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= htmlspecialchars($y) ?>" <?= $academicYear === $y ? 'selected' : '' ?>>
              <?= htmlspecialchars($y) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Época</label>
        <select name="season">
          <option value="">-- todas --</option>
          <option value="normal"  <?= $season==='normal' ? 'selected' : '' ?>>Normal</option>
          <option value="recurso" <?= $season==='recurso' ? 'selected' : '' ?>>Recurso</option>
          <option value="especial"<?= $season==='especial' ? 'selected' : '' ?>>Especial</option>
        </select>
      </div>

      <div class="row" style="grid-column: 1 / -1; margin-top:6px;">
        <button class="btn btn-ghost" type="submit">Aplicar</button>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/aluno/notas.php">Limpar</a>
        <span class="badge warn">Registos: <?= count($rows) ?></span>
      </div>
    </form>
  </div>

  <hr>

  <?php if (!$rows): ?>
    <div class="alert">Ainda não existem notas publicadas para os filtros selecionados.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Ano</th>
            <th>Época</th>
            <th>UC</th>
            <th>Nota final</th>
            <th>Atualizado</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars((string)$r['academic_year']) ?></td>
            <td><?= htmlspecialchars((string)$r['season']) ?></td>
            <td>
              <div style="font-weight:900;"><?= htmlspecialchars((string)$r['uc_code']) ?></div>
              <div class="subtle" style="font-size:12px;"><?= htmlspecialchars((string)$r['uc_name']) ?></div>
            </td>
            <td style="font-weight:950; font-size:16px;">
              <?= $r['final_grade'] === null ? '<span class="subtle">—</span>' : htmlspecialchars((string)$r['final_grade']) ?>
            </td>
            <td><?= htmlspecialchars((string)$r['updated_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>