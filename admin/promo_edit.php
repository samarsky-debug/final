<?php require_once 'auth_check.php';
$id = (int)($_GET['id'] ?? 0);
$promo = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM promocodes WHERE id = ?");
    $stmt->execute([$id]);
    $promo = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $min_order_amount = (float)$_POST['min_order_amount'];
    $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $per_user_limit = !empty($_POST['per_user_limit']) ? (int)$_POST['per_user_limit'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE promocodes SET 
            code=?, discount_type=?, discount_value=?, min_order_amount=?, 
            max_discount=?, usage_limit=?, per_user_limit=?, is_active=?, expires_at=? 
            WHERE id=?");
        $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, 
                        $max_discount, $usage_limit, $per_user_limit, $is_active, $expires_at, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO promocodes 
            (code, discount_type, discount_value, min_order_amount, max_discount, 
             usage_limit, per_user_limit, is_active, expires_at) 
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$code, $discount_type, $discount_value, $min_order_amount, 
                        $max_discount, $usage_limit, $per_user_limit, $is_active, $expires_at]);
    }
    header('Location: promocodes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Редактировать' : 'Добавить' ?> промокод</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-container">
    <div class="sidebar">...</div>
    <div class="content">
        <h1><?= $id ? 'Редактирование промокода' : 'Новый промокод' ?></h1>
        <form method="post">
            <label>Код промокода (латиница, цифры, автоматом в верхний регистр)</label>
            <input type="text" name="code" value="<?= htmlspecialchars($promo['code'] ?? '') ?>" required
                   style="text-transform: uppercase; font-family: monospace;"
                   oninput="this.value = this.value.toUpperCase()">

            <label>Тип скидки</label>
            <select name="discount_type">
                <option value="percent" <?= ($promo['discount_type'] ?? '') == 'percent' ? 'selected' : '' ?>>Процент (%)</option>
                <option value="fixed" <?= ($promo['discount_type'] ?? '') == 'fixed' ? 'selected' : '' ?>>Фиксированная (₽)</option>
            </select>

            <label>Значение скидки</label>
            <input type="number" step="0.01" name="discount_value" value="<?= $promo['discount_value'] ?? '' ?>" required>

            <label>Минимальная сумма заказа (₽)</label>
            <input type="number" step="0.01" name="min_order_amount" value="<?= $promo['min_order_amount'] ?? 0 ?>">

            <label>Максимальная скидка (₽) – оставить пустым, если без ограничения</label>
            <input type="number" step="0.01" name="max_discount" value="<?= $promo['max_discount'] ?? '' ?>">

            <label>Общий лимит использований (пусто – без лимита)</label>
            <input type="number" name="usage_limit" value="<?= $promo['usage_limit'] ?? '' ?>" min="1">

            <label>Лимит на одного пользователя (пусто – без лимита)</label>
            <input type="number" name="per_user_limit" value="<?= $promo['per_user_limit'] ?? '' ?>" min="1">

            <label>Действителен до (оставьте пустым, если бессрочно)</label>
            <input type="datetime-local" name="expires_at" 
                   value="<?= $promo['expires_at'] ? date('Y-m-d\TH:i', strtotime($promo['expires_at'])) : '' ?>">

            <label style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="is_active" value="1" <?= ($promo['is_active'] ?? 1) ? 'checked' : '' ?>>
                Активен
            </label>

            <div class="button-group" style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit">Сохранить</button>
                <a href="promocodes.php" class="btn" style="background:#666; text-decoration: none;">Отмена</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>