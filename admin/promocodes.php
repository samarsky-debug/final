<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Промокоды | Админ-панель</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-container">
    <div class="sidebar">
        <h2>Админ-панель</h2>
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="products.php">Товары</a></li>
            <li><a href="orders.php">Заказы</a></li>
            <li><a href="users.php">Пользователи</a></li>
            <li><a href="promocodes.php" class="active">Промокоды</a></li>
            <li><a href="cities.php">Города и доставка</a></li>
            <li><a href="logout.php" onclick="return confirm('Выйти?');">Выход</a></li>
        </ul>
    </div>
    <div class="content">
        <h1>Промокоды</h1>
        <a href="promo_edit.php" class="btn btn-add">+ Добавить промокод</a>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Код</th>
                        <th>Тип</th>
                        <th>Скидка</th>
                        <th>Мин. сумма</th>
                        <th>Макс. скидка</th>
                        <th>Лимит</th>
                        <th>Использовано</th>
                        <th>На пользователя</th>
                        <th>Активен</th>
                        <th>Действует до</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM promocodes ORDER BY id DESC");
                    while ($row = $stmt->fetch()):
                        $expires = $row['expires_at'] ? date('d.m.Y H:i', strtotime($row['expires_at'])) : '∞';
                        $discountValue = $row['discount_type'] == 'percent' 
                            ? $row['discount_value'] . '%' 
                            : number_format($row['discount_value'], 0, '.', ' ') . ' ₽';
                        $maxDiscount = $row['max_discount'] ? number_format($row['max_discount'], 0, '.', ' ') . ' ₽' : '—';
                        $usageLimit = $row['usage_limit'] ?? '—';
                        $usedCount = $row['used_count'] ?? 0;
                        $perUserLimit = $row['per_user_limit'] ?? '—';
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['code']) ?></strong></td>
                        <td><?= $row['discount_type'] == 'percent' ? 'Процент' : 'Фиксированная' ?></td>
                        <td><?= $discountValue ?></td>
                        <td><?= number_format($row['min_order_amount'], 0, '.', ' ') ?> ₽</td>
                        <td><?= $maxDiscount ?></td>
                        <td><?= $usageLimit ?></td>
                        <td><?= $usedCount ?></td>
                        <td><?= $perUserLimit ?></td>
                        <td><?= $row['is_active'] ? ' Да' : ' Нет' ?></td>
                        <td><?= $expires ?></td>
                        <td>
                            <a href="promo_edit.php?id=<?= $row['id'] ?>"> Редактировать</a>
                            <a href="promo_delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Удалить промокод?');"> Удалить</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>