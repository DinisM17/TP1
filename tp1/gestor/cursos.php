<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/guard.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/path.php';

$u = require_role('gestor');
$pdo = db();

$err = null;
$ok  = null;

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($action === 'create') {
    $code = trim((string)($_POST['code'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));

    if ($code === '' || $name === '') $err = "Código e nome são obrigatórios.";
    else {
      try {
        $st = $pdo->prepare("INSERT INTO courses (code, name, active) VALUES (?, ?, 1)");
        $st->execute([$code, $name]);
        $ok = "Curso criado.";
      } catch (Throwable $e) {
        $err = "Não foi possível criar (código duplicado?).";
      }
    }
  }

  elseif ($action === 'update' && $id > 0) {
    $code = trim((string)($_POST['code'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));

    if ($code === '' || $name === '') $err = "Código e nome são obrigatórios.";
    else {
      try {
        $st = $pdo->prepare("UPDATE courses SET code=?, name=? WHERE id=?");
        $st->execute([$code, $name, $id]);
        $ok = "Curso atualizado.";
      } catch (Throwable $e) {
        $err = "Não foi possível atualizar (código duplicado?).";
      }
    }
  }

  elseif ($action === 'toggle' && $id > 0) {
    $st = $pdo->prepare("UPDATE courses SET active = IF(active=1,0,1) WHERE id=?");
    $st->execute([$id]);
    $ok = "Estado alterado.";
  }

  header('Location: ' . BASE_URL . '/gestor/cursos.php');
  exit;
}

// editar (GET)
$edit = null;
if ($action === 'edit' && $id > 0) {
  $st = $pdo->prepare("SELECT * FROM courses WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $edit = $st->fetch();
}

$rows = $pdo->query("SELECT * FROM courses ORDER BY active DESC, name ASC")->fetchAll();

$title = "Cursos";
require __DIR__ . '/../templates/header.php';
?>
<div class="card">
  <div class="card-header">
    <div>
      <h1 class="page-title" style="margin:0;">Cursos</h1>
      <div class="subtle">Criar, editar e desativar cursos.</div>
    </div>
    <div class="row">
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/area.php">Área do Gestor</a>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/plano.php">Plano</a>
    </div>
  </div>

  <?php if ($err): ?><div class="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="alert success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card" style="box-shadow:none; margin:0; border-style:dashed;">
      <h2 style="margin:0 0 10px;"><?= $edit ? 'Editar curso' : 'Novo curso' ?></h2>

      <form method="post">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <label>Código</label>
        <input name="code" required value="<?= htmlspecialchars((string)($edit['code'] ?? '')) ?>" placeholder="ex: LEI">

        <label>Nome</label>
        <input name="name" required value="<?= htmlspecialchars((string)($edit['name'] ?? '')) ?>" placeholder="ex: Licenciatura em Engenharia Informática">

        <div class="row" style="margin-top:12px;">
          <button class="btn" type="submit"><?= $edit ? 'Guardar' : 'Criar' ?></button>
          <?php if ($edit): ?>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/cursos.php">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card" style="box-shadow:none; margin:0;">
      <h2 style="margin:0 0 10px;">Lista</h2>

      <?php if (!$rows): ?>
        <div class="alert">Sem cursos.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Estado</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td style="font-weight:900;"><?= htmlspecialchars((string)$r['code']) ?></td>
                <td><?= htmlspecialchars((string)$r['name']) ?></td>
                <td>
                  <span class="badge <?= ((int)$r['active']===1) ? 'ok' : 'bad' ?>">
                    <?= ((int)$r['active']===1) ? 'ativo' : 'inativo' ?>
                  </span>
                </td>
                <td class="row" style="gap:8px;">
                  <a class="btn btn-ghost" href="<?= BASE_URL ?>/gestor/cursos.php?action=edit&id=<?= (int)$r['id'] ?>">Editar</a>
                  <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-ghost" type="submit">
                      <?= ((int)$r['active']===1) ? 'Desativar' : 'Ativar' ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>