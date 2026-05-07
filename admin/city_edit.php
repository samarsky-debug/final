<?php require_once 'auth_check.php';
$id = (int)($_GET['id'] ?? 0);
$city = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM cities WHERE id = ?");
    $stmt->execute([$id]);
    $city = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $region = trim($_POST['region']);
    $delivery_price = (float)$_POST['delivery_price'];
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE cities SET name=?, region=?, delivery_price=?, sort_order=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $region, $delivery_price, $sort_order, $is_active, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cities (name, region, delivery_price, sort_order, is_active) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $region, $delivery_price, $sort_order, $is_active]);
    }
    header('Location: cities.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title><?= $id ? 'Редактировать' : 'Добавить' ?> город</title><link rel="stylesheet" href="css/admin.css"></head>
<body>
<div class="admin-container">
    <div class="sidebar">...</div>
    <div class="content">
        <h1><?= $id ? 'Редактирование' : 'Новый город' ?></h1>
        <form method="post">
            <label>Название города *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($city['name'] ?? '') ?>" required>
            <label>Регион (область)</label>
            <input type="text" name="region" value="<?= htmlspecialchars($city['region'] ?? '') ?>">
            <label>Стоимость доставки (₽) *</label>
            <input type="number" step="0.01" name="delivery_price" value="<?= $city['delivery_price'] ?? 0 ?>" required>
            <label>Порядок сортировки</label>
            <input type="number" name="sort_order" value="<?= $city['sort_order'] ?? 0 ?>">
            <label><input type="checkbox" name="is_active" value="1" <?= ($city['is_active'] ?? 1) ? 'checked' : '' ?>> Активен</label>
            <button type="submit">Сохранить</button>
        </form>
    </div>
</div>
</body>
</html>