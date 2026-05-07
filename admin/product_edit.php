<?php
require_once 'auth_check.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = (float)$_POST['price'];
    $description = $_POST['description'];
    $category = trim($_POST['category']);
    $size = $_POST['size'];
    $material = $_POST['material'];
    $article = $_POST['article'];
    $insulation = $_POST['insulation'];
    $temp_range = $_POST['temp_range'];
    $stock = (int)$_POST['stock'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE products SET title=?, price=?, description=?, category=?, size=?, material=?, article=?, insulation=?, temp_range=?, stock=?, is_active=? WHERE id=?");
        $stmt->execute([$title, $price, $description, $category, $size, $material, $article, $insulation, $temp_range, $stock, $is_active, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (title, name, price, description, category, size, material, article, insulation, temp_range, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $title, $price, $description, $category, $size, $material, $article, $insulation, $temp_range, $stock, $is_active]);
        $id = $pdo->lastInsertId();
    }

    // Загрузка изображений
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/fashion-main/img/products/';
        $relativeDir = 'img/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $stmtSort = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?");
        $stmtSort->execute([$id]);
        $nextSort = (int)$stmtSort->fetchColumn();

        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;

            $fileName = 'product_' . $id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $fullPath = $uploadDir . $fileName;
            if (move_uploaded_file($tmpName, $fullPath)) {
                $dbPath = $relativeDir . $fileName;
                $isMain = ($index === 0 && $nextSort === 0) ? 1 : 0;
                $sortOrder = $nextSort + $index + 1;
                $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_main, sort_order) VALUES (?, ?, ?, ?)");
                $stmtImg->execute([$id, $dbPath, $isMain, $sortOrder]);
            }
        }
    }

    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $id ? 'Редактировать' : 'Добавить' ?> товар</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-container">
    <div class="sidebar">...</div>
    <div class="content">
        <h1><?= $id ? 'Редактирование' : 'Новый товар' ?></h1>
        <form method="post" enctype="multipart/form-data">
            <label>Название товара</label>
            <input type="text" name="title" value="<?= htmlspecialchars($product['title'] ?? '') ?>" required>

            <label>Цена (₽)</label>
            <input type="number" step="0.01" name="price" value="<?= $product['price'] ?? '' ?>" required>

            <label>Категория</label>
            <input type="text" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>" placeholder="например, puffer, outerwear, tshirt">

            <label>Описание</label>
            <textarea name="description" rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

            <label>Размеры (через запятую, например: S,M,L,XL)</label>
            <input type="text" name="size" value="<?= htmlspecialchars($product['size'] ?? '') ?>">

            <label>Материал</label>
            <input type="text" name="material" value="<?= htmlspecialchars($product['material'] ?? '') ?>">

            <label>Артикул</label>
            <input type="text" name="article" value="<?= htmlspecialchars($product['article'] ?? '') ?>">

            <label>Утеплитель</label>
            <input type="text" name="insulation" value="<?= htmlspecialchars($product['insulation'] ?? '') ?>">

            <label>Температурный режим</label>
            <input type="text" name="temp_range" value="<?= htmlspecialchars($product['temp_range'] ?? '') ?>" placeholder="например, до -15°C">

            <label>Остаток на складе (шт.)</label>
            <input type="number" name="stock" value="<?= $product['stock'] ?? 0 ?>">

            <label>
                <input type="checkbox" name="is_active" value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                Активен (отображать на сайте)
            </label>

            <label>Изображения товара</label>
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
            <small>Изображения сохранятся в папку img/products/</small>

            <?php if ($id): ?>
                <div style="margin: 1rem 0;">
                    <strong>Текущие изображения:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                        <?php
                        $stmtImg = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order");
                        $stmtImg->execute([$id]);
                        $images = $stmtImg->fetchAll();
                        foreach ($images as $img):
                            $imgUrl = BASE_URL . ltrim($img['image_url'], '/');
                        ?>
                            <div style="border:1px solid #ddd; padding:0.5rem;">
                                <img src="<?= $imgUrl ?>" style="width:100px; height:100px; object-fit:cover;">
                                <div>
                                    <?php if (!$img['is_main']): ?>
                                        <a href="set_main_image.php?id=<?= $img['id'] ?>&product_id=<?= $id ?>">⭐ Главное</a> |
                                    <?php else: ?>
                                        <span>★ Главное</span> |
                                    <?php endif; ?>
                                    <a href="delete_image.php?id=<?= $img['id'] ?>&product_id=<?= $id ?>" onclick="return confirm('Удалить?')">✖ Удалить</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <button type="submit">Сохранить товар</button>
        </form>
    </div>
</div>
</body>
</html>