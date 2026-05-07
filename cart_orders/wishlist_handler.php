<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Авторизуйтесь', 'redirect' => 'user/login.php']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

if (!$product_id && $action !== 'get_all') {
    echo json_encode(['error' => 'Не указан товар']);
    exit;
}

// Для всех действий, кроме get_all, проверяем существование товара
if ($action !== 'get_all') {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Товар не найден']);
        exit;
    }
}

if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$userId, $product_id]);
    echo json_encode(['success' => true]);
} 
elseif ($action === 'remove') {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $product_id]);
    echo json_encode(['success' => true]);
}
elseif ($action === 'check') {
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $product_id]);
    echo json_encode(['in_wishlist' => (bool)$stmt->fetch()]);
}
elseif ($action === 'get_all') {
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'items' => $stmt->fetchAll()]);
}
else {
    echo json_encode(['error' => 'Неизвестное действие']);
}