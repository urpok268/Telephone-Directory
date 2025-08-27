<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/auth.php';
require_login();

$error = null;
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $full_name = trim($_POST['full_name'] ?? '');
        $position  = trim($_POST['position'] ?? '');
        $org       = trim($_POST['org'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $fax       = trim($_POST['fax'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $note      = trim($_POST['note'] ?? '');

        if ($full_name === '') {
            throw new RuntimeException('Укажите ФИО');
        }

        $stmt = $pdo->prepare("INSERT INTO contacts (full_name, position, org, phone, email, fax, address, note)
VALUES (:full_name, :position, :org, :phone, :email, :fax, :address, :note)");
        $stmt->execute([
            ':full_name' => $full_name,
            ':position'  => $position,
            ':org'       => $org,
            ':phone'     => $phone,
            ':email'     => $email,
            ':fax'       => $fax,
            ':address'   => $address,
            ':note'      => $note,
        ]);
        $ok = true;
        // После успешного добавления возвращаемся на главную
        header('Location: index.php?added=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$config = require __DIR__ . '/app/config.php';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Добавление — Справочник</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
  <div class="screen" role="application" aria-label="Экран добавления">
    <header class="header" role="banner">
     <a href="index.html" class="noDecorationA">
      <div class="header-left">
        <div class="logo" aria-hidden="true">
          <div class="mark"><b>ЛОГО</b></div>
        </div>
        <div>
          <div class="brand">Справочник</div>
          <div class="org-title" id="globalOrg">Добавить</div>
        </div>
      </div>
     </a>
      <div class="header-right">
        <?php if (is_logged_in()): ?>
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
          <div class="card-title">Добавить запись</div>
          <div class="muted">После сохранения запись появится в списке</div>
        </div>
        <div class="card-body">
          <?php if ($ok): ?>
            <div class="alert alert-success">Запись добавлена</div>
          <?php elseif ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
          <?php endif; ?>

          <form method="post" action="add.php" class="form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <div class="grid two">
              <div class="form-row">
                <label>ФИО*</label>
                <input name="full_name" type="text" required>
              </div>
              <div class="form-row">
                <label>Должность</label>
                <input name="position" type="text">
              </div>
              <div class="form-row">
                <label>Организация</label>
                <input name="org" type="text" placeholder="ООО «Ромашка»">
              </div>
              <div class="form-row">
                <label>Телефон</label>
                <input name="phone" type="text" placeholder="+7 (___) ___-__-__">
              </div>
              <div class="form-row">
                <label>Email</label>
                <input name="email" type="email" placeholder="user@example.com">
              </div>
              <div class="form-row">
                <label>Факс</label>
                <input type="text" name="fax" placeholder="+7 495 123-45-67">
              </div>
              <div class="form-row">
                <label>Адрес</label>
                <input type="text" name="address" placeholder="г. Москва, ул. Пушкина, д. 1">
              </div>
              <div class="form-row" style="grid-column:1/-1">
                <label>Примечание</label>
                <textarea name="note" rows="3"></textarea>
              </div>
            </div>
            <div class="card-actions" style="flex-direction:row;gap:12px;">
              <button type="submit" class="btn-action">Сохранить</button>
              <a class="btn-action" href="index.php">Отмена</a>
            </div>
          </form>
        </div>
      </div>
    </main>

    <footer class="footer">
      <div>&nbsp;</div>
      <div style="font-size:12px;color:var(--muted)">Поля, помеченные *, обязательны</div>
    </footer>
  </div>

  <script src="scripts/qrcode.min.js"></script>
  <script src="scripts/script.js"></script>
</body>
</html>
