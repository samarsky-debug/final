<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

require_once '../config.php';

function getCartDetails($pdo) {
    $cartItems = [];
    $total = 0;
    if (empty($_SESSION['cart'])) return ['items' => [], 'total' => 0];
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productsById = [];
    foreach ($products as $p) $productsById[$p['id']] = $p;
    foreach ($_SESSION['cart'] as $id => $sizes) {
        if (!isset($productsById[$id])) { unset($_SESSION['cart'][$id]); continue; }
        $product = $productsById[$id];
        foreach ($sizes as $size => $quantity) {
            $total += $product['price'] * $quantity;
            $cartItems[] = [
                'id'       => $id,
                'name'     => $product['title'],
                'price'    => (float)$product['price'],
                'quantity' => $quantity,
                'size'     => $size
            ];
        }
    }
    return ['items' => $cartItems, 'total' => $total];
}

$cartData = getCartDetails($pdo);
$cartItems = $cartData['items'];
$total = $cartData['total'];

$appliedPromo = $_SESSION['applied_promo'] ?? null;
$discountAmount = $appliedPromo['amount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оформление заказа | Fashion Future</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="checkout-container">
    <h1>Оформление заказа</h1>

    <div class="cart-summary">
        <h3>Ваш заказ</h3>
        <?php if (empty($cartItems)): ?>
            <p>Корзина пуста. <a href="../index.php">Вернуться к покупкам</a></p>
        <?php else: ?>
            <div id="cart-items-list">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <span><?= htmlspecialchars($item['name']) ?> (Размер: <?= htmlspecialchars($item['size']) ?>) x <?= $item['quantity'] ?></span>
                        <span class="item-subtotal"><?= number_format($item['price'] * $item['quantity'], 0, '.', ' ') ?> ₽</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="promo-section">
                <div class="promo-input-group">
                    <input type="text" id="promoCode" placeholder="Введите промокод" style="text-transform:uppercase">
                    <button type="button" id="applyPromoBtn">Применить</button>
                </div>
                <div id="promoMessage" class="promo-message"></div>
            </div>

            <div class="cart-total">
                <div class="total-row"><span>Сумма:</span><span id="subtotalAmount"><?= number_format($total, 0, '.', ' ') ?> ₽</span></div>
                <div class="total-row discount-row" id="discountRow" style="<?= $discountAmount ? '' : 'display:none' ?>">
                    <span>Скидка (<span id="discountPercent"><?= $appliedPromo['percent'] ?? 0 ?></span>%):</span>
                    <span id="discountAmountSpan">- <?= number_format($discountAmount, 0, '.', ' ') ?> ₽</span>
                </div>
                <div class="total-row delivery-info">
                    <span>Доставка:</span>
                    <span id="deliveryAmount">0 ₽</span>
                </div>
                <?php if ($total >= 6000): ?>
                <?php endif; ?>
                <div class="total-row final-total"><span>Итого к оплате:</span><span id="finalTotal"><?= number_format($total - $discountAmount, 0, '.', ' ') ?> ₽</span></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($cartItems)): ?>
        <form id="orderForm">
            <input type="hidden" name="promo_code" id="hiddenPromoCode" value="<?= htmlspecialchars($appliedPromo['code'] ?? '') ?>">
            <input type="hidden" name="discount_amount" id="hiddenDiscountAmount" value="<?= $discountAmount ?>">
            <input type="hidden" name="delivery_price" id="deliveryPrice" value="0">
            <input type="hidden" name="city_id" id="cityId" required>

            <div class="form-group">
                <label>Город доставки *</label>
                <input type="text" id="cityInput" placeholder="Начните вводить город..." autocomplete="off" required>
                <div id="citySuggestions" class="suggestions-box"></div>
            </div>

            <div class="form-group">
                <label>Адрес (улица, дом, квартира) *</label>
                <input type="text" name="address" id="address" required>
            </div>

            <div class="form-group">
                <label>Телефон *</label>
                <input type="tel" name="phone" id="phone" required placeholder="+7 999 123 45 67" maxlength="16" inputmode="numeric">
                <small class="phone-hint">Формат: +7 999 123 45 67</small>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Комментарий к заказу</label>
                <textarea name="comment" id="comment" rows="3"></textarea>
            </div>

            <button type="submit" id="submitOrderBtn">Подтвердить заказ</button>
        </form>
    <?php endif; ?>
    <div class="back-link"><a href="../index.php">← Вернуться в магазин</a></div>
