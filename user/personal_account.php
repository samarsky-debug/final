<?php
session_start();
require_once '../config.php'; // подключаем PDO
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ========== ПРОВЕРКА НАЛИЧИЯ НЕОБХОДИМЫХ ПОЛЕЙ ==========
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''");
        $defaultHash = password_hash('123456', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE password = ''")->execute([$defaultHash]);
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'address'");
    if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL");
} catch (PDOException $e) {}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$loginError = '';
$passwordChangeMessage = '';
$passwordChangeError = '';
$orderActionMessage = '';
$profileUpdateMessage = '';
$profileUpdateError = '';

// ========== ОБРАБОТКА ВХОДА ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($username === '' || $pass === '') {
        $loginError = 'Заполните логин и пароль.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userRow && password_verify($pass, $userRow['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['username'] = $userRow['username'];
            header("Location: personal_account.php");
            exit;
        } else {
            $loginError = 'Неверное имя пользователя или пароль.';
        }
    }
}

// ========== ОПЛАТА ЗАКАЗА ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_order_id']) && isset($_SESSION['user_id'])) {
    $orderId = (int)$_POST['pay_order_id'];
    $userId = (int)$_SESSION['user_id'];
    $checkStmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $checkStmt->execute([$orderId, $userId]);
    if ($checkStmt->fetch()) {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $orderActionMessage = $updateStmt->execute([$orderId]) ? "✅ Заказ №{$orderId} успешно оплачен!" : "❌ Ошибка при оплате.";
    } else {
        $orderActionMessage = "❌ Заказ не найден или уже оплачен.";
    }
}

// ========== ОТМЕНА ЗАКАЗА ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id']) && isset($_SESSION['user_id'])) {
    $orderId = (int)$_POST['cancel_order_id'];
    $userId = (int)$_SESSION['user_id'];
    $checkStmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $checkStmt->execute([$orderId, $userId]);
    if ($checkStmt->fetch()) {
        $updateStmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $orderActionMessage = $updateStmt->execute([$orderId]) ? "❌ Заказ №{$orderId} отменён." : "❌ Ошибка при отмене.";
    } else {
        $orderActionMessage = "❌ Заказ не найден или не может быть отменён.";
    }
}

// ========== СМЕНА ПАРОЛЯ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && isset($_SESSION['user_id'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $userId = (int)$_SESSION['user_id'];
    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $passwordChangeError = 'Заполните все поля.';
    } elseif ($newPass !== $confirmPass) {
        $passwordChangeError = 'Новый пароль и подтверждение не совпадают.';
    } elseif (strlen($newPass) < 6) {
        $passwordChangeError = 'Новый пароль должен содержать минимум 6 символов.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && password_verify($currentPass, $row['password'])) {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updateStmt->execute([$newHash, $userId])) {
                $passwordChangeMessage = 'Пароль успешно изменён! Пожалуйста, войдите снова.';
                session_destroy();
                header("refresh:2;url=personal_account.php");
                exit;
            } else {
                $passwordChangeError = 'Ошибка при обновлении пароля.';
            }
        } else {
            $passwordChangeError = 'Неверный текущий пароль.';
        }
    }
}

// ========== ОБНОВЛЕНИЕ ПРОФИЛЯ ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && isset($_SESSION['user_id'])) {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $userId = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
    if ($stmt->execute([$phone, $address, $userId])) {
        $profileUpdateMessage = 'Контактные данные успешно обновлены.';
    } else {
        $profileUpdateError = 'Ошибка при сохранении данных.';
    }
}

