<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/path.php';

$u = current_user();
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'IAedu') ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/styles.css">
</head>
<body>
<header class="topbar">
  <div class="container">
    <div class="brand">IAedu Académicos</div>

    <nav class="nav">
      <a href="<?= BASE_URL ?>/public/index.php">Início</a>
<?php if ($u && $u['role'] === 'aluno'): ?>
  <a href="<?= BASE_URL ?>/aluno/area.php">Área do Aluno</a>
<?php endif; ?>

<?php if ($u && $u['role'] === 'funcionario'): ?>
  <a href="<?= BASE_URL ?>/funcionario/area.php">Área do Funcionário</a>
<?php endif; ?>

<?php if ($u && $u['role'] === 'gestor'): ?>
  <a href="<?= BASE_URL ?>/gestor/area.php">Área do Gestor</a>
<?php endif; ?>

      <?php if ($u): ?>
        <span class="subtle">|</span>
        <span class="subtle" style="font-weight:800;">
          <?= htmlspecialchars($u['name']) ?>
        </span>
        <form class="inline" action="<?= BASE_URL ?>/public/logout.php" method="post">
          <button type="submit" class="btn btn-ghost">Log Out</button>
        </form>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/public/login.php">Log In</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">