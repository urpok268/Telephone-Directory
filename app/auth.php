<?php
$config = require __DIR__ . '/config.php';
session_name($config['session_name']);
session_start();

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_username(): ?string {
    return $_SESSION['username'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) {
        http_response_code(400);
        echo "Неверный CSRF-токен.";
        exit;
    }
}
