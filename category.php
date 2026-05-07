<?php
session_start();
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'samarskiy';

$mysqli = new mysqli($host, $user, $password, $database);
if ($mysqli->connect_error) {
    die('Ошибка подключения: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

$base_url = '/';
$script_name = $_SERVER['SCRIPT_NAME'];
$script_dir = dirname($script_name);
if ($script_dir != '/' && $script_dir != '\\') {
    $base_url = $script_dir . '/';
}

// получение списка избранных товаров для авторизованного пользователя
$wishlistIds = [];
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $wishStmt = $mysqli->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wishStmt->bind_param("i", $userId);
    $wishStmt->execute();
    $wishResult = $wishStmt->get_result();
    while ($row = $wishResult->fetch_assoc()) {
        $wishlistIds[] = $row['product_id'];
    }
}

$sql = "
    SELECT p.*, 
        (SELECT image_url 
         FROM product_images 
         WHERE product_id = p.id 
         ORDER BY is_main DESC, sort_order ASC LIMIT 1) AS main_image
    FROM products p
    ORDER BY p.id ASC
";
$result = $mysqli->query($sql);
if (!$result) {
    die('Ошибка запроса: ' . $mysqli->error);
}
$products = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог — Fashion Future</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php require_once "block/header.php"; ?>
<?php require_once "block/cart.php"; ?>

<main>
    <div class="filter-container">
        <button class="filter-btn active" data-category="all">Все</button>
        <button class="filter-btn" data-category="puffer">Пуховики</button>
        <button class="filter-btn" data-category="outerwear">Толстовки</button>
        <button class="filter-btn" data-category="longsleeve">Лонгсливы</button>
        <button class="filter-btn" data-category="tshirt">Футболки</button>
        <button class="filter-btn" data-category="bottoms">Штаны</button>
        <button class="filter-btn" data-category="sneakers">Кроссовки</button>
        <button class="filter-btn" data-category="accessories">Аксессуары</button>
    </div>

    <div class="catalog-header">
        <h2 class="section-title">Man's clothes</h2>
        <div class="sort-container">
            <label for="sortSelect">Сортировать:</label>
            <select id="sortSelect">
                <option value="default">По умолчанию</option>
                <option value="price_asc">По возрастанию цены</option>
                <option value="price_desc">По убыванию цены</option>
            </select>
        </div>
    </div>

    <div class="products" id="mens-clothes">
        <?php if (empty($products)): ?>
            <p>Нет товаров в базе данных.</p>
        <?php else: ?>
            <?php foreach ($products as $product):
                $id = (int)($product['id'] ?? 0);
                $name = htmlspecialchars($product['title'] ?? 'Без названия');
                $price = (float)($product['price'] ?? 0);
                $category = htmlspecialchars($product['category'] ?? 'other');
                $shortDesc = htmlspecialchars(mb_substr($product['description'] ?? '', 0, 100));
                
                $raw_image = $product['main_image'] ?? '';
                if (!empty($raw_image)) {
                    $clean_path = preg_replace('#^\.\./#', '', $raw_image);
                    $clean_path = ltrim($clean_path, '/');
                    $image = $base_url . $clean_path;
                } else {
                    $image = $base_url . 'img/placeholder.jpg';
                }
                $link = "product.php?id=$id";
                
                $inWishlist = in_array($id, $wishlistIds);
                $heartClass = $inWishlist ? 'active' : '';
                $heartText = $inWishlist ? '❤️' : '♡';
            ?>
            <div class="product" data-category="<?= $category ?>" data-id="<?= $id ?>" data-price="<?= $price ?>">
                <a href="<?= $link ?>" class="product-link">
                    <img src="<?= $image ?>" alt="<?= $name ?>" onerror="this.src='<?= $base_url ?>img/placeholder.jpg'">
                </a>
                <h3><?= $name ?></h3>
                <?php if ($shortDesc): ?>
                    <p><?= $shortDesc ?>…</p>
                <?php endif; ?>
                <div class="product-price"><?= number_format($price, 0, '', ' ') ?> ₽</div>
                <div class="product-meta-extra">
                    <a href="<?= $link ?>" class="btn-small">Подробнее</a>
                    <button class="wishlist-btn <?= $heartClass ?>" data-id="<?= $id ?>"><?= $heartText ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once "block/footer.php"; ?>

<style>
.catalog-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.sort-container {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}
.sort-container label {
    font-weight: 500;
    color: #333;
}
#sortSelect {
    padding: 0.4rem 0.8rem;
    border-radius: 30px;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
}
@media (max-width: 600px) {
    .catalog-header {
        flex-direction: column;
        gap: 0.8rem;
    }
}
</style>

<script>
// Избранное (работает через делегирование)
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const productId = btn.dataset.id;
    const isActive = btn.classList.contains('active');
    const action = isActive ? 'remove' : 'add';

    const formData = new FormData();
    formData.append('action', action);
    formData.append('product_id', productId);

    try {
        const response = await fetch('cart_orders/wishlist_handler.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            if (action === 'add') {
                btn.classList.add('active');
                btn.textContent = '❤️';
            } else {
                btn.classList.remove('active');
                btn.textContent = '♡';
            }
        } else if (result.redirect) {
            window.location.href = result.redirect;
        } else {
            alert(result.error || 'Ошибка');
        }
    } catch (err) {
        console.error(err);
        alert('Ошибка соединения');
    }
});

// Фильтрация и сортировка
(function() {
    const productsContainer = document.getElementById('mens-clothes');
    const allProducts = Array.from(document.querySelectorAll('.product'));
    const filterBtns = document.querySelectorAll('.filter-btn');
    const sortSelect = document.getElementById('sortSelect');
    let currentCategory = 'all';

    function render() {
        let visibleProducts = allProducts.filter(p => {
            return currentCategory === 'all' || p.dataset.category === currentCategory;
        });
        const sortValue = sortSelect.value;
        if (sortValue === 'price_asc') {
            visibleProducts.sort((a,b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price));
        } else if (sortValue === 'price_desc') {
            visibleProducts.sort((a,b) => parseFloat(b.dataset.price) - parseFloat(a.dataset.price));
        } else {
            visibleProducts.sort((a,b) => parseInt(a.dataset.id) - parseInt(b.dataset.id));
        }
        productsContainer.innerHTML = '';
        visibleProducts.forEach(p => productsContainer.appendChild(p));
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentCategory = btn.dataset.category;
            render();
        });
    });

    sortSelect.addEventListener('change', () => render());
})();
</script>

</body>
</html>