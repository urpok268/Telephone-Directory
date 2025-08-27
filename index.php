<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/auth.php';

// Имя текущего пользователя (если вошёл) и отметка времени
$adminName = is_logged_in() ? current_username() : null;
$lastSync  = date('Y-m-d H:i');

// CSRF — передадим в JS для кнопок удаления
$csrf = is_logged_in() ? csrf_token() : '';

// Подгружаем контакты из БД и сразу готовим структуру как в people.js.php
$people = [];
try {
    $stmt = $pdo->query("SELECT id, full_name, position, org, phone, email, fax, address, note FROM contacts ORDER BY full_name ASC");
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        // phones: если есть запятые — разобьём, иначе один элемент
        $phones = [];
        $p = trim((string)($r['phone'] ?? ''));
        if ($p !== '') {
            $phones = preg_split('/\s*[,;]\s*/u', $p);
        }
        $people[] = [
            'id'      => (string)$r['id'],
            'org'     => (string)($r['org'] ?? ''), // при желании можно добавить поле org в БД
            'name'    => $r['full_name'],
            'pos'     => $r['position'] ?? '',
            'phones'  => $phones,
            'email'   => $r['email'] ?? '',
            'fax'     => (string)($r['fax'] ?? ''),
            'address' => (string)($r['address'] ?? ''),
            'note'    => $r['note'] ?? '',
        ];
    }
} catch (Throwable $e) {
    // Если что-то пойдёт не так — покажем диагностическое сообщение
    http_response_code(500);
    echo "<h1>Ошибка загрузки данных</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</pre>";
    exit;
}

// Организации нам не нужны — оставим пустой объект для совместимости
$orgData = new stdClass();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Контакты</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
  <a id="top"></a>
  <div class="screen" role="application" aria-label="Экран контактов">
    <header class="header" role="banner">
	 <a href="index.php" class="noDecorationA">
      <div class="header-left">
        <div class="logo" aria-hidden="true">
          <div class="mark"><b>ЛОГО</b></div>
        </div>
        <div>
          <div class="brand">Справочник</div>
          <div class="org-title" id="globalOrg">Список организаций</div>
        </div>
      </div>
     </a>
      <div class="meta" aria-live="polite">
        <div id="time" style="font-weight:700;font-size:18px">12:34</div>

        <div class="auth" role="group" aria-label="Управление сессией">
          <?php if ($adminName): ?>
            <span class="user-name" title="Текущий пользователь">
              <?= htmlspecialchars($adminName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </span>
            <a class="btn-auth" href="logout.php">Выйти</a>
          <?php else: ?>
            <a class="btn-auth" href="login.php">Войти</a>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <main class="main" role="main">
      <div>
        <input id="search" class="search"
               placeholder="Поиск: наименование организации, ФИО, должность, телефон, e-mail"
               aria-label="Поиск контактов" />
        <div style="margin-top:8px;color:var(--muted);font-size:13px">
          Карточка содержит: Наименование организации, Адрес организации, ФИО, Должность,
          Телефон, E-mail, Факс и QR (по первому телефону).
        </div>
      </div>

      <section class="list" id="resultsList" aria-label="Список контактов"></section>
	  <?php if (!$adminName): ?>
	    <a class="fab-up fab-up-no-admin" href="#top" title="Наверх">↑</a>
	  <?php endif; ?>
	  <?php if ($adminName): ?>
	    <a class="fab-up" href="#top" title="Наверх">↑</a>
	    <a class="fab-add" href="add.php" title="Добавить запись" aria-label="Добавить запись">+</a>
	  <?php endif; ?>
    </main>

    <footer class="footer" role="contentinfo">
      <div><strong id="countShown">0</strong> контактов</div>
      <div style="font-size:12px;color:var(--muted)">
        <span id="lastSync"><?= htmlspecialchars($lastSync, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
      </div>
    </footer>
  </div>

  <!-- модальное окно для увеличенного QR -->
  <div id="qrModal" class="modal">
    <div class="modal-content">
      <span id="modalClose" class="modal-close">&times;</span>
      <div id="modalQR"></div>
    </div>
  </div>

  <!-- локальные скрипты -->
  <script src="scripts/qrcode.min.js"></script>

  <!-- Данные из БД: без отдельного запроса по JS -->
  <script>
    window.people  = <?= json_encode($people, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.orgData = <?= json_encode($orgData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // Передаём статус входа и CSRF в JS
    window.isLoggedIn = <?= $adminName ? 'true' : 'false' ?>;
    <?php if ($adminName): ?>
    window.csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    <?php endif; ?>
  </script>

  <!-- Рендер/поиск/QR/кнопки -->
  <script src="scripts/script.js"></script>
</body>
</html>
