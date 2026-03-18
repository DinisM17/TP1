<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('funcionario');
$pdo = db();

$err = null;

// dados para filtros/criação
$ucs = $pdo->query("SELECT id, code, name FROM ucs WHERE active=1 ORDER BY name")->fetchAll();

// filtros GET
$fUc = (int)($_GET['uc_id'] ?? 0);
$fYear = trim((string)($_GET['academic_year'] ?? ''));
$fSeason = trim((string)($_GET['season'] ?? ''));

$where = [];
$args = [];

if ($fUc > 0) { $where[] = "gs.uc_id=?"; $args[] = $fUc; }
if ($fYear !== '') { $where[] = "gs.academic_year=?"; $args[] = $fYear; }
if ($fSeason !== '' && in_array($fSeason, ['normal','recurso','especial'], true)) { $where[] = "gs.season=?"; $args[] = $fSeason; }

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// criar pauta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ucId = (int)($_POST['uc_id'] ?? 0);
  $academicYear = trim((string)($_POST['academic_year'] ?? ''));
  $season = (string)($_POST['season'] ?? '');

  if ($ucId <= 0) $err = "Seleciona uma UC.";
  elseif ($academicYear === '') $err = "Indica o ano letivo (ex: 2025/2026).";
  elseif (!in_array($season, ['normal','recurso','especial'], true)) $err = "Época inválida.";
  else {
    try {
      $st = $pdo->prepare("
        INSERT INTO grade_sheets (uc_id, academic_year, season, created_by)
        VALUES (?, ?, ?, ?)
      ");
      $st->execute([$ucId, $academicYear, $season, $u['id']]);
      $newId = (int)$pdo->lastInsertId();

      header('Location: ' . BASE_URL . '/funcionario/pauta_abrir.php?id=' . $newId);
      exit;
    } catch (Throwable $e) {
      $err = "Não foi possível criar a pauta (pode já existir para UC/ano/época).";
    }
  }
}

// listar pautas (com contagem)
$stmt = $pdo->prepare("
  SELECT
    gs.id,
    gs.academic_year,
    gs.season,
    gs.created_at,
    ucs.code AS uc_code,
    ucs.name AS uc_name,
    usr.name AS created_by_name,
    (SELECT COUNT(*) FROM grade_sheet_rows r WHERE r.grade_sheet_id = gs.id) AS n_students
  FROM grade_sheets gs
  JOIN ucs ON ucs.id = gs.uc_id
  JOIN users usr ON usr.id = gs.created_by
  $whereSql
  ORDER BY gs.created_at DESC
");
$stmt->execute($args);
$rows = $stmt->fetchAll();

$title = "Pautas";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Pautas</h1>
      <div class="subtle">Criar pautas e gerir lançamentos de notas.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pedidos.php">Pedidos</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Portal</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
      <h2 style="margin:0 0 10px;">Criar pauta</h2>
      <form method="post" class="grid" style="grid-template-columns: 1fr 1fr;">
        <div style="grid-column: 1 / -1;">
          <label>UC</label>
          <select name="uc_id" required>
            <option value="">-- selecionar --</option>
            <?php foreach ($ucs as $c): ?>
              <option value="<?= (int)$c['id'] ?>">
                <?= htmlspecialchars($c['code'] . ' — ' . $c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label>Ano letivo</label>
          <input name="academic_year" placeholder="ex: 2025/2026"
                 value="<?= htmlspecialchars(date('Y') . '/' . (date('Y')+1)) ?>" required>
        </div>

        <div>
          <label>Época</label>
          <select name="season" required>
            <option value="normal">Normal</option>
            <option value="recurso">Recurso</option>
            <option value="especial">Especial</option>
          </select>
        </div>

        <div style="grid-column: 1 / -1;">
          <button class="btn" type="submit" style="width:100%;">Criar e abrir</button>
        </div>
      </form>
    </div>

    <div class="card" style="box-shadow:none; margin:0;">
      <h2 style="margin:0 0 10px;">Filtros</h2>
      <form method="get" class="grid" style="grid-template-columns: 1fr 1fr;">
        <div style="grid-column: 1 / -1;">
          <label>UC</label>
          <select name="uc_id">
            <option value="">-- todas --</option>
            <?php foreach ($ucs as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $fUc===(int)$c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['code'] . ' — ' . $c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label>Ano letivo</label>
          <input name="academic_year" value="<?= htmlspecialchars($fYear) ?>" placeholder="ex: 2025/2026">
        </div>

        <div>
          <label>Época</label>
          <select name="season">
            <option value="">-- todas --</option>
            <option value="normal"  <?= $fSeason==='normal' ? 'selected' : '' ?>>Normal</option>
            <option value="recurso" <?= $fSeason==='recurso' ? 'selected' : '' ?>>Recurso</option>
            <option value="especial"<?= $fSeason==='especial' ? 'selected' : '' ?>>Especial</option>
          </select>
        </div>

        <div class="row" style="grid-column: 1 / -1; margin-top:6px;">
          <button class="btn btn-ghost" type="submit">Aplicar</button>
          <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pautas.php">Limpar</a>
          <span class="badge warn">Resultados: <?= count($rows) ?></span>
        </div>
      </form>
    </div>
  </div>

  <hr>

  <h2 style="margin:0 0 10px;">Lista de pautas</h2>

  <?php if (!$rows): ?>
    <div class="alert">Sem pautas para os filtros atuais.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>UC</th>
            <th>Ano</th>
            <th>Época</th>
            <th>Alunos</th>
            <th>Criada por</th>
            <th>Criada em</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td>
              <div style="font-weight:900;"><?= htmlspecialchars($r['uc_code']) ?></div>
              <div class="subtle" style="font-size:12px;"><?= htmlspecialchars($r['uc_name']) ?></div>
            </td>
            <td><?= htmlspecialchars($r['academic_year']) ?></td>
            <td><?= htmlspecialchars($r['season']) ?></td>
            <td><?= (int)$r['n_students'] ?></td>
            <td><?= htmlspecialchars($r['created_by_name']) ?></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
            <td>
              <a class="btn" href="<?= BASE_URL ?>/funcionario/pauta_abrir.php?id=<?= (int)$r['id'] ?>">Abrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>