// ========== ПОЛУЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ ==========
$currentUser = null;
$orders = [];
$wishlistItems = [];

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT username, email, phone, address, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$currentUser) {
        session_destroy();
        header("Location: personal_account.php");
        exit;
    }

    // История заказов с discount_amount
    $orderQuery = "
        SELECT o.id, o.order_number, o.total_amount, o.discount_amount, o.status, o.created_at,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ";
    $stmtOrders = $pdo->prepare($orderQuery);
    $stmtOrders->execute([$userId]);
    $ordersRaw = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ordersRaw as $order) {
        $itemsQuery = "
            SELECT oi.quantity, oi.price, p.title as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ";
        $stmtItems = $pdo->prepare($itemsQuery);
        $stmtItems->execute([$order['id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        $orders[] = $order;
    }

    // Избранное
    $wishlistQuery = "
        SELECT w.product_id, p.title, p.price, 
               (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_main DESC LIMIT 1) as image
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ";
    $stmtWish = $pdo->prepare($wishlistQuery);
    $stmtWish->execute([$userId]);
    $wishlistItems = $stmtWish->fetchAll(PDO::FETCH_ASSOC);

    // История авторизаций
    $stmt = $pdo->prepare("SELECT ip, user_agent, created_at FROM login_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $loginHistory = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | Fashion Future</title>
    <link rel="stylesheet" href="../css/account.css">
</head>
<body>
<div class="account-container">
    <a href="../index.php" class="back-link">← На главную</a>

    <?php if ($currentUser !== null): ?>
        <!-- Данные пользователя -->
        <div class="login-form">
            <div class="info-row"><div class="label">Имя пользователя</div><div class="value"><strong><?= htmlspecialchars($currentUser['username']) ?></strong></div></div>
            <div class="info-row"><div class="label">Email</div><div class="value"><?= htmlspecialchars($currentUser['email']) ?></div></div>
            <div class="info-row"><div class="label">Телефон</div><div class="value"><?= htmlspecialchars($currentUser['phone'] ?? '— не указан —') ?></div></div>
            <div class="info-row"><div class="label">Адрес доставки</div><div class="value"><?= nl2br(htmlspecialchars($currentUser['address'] ?? '— не указан —')) ?></div></div>
            <div class="info-row"><div class="label">Дата регистрации</div><div class="value"><?= $currentUser['created_at'] ? date('d.m.Y \в H:i', strtotime($currentUser['created_at'])) : '—' ?></div></div>
        </div>

        <div>
            <button id="toggleProfileBtn" class="toggle-profile-btn">✎ Редактировать профиль</button>
            <button id="togglePasswordBtn" class="toggle-password-btn">🔑 Сменить пароль</button>
            <a href="addresses.php" class="toggle-profile-btn">🏠 Мои адреса</a>
        </div>

        <!-- Форма редактирования профиля -->
        <div id="profileEditForm" class="login-form" style="display: none;">
            <h3>Редактирование контактных данных</h3>
            <?php if ($profileUpdateMessage): ?><div class="message-success">✅ <?= htmlspecialchars($profileUpdateMessage) ?></div><?php endif; ?>
            <?php if ($profileUpdateError): ?><div class="message-error">⚠️ <?= htmlspecialchars($profileUpdateError) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Телефон</label><input type="tel" name="phone" id="phone" placeholder="+7 999 123 45 67"></div>
                <div class="form-group"><label>Адрес доставки</label><textarea name="address" rows="3"><?= htmlspecialchars($currentUser['address'] ?? '') ?></textarea></div>
                <button type="submit" name="update_profile">Сохранить изменения</button>
            </form>
        </div>

        <!-- Форма смены пароля -->
        <div id="passwordChangeForm" class="login-form" style="display: none;">
            <h3>Смена пароля</h3>
            <?php if ($passwordChangeMessage): ?><div class="message-success">✅ <?= htmlspecialchars($passwordChangeMessage) ?></div><?php endif; ?>
            <?php if ($passwordChangeError): ?><div class="message-error">⚠️ <?= htmlspecialchars($passwordChangeError) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Текущий пароль</label><input type="password" name="current_password" required></div>
                <div class="form-group"><label>Новый пароль (мин. 6 символов)</label><input type="password" name="new_password" required></div>
                <div class="form-group"><label>Подтверждение</label><input type="password" name="confirm_password" required></div>
                <button type="submit" name="change_password">Изменить пароль</button>
            </form>
        </div>

        <div><a href="?logout=1" class="logout-link">Выйти из аккаунта</a></div>

        <!-- ИСТОРИЯ ЗАКАЗОВ -->
        <div class="orders-section">
            <h3>История заказов</h3>
            <?php if ($orderActionMessage): ?><div class="message-success"><?= htmlspecialchars($orderActionMessage) ?></div><?php endif; ?>
            <?php if (empty($orders)): ?>
                <p style="color:#666;">У вас пока нет заказов.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header" onclick="toggleOrderItems(this)">
                            <div>
                                <span class="order-number">Заказ №<?= htmlspecialchars($order['order_number']) ?></span>
                                <span class="order-date">от <?= date('d.m.Y', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <?php 
                                $subtotal = $order['total_amount'] + ($order['discount_amount'] ?? 0);
                                $hasValidDiscount = ($order['discount_amount'] ?? 0) > 0 && $subtotal > 0 && $order['total_amount'] > 0;
                                ?>
                                <?php if ($hasValidDiscount): ?>
                                    <div class="order-price-details">
                                        <span class="order-subtotal">Было: <?= number_format($subtotal, 0, '.', ' ') ?> ₽</span>
                                        <span class="order-discount">Скидка: -<?= number_format($order['discount_amount'], 0, '.', ' ') ?> ₽</span>
                                        <span class="order-total">Итого: <?= number_format($order['total_amount'], 0, '.', ' ') ?> ₽</span>
                                    </div>
                                <?php else: ?>
                                    <span class="order-total"><?= number_format($order['total_amount'], 0, '.', ' ') ?> ₽</span>
                                <?php endif; ?>
                                <span class="order-status status-<?= htmlspecialchars($order['status']) ?>">
                                    <?php
                                    $statuses = ['pending'=>'Ожидает оплаты','paid'=>'Оплачен','shipped'=>'Отправлен','delivered'=>'Доставлен','cancelled'=>'Отменён'];
                                    echo htmlspecialchars($statuses[$order['status']] ?? $order['status']);
                                    ?>
                                </span>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <div class="action-buttons-group">
                                        <form method="post"><input type="hidden" name="pay_order_id" value="<?= $order['id'] ?>"><button type="submit" class="pay-button">Оплатить</button></form>
                                        <form method="post" onsubmit="return confirm('Отменить заказ?');"><input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>"><button type="submit" class="cancel-button">Отменить</button></form>
                                    </div>
                                <?php endif; ?>
                                <span class="toggle-icon">▼</span>
                            </div>
                        </div>
                        <div class="order-items">
                             <table>
                                <thead><tr><th>Товар</th><th>Кол-во</th><th>Цена</th><th>Сумма</th></tr></thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= (int)$item['quantity'] ?></td>
                                        <td><?= number_format($item['price'], 0, '.', ' ') ?> ₽</td>
                                        <td><?= number_format($item['quantity'] * $item['price'], 0, '.', ' ') ?> ₽</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ИСТОРИЯ АВТОРИЗАЦИЙ -->
        <div class="login-history-section">
            <h3>История входов</h3>
            <?php if (empty($loginHistory)): ?>
                <p style="color:#666;">История входов пуста.</p>
            <?php else: ?>
                <table class="history-table">
                    <thead><tr><th>Дата и время</th><th>IP-адрес</th><th>Устройство / Браузер</th></tr></thead>
                    <tbody>
                        <?php foreach ($loginHistory as $log): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><?= htmlspecialchars($log['ip']) ?></td>
                            <td class="user-agent-cell" title="<?= htmlspecialchars($log['user_agent']) ?>"><?= htmlspecialchars(mb_strimwidth($log['user_agent'], 0, 60, '...')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ИЗБРАННОЕ -->
        <div class="wishlist-section">
            <h3>Избранное</h3>
            <?php if (empty($wishlistItems)): ?>
                <p style="color:#666;">У вас пока нет избранных товаров. <a href="../category.php">Перейти в каталог</a></p>
            <?php else: ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlistItems as $item):
                        $rawImage = !empty($item['image']) ? $item['image'] : '';
                        if ($rawImage && !preg_match('#^(https?://)#', $rawImage)) $rawImage = BASE_URL . ltrim($rawImage, '/');
                        $image = htmlspecialchars($rawImage ?: BASE_URL . 'img/placeholder.jpg');
                        $priceFormatted = number_format($item['price'], 0, '.', ' ');
                    ?>
                    <div class="wishlist-card" data-id="<?= $item['product_id'] ?>">
                        <a href="../product.php?id=<?= $item['product_id'] ?>"><img src="<?= $image ?>" alt="<?= htmlspecialchars($item['title']) ?>"></a>
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <div class="wishlist-price"><?= $priceFormatted ?> ₽</div>
                        <div class="wishlist-actions">
                            <button class="wishlist-remove" data-id="<?= $item['product_id'] ?>">Удалить</button>
                            <button class="wishlist-add-to-cart" data-id="<?= $item['product_id'] ?>">В корзину</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script src="../js/profile.js"></script>
    <?php else: ?>
        <!-- Форма входа для неавторизованных -->
        <div class="login-form">
            <h2>Вход в систему</h2>
            <?php if ($loginError): ?><div class="message-error">⚠️ <?= htmlspecialchars($loginError) ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Логин</label><input type="text" name="username" required autofocus></div>
                <div class="form-group"><label>Пароль</label><input type="password" name="password" required></div>
                <button type="submit" name="login">Войти</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>js/phone_number.js"></script>
</body>
</html>