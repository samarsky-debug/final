<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Неверный формат запроса']);
    exit;
}

$cart = $input['cart'] ?? [];
$address = trim($input['address'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$comment = trim($input['comment'] ?? '');
$cityId = (int)($input['city_id'] ?? 0);
$deliveryPrice = (float)($input['delivery_price'] ?? 0);
$promoCode = trim($input['promo_code'] ?? '');
$discountAmount = (float)($input['discount_amount'] ?? 0);

if (empty($cart) || empty($address) || empty($phone) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Заполните все обязательные поля']);
    exit;
}

$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}
$discountAmount = min($discountAmount, $total);
$finalTotal = $total - $discountAmount + $deliveryPrice;
$orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(100, 999);
$userId = $_SESSION['user_id'] ?? null;

$cityName = '';
if ($cityId) {
    $stmt = $pdo->prepare("SELECT name FROM cities WHERE id = ? AND is_active = 1");
    $stmt->execute([$cityId]);
    $cityName = $stmt->fetchColumn();
}

$appliedPromoId = null;
if (!empty($promoCode)) {
    $stmt = $pdo->prepare("SELECT id, discount_type, discount_value, max_discount FROM promocodes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$promoCode]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($promo) {
        if ($promo['discount_type'] === 'percent') {
            $recalcDiscount = $total * ($promo['discount_value'] / 100);
            if (!empty($promo['max_discount']) && $recalcDiscount > $promo['max_discount']) {
                $recalcDiscount = $promo['max_discount'];
            }
        } else {
            $recalcDiscount = $promo['discount_value'];
        }
        $discountAmount = min($recalcDiscount, $total);
        $finalTotal = $total - $discountAmount + $deliveryPrice;
        $appliedPromoId = $promo['id'];
    } else {
        $discountAmount = 0;
        $finalTotal = $total + $deliveryPrice;
        $promoCode = null;
    }
}

try {
    $pdo->beginTransaction();

    // Проверка остатков (блокировка строк)
    foreach ($cart as $item) {
        $stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $stmtStock->execute([$item['id']]);
        $stock = $stmtStock->fetchColumn();
        if ($stock === false) {
            throw new Exception("Товар с ID {$item['id']} не найден");
        }
        if ($stock < $item['quantity']) {
            throw new Exception("Недостаточно товара ID {$item['id']}. На складе: {$stock}, заказано: {$item['quantity']}");
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (user_id, order_number, total_amount, discount_amount, promo_code, status, 
         shipping_address, contact_phone, contact_email, comment, city, delivery_price) 
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, $orderNumber, $finalTotal, $discountAmount, $promoCode,
        $address, $phone, $email, $comment, $cityName, $deliveryPrice
    ]);
    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $stmtItem->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['quantity'], $item['id']]);
    }

    if ($appliedPromoId) {
        $pdo->prepare("UPDATE promocodes SET used_count = used_count + 1 WHERE id = ?")->execute([$appliedPromoId]);
        if ($userId) {
            $pdo->prepare("INSERT INTO promo_usage (promo_id, user_id) VALUES (?, ?)")->execute([$appliedPromoId, $userId]);
        }
    }

    $_SESSION['cart'] = [];
    unset($_SESSION['applied_promo']);

    $pdo->commit();
    echo json_encode(['success' => true, 'order_number' => $orderNumber]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}