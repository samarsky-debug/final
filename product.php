<?php
error_reporting(E_ALL);

require_once 'config.php';
// Недавно просмотренные товары
if (!isset($_SESSION['recent_products'])) {
    $_SESSION['recent_products'] = [];
}
$product_id = (int)$_GET['id'];
// Удаляем, если уже есть, чтобы добавить в начало
if (($key = array_search($product_id, $_SESSION['recent_products'])) !== false) {
    unset($_SESSION['recent_products'][$key]);
}
array_unshift($_SESSION['recent_products'], $product_id);

$_SESSION['recent_products'] = array_slice($_SESSION['recent_products'], 0, 10);
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    die('Товар не найден');
}
$product_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT id, title, name, price, description, category, size, material, article, insulation, temp_range, stock, is_active
    FROM products
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    die('Товар не найден');
}

// изображения
$stmt_img = $pdo->prepare("
    SELECT image_url, is_main
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, sort_order ASC
");
$stmt_img->execute([$product_id]);
$images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

$main_image = '';
$thumbnails = [];

foreach ($images as $img) {
    $src = !empty($img['image_url']) ? $img['image_url'] : $img['image_path'];
    if (empty($src)) continue;

    // Нормализация пути
    $src = ltrim($src, '/');
    $src = preg_replace('#^\.\./#', '', $src);
    $fullSrc = BASE_URL . $src;

    if ($img['is_main']) {
        $main_image = $fullSrc;
    }
    $thumbnails[] = $fullSrc;
}

if (empty($main_image) && !empty($thumbnails)) {
    $main_image = $thumbnails[0];
}
if (empty($main_image)) {
    $main_image = BASE_URL . 'img/placeholder.jpg';
}

// Форматирование цены
$price_formatted = number_format($product['price'], 0, '.', ' ');

$sizes = !empty($product['size']) ? array_map('trim', explode(',', $product['size'])) : [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($product['title']) ?> — Fashion Future</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
</head>
<body>
<?php require_once "block/header.php"; ?>

<main>
    <div class="product-card-container">
        <div class="images-section">
            <div class="main-img-wrapper">
                <img src="<?= $main_image ?>" alt="<?= htmlspecialchars($product['title']) ?>" id="main-img">
            </div>
            <?php if (count($thumbnails) > 1): ?>
            <div class="thumbnails">
                <?php foreach ($thumbnails as $thumb): ?>
                    <img src="<?= $thumb ?>" alt="вид" class="thumbnail">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="description-section" 
             data-id="<?= $product['id'] ?>"
             data-name="<?= htmlspecialchars($product['title']) ?>"
             data-price="<?= $product['price'] ?>"
             data-image="<?= $main_image ?>">
            
            <span class="category"><?= htmlspecialchars($product['category'] ?: 'Коллекция 2026') ?></span>
            <h2 class="product-title"><?= htmlspecialchars($product['title']) ?></h2>
            <p class="description-text"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

            <?php if (!empty($sizes)): ?>
                <span class="section-label">Выберите размер</span>
                <div class="size-picker">
                    <?php foreach ($sizes as $size): ?>
                        <div class="size-badge" data-size="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="specs">
                <?php if (!empty($product['material'])): ?>
                    <div class="spec-item"><span>Материал</span><strong><?= htmlspecialchars($product['material']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($product['insulation'])): ?>
                    <div class="spec-item"><span>Утеплитель</span><strong><?= htmlspecialchars($product['insulation']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($product['temp_range'])): ?>
                    <div class="spec-item"><span>Темп. режим</span><strong><?= htmlspecialchars($product['temp_range']) ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($product['article'])): ?>
                    <div class="spec-item"><span>Артикул</span><strong><?= htmlspecialchars($product['article']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="price"><?= $price_formatted ?> ₽</div>
            <button class="add-to-cart-btn">Добавить в корзину</button>
            <button class="wishlist-btn" data-id="<?= $product['id'] ?>">❤️ В избранное</button>

            <div class="delivery-info">
                <div class="info-item">📦 Бесплатная доставка от 15 000 ₽</div>
                <div class="info-item">🔄 Возврат в течение 14 дней</div>
                <div class="info-item">🛡️ Гарантия качества 1 год</div>
            </div>
        </div>
    </div>
</main>

<?php 
require_once "block/footer.php";
require_once "block/cart.php";
?>
<script src="<?= BASE_URL ?>js/change-image.js"></script>
<script src="<?= BASE_URL ?>js/wishlist.js"></script>
</body>
</html>