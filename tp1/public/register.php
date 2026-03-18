<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/path.php';

$title = "Criar conta";
require __DIR__ . '/../templates/header.php';
?>
<div class="card" style="max-width:620px; margin: 18px auto;">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Criar conta</h1>
      <div class="subtle">Registo de aluno para acesso ao portal.</div>
    </div>
  </div>

  <form method="post" action="<?= BASE_URL ?>/public/register_post.php" autocomplete="off">
    <label>Nome</label>
    <input name="name" required>

    <label>Email</label>
    <input type="email" name="email" required placeholder="ex: nome@escola.pt">

    <label>Password</label>
    <input type="password" name="password" minlength="6" required>

    <label>Confirmar password</label>
    <input type="password" name="password2" minlength="6" required>

    <div class="row" style="margin-top:14px;">
      <button class="btn" type="submit">Criar conta</button>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/login.php">Já tenho conta</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>