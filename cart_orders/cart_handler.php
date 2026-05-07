<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config.php';
header('Content-Type: application/json');

// Инициализация корзины
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Получение корзины (GET)
if ($action === 'get') {
    $cartItems = [];
    $total = 0;
    foreach ($_SESSION['cart'] as $id => $sizes) {
        if (!is_array($sizes)) continue;
        $stmt = $pdo->prepare("SELECT id, title, price FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            unset($_SESSION['cart'][$id]);
            continue;
        }
        foreach ($sizes as $size => $quantity) {
            $stmtImg = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_main DESC LIMIT 1");
            $stmtImg->execute([$id]);
            $img = $stmtImg->fetch();
            $cartItems[] = [
                'id' => (int)$id,
                'size' => $size,
                'name' => $product['title'],
                'price' => (float)$product['price'],
                'quantity' => (int)$quantity,
                'image' => $img ? $img['image_url'] : '/img/placeholder.jpg'
            ];
            $total += $product['price'] * $quantity;
        }
    }
    echo json_encode(['items' => $cartItems, 'total' => $total]);
    exit;
}

// Добавление товара
if ($action === 'add') {
    $id = (int)($_POST['id'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($id <= 0 || empty($size)) {
        echo json_encode(['error' => 'Неверные данные']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Товар не найден']);
        exit;
    }
    if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = [];
    $_SESSION['cart'][$id][$size] = ($_SESSION['cart'][$id][$size] ?? 0) + $quantity;
    echo json_encode(['success' => true]);
    exit;
}

// Обновление количества
if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $delta = (int)($_POST['delta'] ?? 0);
    if (isset($_SESSION['cart'][$id][$size])) {
        $_SESSION['cart'][$id][$size] += $delta;
        if ($_SESSION['cart'][$id][$size] <= 0) {
            unset($_SESSION['cart'][$id][$size]);
            if (empty($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Товар не найден']);
    }
    exit;
}

// Удаление товара
if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    if (isset($_SESSION['cart'][$id][$size])) {
        unset($_SESSION['cart'][$id][$size]);
        if (empty($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Товар не найден']);
    }
    exit;
}

// Очистка корзины
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true]);
    exit;
}

// Неизвестное действие
echo json_encode(['error' => 'Неверное действие']);
exit;