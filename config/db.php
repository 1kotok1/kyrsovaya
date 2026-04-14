<?php
// ============================================================
//  Конфигурация базы данных
//  Замените на ваши данные от Beget
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'k903939f_test');   // ваша БД на Beget
define('DB_USER', 'k903939f_test');   // пользователь
define('DB_PASS', 'phpMyAdmin2');     // пароль
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// Вспомогательные функции
function isLoggedIn(): bool  { return !empty($_SESSION['user_id']); }
function isAdmin(): bool     { return ($_SESSION['role'] ?? '') === 'admin'; }
function currentUserId(): int { return (int)($_SESSION['user_id'] ?? 0); }

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}
