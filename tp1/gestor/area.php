<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('gestor');
$pdo = db();

$submittedProfiles = (int)$pdo->query("SELECT COUNT(*) FROM student_profiles WHERE status='submitted'")->fetchColumn();
$coursesCount = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE active=1")->fetchColumn();
$ucsCount = (int)$pdo->query("SELECT COUNT(*) FROM ucs WHERE active=1")->fetchColumn();

$title = "Área do Gestor";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Área do Gestor</h1>
      <div class="subtle">Gestão pedagógica — atalhos principais.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/index.php">Portal</a>
    </div>
  </div>

  <div class="kpi" style="margin-top:14px;">
    <div class="box">
      <div class="subtle">Fichas submetidas</div>
      <div class="big"><?= $submittedProfiles ?></div>
      <div class="subtle" style="margin-top:8px;">Aprovar / rejeitar fichas</div>
      <div class="row" style="margin-top:10px;">
        <a class="btn" href="<?= BASE_URL ?>/gestor/fichas_submetidas.php">Ver validações</a>
      </div>
    </div>

    <div class="box">
      <div class="subtle">Cursos ativos</div>
      <div class="big"><?= $coursesCount ?></div>
      <div class="subtle" style="margin-top:8px;">Configuração de cursos (RF2)</div>
      <div class="row" style="margin-top:10px;">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/cursos.php">Gerir cursos</a>
      </div>
    </div>

    <div class="box">
      <div class="subtle">UCs ativas</div>
      <div class="big"><?= $ucsCount ?></div>
      <div class="subtle" style="margin-top:8px;">Unidades Curriculares e planos (RF2)</div>
      <div class="row" style="margin-top:10px;">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/ucs.php">Gerir UCs</a>
      </div>
    </div>
  </div>

  <hr>

  <div class="card" style="box-shadow:none; margin:0; background: rgba(255,255,255,.03);">
    <h2 style="margin:0 0 8px;">Próximo passo</h2>
    <div class="subtle">
      Implementar RF2 completo: CRUD Cursos, CRUD UCs e Plano de Estudos (associar UCs ao curso com ano/semestre e impedir duplicações).
    </div>
    <div class="row" style="margin-top:10px;">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/plano.php">Plano de estudos</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>