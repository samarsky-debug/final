<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$promoCode = trim($input['promo_code'] ?? '');
$subtotal = (float)($input['subtotal'] ?? 0);

if (empty($promoCode)) {
    echo json_encode(['success' => false, 'message' => 'Введите промокод']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM promocodes WHERE UPPER(code) = UPPER(?)");
$stmt->execute([$promoCode]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promo) {
    echo json_encode(['success' => false, 'message' => 'Промокод не найден']);
    exit;
}
if (!$promo['is_active']) {
    echo json_encode(['success' => false, 'message' => 'Промокод отключён']);
    exit;
}
if ($promo['expires_at'] && strtotime($promo['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Срок действия промокода истёк']);
    exit;
}
if ($subtotal < $promo['min_order_amount']) {
    echo json_encode(['success' => false, 'message' => 'Промокод действует при заказе от ' . number_format($promo['min_order_amount'], 0, '.', ' ') . ' ₽']);
    exit;
}

// Дополнительная проверка: не превышен ли общий лимит использований
if ($promo['usage_limit'] !== null && $promo['used_count'] >= $promo['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'Лимит использований промокода исчерпан']);
    exit;
}

// Проверка per_user_limit (если пользователь авторизован)
if (!empty($_SESSION['user_id']) && $promo['per_user_limit'] > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM promo_usage WHERE promo_id = ? AND user_id = ?");
    $stmt->execute([$promo['id'], $_SESSION['user_id']]);
    $usedByUser = $stmt->fetchColumn();
    if ($usedByUser >= $promo['per_user_limit']) {
        echo json_encode(['success' => false, 'message' => 'Вы уже использовали этот промокод максимальное число раз']);
        exit;
    }
}

$discountAmount = 0;
if ($promo['discount_type'] === 'percent') {
    $discountAmount = $subtotal * ($promo['discount_value'] / 100);
    if (!empty($promo['max_discount']) && $discountAmount > $promo['max_discount']) {
        $discountAmount = $promo['max_discount'];
    }
} else {
    $discountAmount = $promo['discount_value'];
}
$discountAmount = min($discountAmount, $subtotal);
$discountAmount = round($discountAmount);

$_SESSION['applied_promo'] = [
    'id'      => $promo['id'],
    'code'    => $promo['code'],
    'percent' => ($promo['discount_type'] === 'percent') ? $promo['discount_value'] : 0,
    'amount'  => $discountAmount
];

echo json_encode([
    'success' => true,
    'discount_amount' => $discountAmount,
    'percent' => ($promo['discount_type'] === 'percent') ? $promo['discount_value'] : 0,
    'message' => 'Промокод применён! Скидка ' . number_format($discountAmount, 0, '.', ' ') . ' ₽'
]);