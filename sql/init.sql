-- init.sql
-- Скрипт первичной настройки базы данных для проекта "Справочник"
-- Создаёт таблицы и добавляет пользователя admin.

-- 1) Попытка создать расширение pgcrypto (необходимо для crypt/gen_salt)
-- Требует прав суперпользователя. Если их нет, закомментируйте следующую строку.
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 2) Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    user_doc TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3) Таблица контактов (по образцу из ваших сообщений)
CREATE TABLE IF NOT EXISTS contacts (
    id SERIAL PRIMARY KEY,
    full_name TEXT NOT NULL,
    position TEXT,
    org TEXT,
    phone TEXT,
    email TEXT,
    fax TEXT,
    address TEXT,
    note TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 4) Вставка пользователя admin
-- Вариант A: с использованием pgcrypto (bcrypt через crypt + gen_salt).
-- Выполнится правильно, если CREATE EXTENSION pgcrypto выше отработал.
INSERT INTO users (username, password_hash, user_doc)
VALUES ('admin', crypt('admin', gen_salt('bf')), 'Администратор (создан init.sql)')
ON CONFLICT (username) DO NOTHING;

-- Вариант B: если у вас нет прав на создание расширения pgcrypto,
-- используйте заранее сгенерированный bcrypt-хеш (раскомментируйте одну из строк ниже
-- и закомментируйте/удалите вариант A выше).
-- Сгенерированный хеш для пароля 'admin' (пример): $2b$12$AtFVdt/Ut/NIhIyE5y4JTORiObZd6F4EbX3rZhXEKkbHUrEvNNdBK
-- INSERT INTO users (username, password_hash, user_doc)
-- VALUES ('admin', '$2b$12$AtFVdt/Ut/NIhIyE5y4JTORiObZd6F4EbX3rZhXEKkbHUrEvNNdBK', 'Администратор (hash вставлен вручную)')
-- ON CONFLICT (username) DO NOTHING;

-- Дополнительно: пример вставки тестовых записей в contacts / phonebook (опционально)
INSERT INTO contacts (full_name, position, org, phone, email, address, note)
VALUES
('Иванов И.И.', 'Инженер', 'АО Тест', '+7 (000) 000-00-00', 'ivanov@example.local', 'ул. Пр. 1', 'Тестовый контакт')
ON CONFLICT DO NOTHING;
