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
    gs.*,
    ucs.code AS uc_code,
    ucs.name AS uc_name
  FROM grade_sheets gs
  JOIN ucs ON ucs.id = gs.uc_id
  WHERE gs.id=?
  LIMIT 1
");
$stmt->execute([$id]);
$gs = $stmt->fetch();
if (!$gs) { http_response_code(404); echo "Pauta não encontrada."; exit; }

$err = null;
$ok = null;

// elegíveis para gerar
$st = $pdo->prepare("
  SELECT COUNT(*) AS c
  FROM student_uc_enrollments sue
  JOIN users u ON u.id = sue.user_id
  WHERE sue.uc_id=? AND sue.academic_year=? AND sue.status='enrolled' AND u.active=1
");
$st->execute([(int)$gs['uc_id'], (string)$gs['academic_year']]);
$eligibleCount = (int)$st->fetch()['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'generate') {
    $pdo->beginTransaction();
    try {
      $st = $pdo->prepare("
        INSERT IGNORE INTO grade_sheet_rows (grade_sheet_id, student_user_id, final_grade)
        SELECT ?, sue.user_id, NULL
        FROM student_uc_enrollments sue
        JOIN users u ON u.id = sue.user_id
        WHERE sue.uc_id=? AND sue.academic_year=? AND sue.status='enrolled' AND u.active=1
      ");
      $st->execute([(int)$gs['id'], (int)$gs['uc_id'], (string)$gs['academic_year']]);
      $pdo->commit();
      $ok = "Alunos elegíveis adicionados à pauta.";
    } catch (Throwable $e) {
      $pdo->rollBack();
      $err = "Erro ao gerar lista: " . $e->getMessage();
    }
  }

  elseif ($action === 'add_student') {
    $email = trim(mb_strtolower((string)($_POST['student_email'] ?? '')));
    if ($email === '') {
      $err = "Indica o email do aluno.";
    } else {
      $st = $pdo->prepare("SELECT id FROM users WHERE email=? AND active=1 LIMIT 1");
      $st->execute([$email]);
      $user = $st->fetch();
      if (!$user) {
        $err = "Utilizador não encontrado (ou inativo).";
      } else {
        $st = $pdo->prepare("INSERT IGNORE INTO grade_sheet_rows (grade_sheet_id, student_user_id, final_grade) VALUES (?, ?, NULL)");
        $st->execute([(int)$gs['id'], (int)$user['id']]);
        $ok = "Aluno adicionado (se não existia).";
      }
    }
  }

  elseif ($action === 'save_grades') {
    $grades = $_POST['grade'] ?? [];
    if (!is_array($grades)) $grades = [];

    $pdo->beginTransaction();
    try {
      $st = $pdo->prepare("
        UPDATE grade_sheet_rows
        SET final_grade=?, updated_at=NOW()
        WHERE id=? AND grade_sheet_id=?
      ");

      foreach ($grades as $rowId => $val) {
        $rowId = (int)$rowId;
        $valStr = trim((string)$val);

        $grade = null;
        if ($valStr !== '') {
          $valStr = str_replace(',', '.', $valStr);
          if (!is_numeric($valStr)) throw new RuntimeException("Nota inválida na linha {$rowId}.");
          $g = (float)$valStr;
          if ($g < 0 || $g > 20) throw new RuntimeException("Nota fora de 0-20 na linha {$rowId}.");
          $grade = $g;
        }

        $st->execute([$grade, $rowId, (int)$gs['id']]);
      }

      $pdo->commit();
      $ok = "Notas guardadas.";
    } catch (Throwable $e) {
      $pdo->rollBack();
      $err = "Erro ao guardar: " . $e->getMessage();
    }
  }

  else {
    $err = "Ação inválida.";
  }
}

// carregar linhas da pauta
$st = $pdo->prepare("
  SELECT
    r.id,
    r.final_grade,
    r.updated_at,
    u.name AS student_name,
    u.email AS student_email
  FROM grade_sheet_rows r
  JOIN users u ON u.id = r.student_user_id
  WHERE r.grade_sheet_id=?
  ORDER BY u.name ASC
");
$st->execute([(int)$gs['id']]);
$rows = $st->fetchAll();

$title = "Pauta";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Pauta #<?= (int)$gs['id'] ?></h1>
      <div class="subtle">
        <?= htmlspecialchars($gs['uc_code'] . ' — ' . $gs['uc_name']) ?>
        · <?= htmlspecialchars((string)$gs['academic_year']) ?>
        · <?= htmlspecialchars((string)$gs['season']) ?>
      </div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pautas.php">Voltar</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pedidos.php">Pedidos</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="kpi" style="margin-top:14px;">
    <div class="box">
      <div class="subtle">Alunos na pauta</div>
      <div class="big"><?= count($rows) ?></div>
    </div>
    <div class="box">
      <div class="subtle">Elegíveis encontrados</div>
      <div class="big"><?= $eligibleCount ?></div>
      <div class="subtle" style="margin-top:8px;">(inscritos na UC neste ano letivo)</div>
    </div>
    <div class="box">
      <div class="subtle">Ações rápidas</div>
      <div class="row" style="margin-top:10px;">
        <form method="post" style="margin:0;">
          <input type="hidden" name="action" value="generate">
          <button class="btn" type="submit">Gerar lista</button>
        </form>
      </div>
    </div>
  </div>

  <hr>

  <div class="grid">
    <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
      <h2 style="margin:0 0 10px;">Adicionar aluno manualmente</h2>
      <form method="post">
        <input type="hidden" name="action" value="add_student">
        <label>Email do aluno</label>
        <input name="student_email" placeholder="ex: aluno@escola.pt" required>
        <div class="row" style="margin-top:12px;">
          <button class="btn btn-ghost" type="submit">Adicionar</button>
        </div>
      </form>
      <div class="subtle" style="margin-top:10px; font-size:12px;">
        Útil caso ainda não existam inscrições em UCs para gerar automaticamente.
      </div>
    </div>

    <div class="card" style="box-shadow:none; margin:0;">
      <h2 style="margin:0 0 10px;">Lançamento</h2>
      <div class="subtle">
        Notas finais de 0 a 20. Deixa em branco para “sem nota”.
      </div>
    </div>
  </div>

  <hr>

  <h2 style="margin:0 0 10px;">Notas</h2>

  <?php if (!$rows): ?>
    <div class="alert">
      Ainda não existem alunos na pauta.
      Usa <strong>Gerar lista</strong> ou adiciona manualmente.
    </div>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="action" value="save_grades">

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Aluno</th>
              <th>Email</th>
              <th>Nota</th>
              <th>Atualizado</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars((string)$r['student_name']) ?></td>
              <td><?= htmlspecialchars((string)$r['student_email']) ?></td>
              <td style="min-width:160px;">
                <input
                  name="grade[<?= (int)$r['id'] ?>]"
                  value="<?= $r['final_grade'] === null ? '' : htmlspecialchars((string)$r['final_grade']) ?>"
                  placeholder="ex: 14.5"
                  inputmode="decimal"
                >
              </td>
              <td><?= htmlspecialchars((string)$r['updated_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="row" style="margin-top:12px;">
        <button class="btn" type="submit">Guardar notas</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>