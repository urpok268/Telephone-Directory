<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo "Некорректный ID";
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: index.php');
    exit;
}

// GET — красивая страница подтверждения удаления
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    $title = "Ошибка";
    $message = "Некорректный идентификатор записи.";
} else {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $contact = $stmt->fetch();
    if (!$contact) {
        http_response_code(404);
        $title = "Не найдено";
        $message = "Запись не найдена или уже удалена.";
    } else {
        $title = "Удалить запись?";
        $message = htmlspecialchars($contact['full_name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$config = require __DIR__ . '/app/config.php';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> — Справочник</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="img/favicon2.ico" type="image/x-icon">
</head>
<body>
  <div class="screen" role="application" aria-label="Экран удаления">
    <header class="header" role="banner">
      <div class="header-left">
        <img src="img/logo.png" alt="Лого" class="h-12" width="20%" height="20%">
        <div>
          <div class="brand">Справочник</div>
          <div class="muted">Телефонный/контактный справочник</div>
        </div>
      </div>
      <div class="header-right">
        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
          <div class="user-box">
            <span class="user-name"><?= htmlspecialchars(current_username(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <a class="btn-auth" href="logout.php">Выйти</a>
          </div>
        <?php else: ?>
          <a class="btn-auth" href="login.php">Войти</a>
        <?php endif; ?>
      </div>
    </header>

    <main class="main" role="main">
      <div class="card" style="max-width:720px;margin:0 auto;">
        <div class="card-header">
          <div class="card-title"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
          <div class="muted">Действие необратимо</div>
        </div>
        <div class="card-body">
          <p><?= $message ?></p>
          <?php if (!empty($contact)): ?>
          <div class="card-actions" style="flex-direction:row;gap:12px;justify-content:flex-start;">
            <form method="post" class="inline-delete-form">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
              <input type="hidden" name="id" value="<?= (int)$id ?>">
              <button type="submit" class="btn-action danger">Удалить</button>
            </form>
            <a class="btn-action" href="index.php">Отмена</a>
          </div>
          <?php else: ?>
            <a class="btn-action" href="index.php">На главную</a>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <footer class="footer">
      <div>&nbsp;</div>
      <div style="font-size:12px;color:var(--muted)">Вернитесь назад, если сомневаетесь</div>
    </footer>
  </div>
</body>
</html>
