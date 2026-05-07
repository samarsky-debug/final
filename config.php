<?php
$host = 'localhost';
$dbname = 'samarskiy';
$user = 'root';
$pass = '';

define('BASE_URL', '/fashion-main/');
define('FREE_SHIPPING_LIMIT', 5000);
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Режим продакшена (false = отладка, true = ошибки не выводятся)
define('PRODUCTION', false);  // на защите поставьте true

if (PRODUCTION) {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Запуск сессии (если ещё не запущена)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция безопасного вывода (сокращение htmlspecialchars)
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
function addNotification($userId, $title, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $title, $message]);
}
?>