<?php
// Скопируйте этот файл как config.php и укажите реквизиты своей базы PostgreSQL
return [
    // Пример: pgsql:host=127.0.0.1;port=5432;dbname=db
    'db_dsn' => '',
    'db_user' => 'teldir_user',
    'db_pass' => 'teldir_password',

    // Имя cookie сессии (можно оставить по умолчанию)
    'session_name' => 'TELDIRSID',

    // Базовый путь (если деплоите не в корень домена)
    'base_path' => '',
];
