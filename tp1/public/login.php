<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/path.php';

// Se já estiver logado, manda para a home do perfil
$already = current_user();
if ($already) {
  $role = $already['role'] ?? '';
  if ($role === 'aluno') { header('Location: ' . BASE_URL . '/aluno/area.php'); exit; }
  if ($role === 'funcionario') { header('Location: ' . BASE_URL . '/funcionario/pedidos.php'); exit; }
  if ($role === 'gestor') { header('Location: ' . BASE_URL . '/gestor/fichas_submetidas.php'); exit; }
  header('Location: ' . BASE_URL . '/public/index.php'); exit;
}

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = (string)($_POST['email'] ?? '');
  $pass  = (string)($_POST['password'] ?? '');

  if (auth_login($email, $pass)) {
    $u = current_user();
    $role = $u['role'] ?? '';

    if ($role === 'aluno') { header('Location: ' . BASE_URL . '/aluno/area.php'); exit; }
if ($role === 'funcionario') { header('Location: ' . BASE_URL . '/funcionario/area.php'); exit; }
if ($role === 'gestor')      { header('Location: ' . BASE_URL . '/gestor/area.php'); exit; }
  }

  $err = "Credenciais inválidas.";
}

$title = "Entrar";
require __DIR__ . '/../templates/header.php';
?>
<div class="card" style="max-width:560px; margin: 18px auto;">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Entrar</h1>
      <div class="subtle">Autenticação no sistema.</div>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="alert"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <div class="row" style="margin-top:14px; justify-content:space-between;">
      <button class="btn" type="submit">Entrar</button>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/register.php">Criar conta</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>