<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/path.php';
require_once __DIR__ . '/../app/db.php';

$u = current_user();
$pdo = db();

/**
 * Se estiver logado, manda logo para a "home" do perfil
 * (o public/index fica como landing pública).
 */
if ($u) {
  $role = (string)($u['role'] ?? '');
  if ($role === 'aluno') {
    header('Location: ' . BASE_URL . '/aluno/area.php');
    exit;
  }
if ($role === 'funcionario') { header('Location: ' . BASE_URL . '/funcionario/area.php'); exit; }
if ($role === 'gestor')      { header('Location: ' . BASE_URL . '/gestor/area.php'); exit; }
}

$title = "Portal Académico";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Portal Académico</h1>
      <div class="subtle">
        Acede ao portal para criar conta, preencher ficha e submeter pedidos.
      </div>
    </div>
  </div>

  <div class="kpi" style="margin-top:14px;">
    <div class="box">
      <div class="subtle">Novo aluno</div>
      <div class="big">Criar conta</div>
      <div class="subtle" style="margin-top:8px;">
        Regista-te para iniciar o processo.
      </div>
      <div class="row" style="margin-top:10px;">
        <a class="btn" href="<?= BASE_URL ?>/public/register.php">Criar conta</a>
      </div>
    </div>

    <div class="box">
      <div class="subtle">Já tens conta?</div>
      <div class="big">Entrar</div>
      <div class="subtle" style="margin-top:8px;">
        Usa as tuas credenciais para aceder.
      </div>
      <div class="row" style="margin-top:10px;">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/login.php">Entrar</a>
      </div>
    </div>

    <div class="box">
      <div class="subtle">Como funciona</div>
      <div class="big">Passos</div>
      <div class="subtle" style="margin-top:8px;">
        1) Criar conta<br>
        2) Preencher ficha + foto<br>
        3) Submeter para validação<br>
        4) Criar pedido de matrícula<br>
        5) (Após aprovação) Inscrever UCs
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>