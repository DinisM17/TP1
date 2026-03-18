<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('gestor');
$pdo = db();

$rows = $pdo->query("
  SELECT
    sp.id,
    sp.user_id,
    sp.full_name,
    sp.status,
    sp.submitted_at,
    usr.email,
    usr.name AS user_name,
    c.name AS desired_course_name
  FROM student_profiles sp
  JOIN users usr ON usr.id = sp.user_id
  LEFT JOIN courses c ON c.id = sp.desired_course_id
  WHERE sp.status='submitted'
  ORDER BY sp.submitted_at ASC
")->fetchAll();

$title = "Fichas submetidas";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Fichas submetidas</h1>
      <div class="subtle">Aprovar ou rejeitar fichas submetidas pelos alunos.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Dashboard</a>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="alert success">Não existem fichas submetidas.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Aluno</th>
            <th>Email</th>
            <th>Curso pretendido</th>
            <th>Submetida em</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars((string)$r['full_name']) ?></td>
            <td><?= htmlspecialchars((string)$r['email']) ?></td>
            <td><?= htmlspecialchars((string)($r['desired_course_name'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)$r['submitted_at']) ?></td>
            <td>
              <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/ficha_decidir.php?id=<?= (int)$r['id'] ?>">
                Decidir
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>