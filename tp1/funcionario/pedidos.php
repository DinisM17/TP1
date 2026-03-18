<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('funcionario');
$pdo = db();

$rows = $pdo->query("
  SELECT
    er.id,
    er.created_at,
    s.name  AS student_name,
    s.email AS student_email,
    c1.name AS course1_name,
    c2.name AS course2_name,
    c3.name AS course3_name
  FROM enrollment_requests er
  JOIN users s        ON s.id = er.user_id
  JOIN courses c1     ON c1.id = er.course_id
  LEFT JOIN courses c2 ON c2.id = er.course2_id
  LEFT JOIN courses c3 ON c3.id = er.course3_id
  WHERE er.status='pending'
  ORDER BY er.created_at ASC
")->fetchAll();

$title = "Pedidos";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Pedidos de matrícula</h1>
      <div class="subtle">Lista de pedidos pendentes para decisão.</div>
    </div>
    <div class="row">
      <span class="badge warn">Pendentes: <?= count($rows) ?></span>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pautas.php">Pautas</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Portal</a>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="alert success">Sem pedidos pendentes.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Aluno</th>
            <th>Opções</th>
            <th>Criado em</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td>
              <div style="font-weight:900;"><?= htmlspecialchars((string)$r['student_name']) ?></div>
              <div class="subtle" style="font-size:12px;"><?= htmlspecialchars((string)$r['student_email']) ?></div>
            </td>
            <td>
              <div>1ª: <?= htmlspecialchars((string)$r['course1_name']) ?></div>
              <div class="subtle" style="font-size:12px;">
                2ª: <?= htmlspecialchars((string)($r['course2_name'] ?? '—')) ?> ·
                3ª: <?= htmlspecialchars((string)($r['course3_name'] ?? '—')) ?>
              </div>
            </td>
            <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
            <td>
              <a class="btn" href="<?= BASE_URL ?>/funcionario/pedido_decidir.php?id=<?= (int)$r['id'] ?>">Decidir</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>