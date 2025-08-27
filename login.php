<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/auth.php';
require __DIR__ . '/app/ldap_config.php';
require __DIR__ . '/app/ldap_auth.php';

$error = null;
$noAccessMsg = '';

// Разрешённые логины (точное совпадение, с учётом регистра)
$ALLOWED_USERS = ['user1', 'user2', 'user3'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            throw new RuntimeException('Введите логин и пароль');
        }

        // --- LDAP: логика перенесена из второго файла ---
        $ldapUser = ldap_try_auth($username, $password);
        if ($ldapUser) {
            // определяем финальное имя для проверки прав
            $finalUsername = $ldapUser['username'] ?? $username;

            if (!in_array($finalUsername, $ALLOWED_USERS, true) && $ALLOWED_USERS) {
                $noAccessMsg = 'У вас нет прав для входа на эту страницу';
                unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_doc']);
                // не продолжаем дальше — покажем форму с сообщением
            } else {
                // В сессии сохраняем тех. ID -1 и имя/логин из LDAP
                $_SESSION['user_id'] = -1;
                $_SESSION['username'] = $finalUsername;
                if (!empty($ldapUser['display_name'])) {
                    $_SESSION['user_doc'] = $ldapUser['display_name'];
                }
                header('Location: index.php');
                exit;
            }
        } else {
            // --- если LDAP не сработал, пробуем локальную БД ---
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new RuntimeException('Неверный логин или пароль');
            }
            $finalUsername = $user['username'] ?? $username;
            if (!in_array($finalUsername, $ALLOWED_USERS, true) && $ALLOWED_USERS) {
                $noAccessMsg = 'У вас нет прав для входа на эту страницу';
                unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_doc']);
                // остаёмся на странице логина, покажем сообщение
            } else {
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $finalUsername;
                if (!empty($user['user_doc'])) $_SESSION['user_doc'] = $user['user_doc'];
                header('Location: index.php'); exit;
            }
        }

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
  <title>Вход — Справочник</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
  <div class="screen" role="application" aria-label="Экран входа">
    <header class="header" role="banner">
	 <a href="index.php" class="go-home">
      <div class="header-left">
        <div class="logo" aria-hidden="true">
          <div class="mark"><b>ЛОГО</b></div>
        </div>
        <div>
          <div class="brand">Справочник</div>
          <div class="org-title">Вход</div>
        </div>
      </div>
	 </a>
      <div class="header-right">
        <a class="btn-auth" href="index.php">На главную</a>
      </div>
    </header>

    <main class="main" role="main">
      <div class="card" style="max-width:520px;margin:0 auto;">
        <div class="card-header">
          <div class="card-title">Вход</div>
          <div class="muted">Введите учетные данные&emsp;</div>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
          <?php endif; ?>

          <form method="post" action="login.php" class="form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
			<?php if (!empty($noAccessMsg)): ?>
			  <div style="color:#d32f2f;font-weight:bold;margin:0 0 8px 0;">
				<?= htmlspecialchars($noAccessMsg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
			  </div>
			<?php endif; ?>
            <div class="form-row">
              <label>Логин</label>
              <input name="username" type="text" required autofocus>
            </div>
            <div class="form-row">
              <label>Пароль</label>
              <input name="password" type="password" required>
            </div>
            <div class="card-actions" style="flex-direction:row;gap:12px;">
              <button type="submit" class="btn-action">Войти</button>
              <a class="btn-action" href="index.php">Отмена</a>
            </div>
          </form>
        </div>
      </div>
    </main>

    <footer class="footer">
      <div>&nbsp;</div>
      <div style="font-size:12px;color:var(--muted)">Доступ ограничен авторизованным пользователям</div>
    </footer>
  </div>

  <script src="scripts/qrcode.min.js"></script>
  <script src="scripts/script.js"></script>
</body>
</html>