</div>

<script>
const FREE_DELIVERY_THRESHOLD = 6000;
const subtotal = <?= $total ?>;
let currentDiscount = <?= $discountAmount ?>;
let currentPromoCode = '<?= htmlspecialchars($appliedPromo['code'] ?? '') ?>';
let currentPercent = <?= $appliedPromo['percent'] ?? 0 ?>;
let selectedCityRawPrice = 0; // исходная стоимость доставки из города (без учёта порога)

function getFinalDeliveryPrice() {
    // Если сумма корзины >= порога, доставка бесплатно
    return (subtotal >= FREE_DELIVERY_THRESHOLD) ? 0 : selectedCityRawPrice;
}

function updateDelivery() {
    const finalDelivery = getFinalDeliveryPrice();
    const deliveryElement = document.getElementById('deliveryAmount');
    if (finalDelivery === 0) {
        deliveryElement.innerHTML = 'Бесплатно';
    } else {
        deliveryElement.innerHTML = finalDelivery.toLocaleString() + ' ₽';
    }
    document.getElementById('deliveryPrice').value = finalDelivery;
    const finalTotal = subtotal - currentDiscount + finalDelivery;
    document.getElementById('finalTotal').innerText = finalTotal.toLocaleString() + ' ₽';
}

function updateTotals(discountAmount, percent, promoCode) {
    currentDiscount = discountAmount;
    currentPercent = percent;
    currentPromoCode = promoCode;
    document.getElementById('discountAmountSpan').innerText = '- ' + discountAmount.toLocaleString() + ' ₽';
    document.getElementById('discountPercent').innerText = percent;
    document.getElementById('discountRow').style.display = discountAmount > 0 ? 'flex' : 'none';
    document.getElementById('hiddenPromoCode').value = promoCode || '';
    document.getElementById('hiddenDiscountAmount').value = discountAmount;
    updateDelivery(); // пересчитаем итог с учётом доставки и нового discount
}

// Поиск города
const cityInput = document.getElementById('cityInput');
const cityIdHidden = document.getElementById('cityId');
const suggestionsBox = document.getElementById('citySuggestions');

cityInput.addEventListener('input', function() {
    const query = this.value.trim();
    if (query.length < 2) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
        return;
    }
    fetch(`../ajax_search_cities.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                suggestionsBox.innerHTML = '<div class="suggestion-item no-results">Ничего не найдено</div>';
                suggestionsBox.style.display = 'block';
                return;
            }
            let html = '';
            data.forEach(city => {
                html += `<div class="suggestion-item" data-id="${city.id}" data-price="${city.delivery_price}">
                            ${city.name} ${city.region ? `(${city.region})` : ''} — доставка ${Number(city.delivery_price).toLocaleString()} ₽
                         </div>`;
            });
            suggestionsBox.innerHTML = html;
            suggestionsBox.style.display = 'block';
        });
});

suggestionsBox.addEventListener('click', (e) => {
    const item = e.target.closest('.suggestion-item');
    if (item && item.dataset.id) {
        cityInput.value = item.textContent.split(' —')[0];
        cityIdHidden.value = item.dataset.id;
        selectedCityRawPrice = parseFloat(item.dataset.price);
        updateDelivery(); // обновляем цену доставки с учётом порога
        suggestionsBox.style.display = 'none';
    }
});

document.addEventListener('click', (e) => {
    if (!cityInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.style.display = 'none';
    }
});

// Применение промокода
document.getElementById('applyPromoBtn')?.addEventListener('click', async function() {
    const code = document.getElementById('promoCode').value.trim();
    const msgDiv = document.getElementById('promoMessage');
    if (!code) { msgDiv.innerText = 'Введите промокод'; msgDiv.className = 'promo-message error'; return; }
    try {
        const response = await fetch('../cart_orders/apply_promo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ promo_code: code, subtotal: subtotal })
        });
        const data = await response.json();
        if (data.success) {
            updateTotals(data.discount_amount, data.percent, code);
            msgDiv.innerText = data.message;
            msgDiv.className = 'promo-message success';
        } else {
            updateTotals(0, 0, '');
            msgDiv.innerText = data.message;
            msgDiv.className = 'promo-message error';
        }
    } catch(err) { msgDiv.innerText = 'Ошибка соединения'; msgDiv.className = 'promo-message error'; }
});
</script>
<script src="<?= BASE_URL ?>js/phone_number.js"></script>
</body>
</html>