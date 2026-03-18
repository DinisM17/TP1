<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('funcionario');
$pdo = db();

$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM enrollment_requests WHERE status='pending'")->fetchColumn();
$pautasCount  = (int)$pdo->query("SELECT COUNT(*) FROM grade_sheets")->fetchColumn();

$title = "Área do Funcionário";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Área do Funcionário</h1>
      <div class="subtle">Serviços Académicos — atalhos de trabalho.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Portal</a>
    </div>
  </div>

  <div class="kpi" style="margin-top:14px;">
    <div class="box">
      <div class="subtle">Pedidos pendentes</div>
      <div class="big"><?= $pendingCount ?></div>
      <div class="subtle" style="margin-top:8px;">Aprovar/rejeitar matrículas</div>
      <div class="row" style="margin-top:10px;">
        <a class="btn" href="<?= BASE_URL ?>/funcionario/pedidos.php">Ver pedidos</a>
      </div>
    </div>

    <div class="box">
      <div class="subtle">Pautas</div>
      <div class="big"><?= $pautasCount ?></div>
      <div class="subtle" style="margin-top:8px;">Criar pautas e lançar notas</div>
      <div class="row" style="margin-top:10px;">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pautas.php">Gerir pautas</a>
      </div>
    </div>

    <div class="box">
      <div class="subtle">Ações rápidas</div>
      <div class="big">Hoje</div>
      <div class="subtle" style="margin-top:8px;">
        • Rever pedidos pendentes<br>
        • Atualizar pautas em curso
      </div>
      <div class="row" style="margin-top:10px;">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/funcionario/pedidos.php">Abrir fila</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>