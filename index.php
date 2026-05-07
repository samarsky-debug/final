<?php
session_start();


require_once 'config.php';

if (!isset($pdo)) {
    die('Ошибка: переменная $pdo не определена.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fashion Future</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php 
  require_once "block/header.php"; 
  require_once "block/cart.php";
  ?>
  <main>
    <section class="hero">
  <div class="hero-text">
    <h2>Одежда как<br>вторая кожа</h2>
    <p>Семиотика стиля, материальный манифест вашего «Я». Откройте новую коллекцию.</p>
    <a href="about-us.php" class="btn btn-outline">О бренде</a>
  </div>
  <div class="hero-watch">
  <div class="watch-3d">
    <canvas id="analogWatch" width="280" height="280"></canvas>
  </div>
</div>
</section>
    <div class="fashion-container">
      <div class="fashionone">
        <p>Одежда — это не просто прикладной инструмент для защиты тела от холода или зноя. Это сложнейшая семиотическая система, «вторая кожа» и материальный манифест нашего внутреннего «Я». Если вдуматься, одежда — это единственный объект материального мира, который сопровождает человека от первого вздоха до последнего, становясь молчаливым свидетелем его эволюции.</p>
      </div>
      <div class="fashiontwo">
        <p>Одежда как граница между «Я» и «Миром». Одежда представляет собой пограничное состояние. Она обозначает предел нашего физического тела и начало внешнего пространства. Это мембрана, которая одновременно защищает нашу уязвимость и транслирует нашу силу. Выбирая, что надеть, мы бессознательно решаем, какую часть своей души мы готовы открыть миру, а какую — оставить в сакральной тишине приватности.</p>
      </div>
    </div>

    <!-- Секция "Бестселлеры" (мужская категория) -->
    <section id="men" class="category">
      <div class="bestseller"><h1>Новинки</h1></div>
      <div class="products">
        <?php
        // 1. Получаем ID мужской категории (ищем по названию "Мужчине" или "Мужские")
        $stmt_cat_men = $pdo->prepare("SELECT id FROM categories WHERE name LIKE '%Муж%' LIMIT 1");
        $stmt_cat_men->execute();
        $men_cat = $stmt_cat_men->fetch(PDO::FETCH_ASSOC);
        
        if ($men_cat) {
            $men_category_id = $men_cat['id'];
            // Выбираем последние 3 товара из этой категории
            $sql_men = "SELECT 
                            p.id, 
                            p.title AS name, 
                            p.price,
                            (SELECT image_url FROM product_images 
                             WHERE product_id = p.id 
                             ORDER BY is_main DESC, sort_order LIMIT 1) AS image_url
                        FROM products p
                        WHERE p.category_id = :cat_id
                        ORDER BY p.id DESC
                        LIMIT 3";
            $stmt_men = $pdo->prepare($sql_men);
            $stmt_men->execute([':cat_id' => $men_category_id]);
            $men_products = $stmt_men->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Если категория не найдена – просто выводим последние 3 товара (без привязки к полу)
            $sql_men = "SELECT 
                            p.id, 
                            p.title AS name, 
                            p.price,
                            (SELECT image_url FROM product_images 
                             WHERE product_id = p.id 
                             ORDER BY is_main DESC, sort_order LIMIT 1) AS image_url
                        FROM products p
                        ORDER BY p.id DESC
                        LIMIT 3";
            $stmt_men = $pdo->query($sql_men);
            $men_products = $stmt_men->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (!empty($men_products)):
            foreach ($men_products as $product):
                $product_id = $product['id'];
                $name = htmlspecialchars($product['name']);
                $price = (int)$product['price'];
                $price_formatted = number_format($price, 0, '.', ' ');
                $image_url = htmlspecialchars($product['image_url'] ?? '');
        ?>
        <div class="product" 
            data-id="<?= $product_id ?>" 
            data-name="<?= $name ?>" 
            data-price="<?= $price ?>" 
            data-image="<?= $image_url ?>">
          <a href="product.php?id=<?= $product_id ?>">
            <?php if (!empty($image_url)): ?>
              <img src="<?= $image_url ?>" alt="<?= $name ?>">
            <?php else: ?>
              <div class="no-image" style="width:100%; height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">Нет фото</div>
            <?php endif; ?>
          </a>
          <div class="product-meta">
            <h3><?= $name ?></h3>
            <p class="price"><?= $price_formatted ?> ₽</p>
            <a href="product.php?id=<?= $product_id ?>" class="btn-small">Подробнее</a>
          </div>
        </div>
        <?php 
            endforeach;
        else:
            echo '<p class="no-products">Нет товаров в мужской категории.</p>';
        endif;
        ?>
      </div>
    </section>

    <!-- Секция "Женская категория" -->
    <section id="women" class="category">
      <div class="products">
        <?php
        // 2. Получаем ID женской категории
        $stmt_cat_women = $pdo->prepare("SELECT id FROM categories WHERE name LIKE '%Жен%' LIMIT 1");
        $stmt_cat_women->execute();
        $women_cat = $stmt_cat_women->fetch(PDO::FETCH_ASSOC);
        
        if ($women_cat) {
            $women_category_id = $women_cat['id'];
            $sql_women = "SELECT 
                              p.id, 
                              p.title AS name, 
                              p.price,
                              (SELECT image_url FROM product_images 
                               WHERE product_id = p.id 
                               ORDER BY is_main DESC, sort_order LIMIT 1) AS image_url
                          FROM products p
                          WHERE p.category_id = :cat_id
                          ORDER BY p.id DESC
                          LIMIT 3";
            $stmt_women = $pdo->prepare($sql_women);
            $stmt_women->execute([':cat_id' => $women_category_id]);
            $women_products = $stmt_women->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Если категории нет – выводим следующие 3 товара (пропустив уже показанные)
            $sql_women = "SELECT 
                              p.id, 
                              p.title AS name, 
                              p.price,
                              (SELECT image_url FROM product_images 
                               WHERE product_id = p.id 
                               ORDER BY is_main DESC, sort_order LIMIT 1) AS image_url
                          FROM products p
                          ORDER BY p.id DESC
                          LIMIT 3 OFFSET 3";
            $stmt_women = $pdo->query($sql_women);
            $women_products = $stmt_women->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (!empty($women_products)):
            foreach ($women_products as $product):
                $product_id = $product['id'];
                $name = htmlspecialchars($product['name']);
                $price = (int)$product['price'];
                $price_formatted = number_format($price, 0, '.', ' ');
                $image_url = htmlspecialchars($product['image_url'] ?? '');
        ?>
        <div class="product" 
            data-id="<?= $product_id ?>" 
            data-name="<?= $name ?>" 
            data-price="<?= $price ?>" 
            data-image="<?= $image_url ?>">
          <a href="product.php?id=<?= $product_id ?>">
            <?php if (!empty($image_url)): ?>
              <img src="<?= $image_url ?>" alt="<?= $name ?>">
            <?php else: ?>
              <div class="no-image" style="width:100%; height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">Нет фото</div>
            <?php endif; ?>
          </a>
          <div class="product-meta">
            <h3><?= $name ?></h3>
            <p class="price"><?= $price_formatted ?> ₽</p>
            <a href="product.php?id=<?= $product_id ?>" class="btn-small">Подробнее</a>
          </div>
        </div>
        <?php 
            endforeach;
        else:
            echo '<p class="no-products">Нет товаров в женской категории.</p>';
        endif;
        ?>
      </div>
    </section>
  </main>

  <!-- панель корзины -->
  <div class="cart-overlay" id="cartOverlay"></div>
  <div class="cart-panel" id="cartPanel">
    <div class="cart-header">
      <h2>Корзина</h2>
      <button class="close-cart" id="closeCart">&times;</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer" id="cartFooter">
      <div class="cart-total">
        <span>Итого:</span>
        <span id="cartTotal">0 ₽</span>
      </div>
      <button class="checkout-btn" id="checkoutBtn">Оформить заказ</button>
    </div>
  </div>

<?php
require_once "block/footer.php";
?>

<script src="js/korzina.js"></script>
<script src="js/canvas.js"></script>
</body>
</html>