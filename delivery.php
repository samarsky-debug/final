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
  <title>Доставка — Fashion Future</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php 
  require_once "block/header.php";
  require_once "block/cart.php";
  ?>
<
  <main>
    <div class="page-header">
      <h1>Доставка</h1>
    </div>
    
    <div class="content-container">
      <section class="delivery-section">
        <h2>Способы и стоимость доставки</h2>
        <p>Мы доставляем заказы по всей России и в некоторые страны СНГ. Все отправления страхуются и отслеживаются.</p>
        
        <h3>Доставка в пункты выдачи заказов (ПВЗ) и постаматы</h3>
        <ul>
          <li>Стоимость: 500 ₽</li>
          <li>Срок: 5–14 рабочих дней</li>
          <li>Более 5000 точек по России (СДЭК, Boxberry, 5Post)</li>
        </ul>
        
        <h3>Почта России</h3>
        <ul>
          <li>Стоимость: от 400 ₽ (зависит от региона и веса)</li>
          <li>Срок: 15–30 рабочих дней</li>
          <li>Отправление 1-го класса или заказное с описью</li>
        </ul>
        
        <h3>Бесплатная доставка</h3>
        <p>При сумме заказа от 15 000 ₽ — доставка по всей России бесплатно (любым из перечисленных способов).</p>
        
        <h3>Международная доставка</h3>
        <p>Доставляем в страны СНГ (Казахстан, Беларусь, Армения, Кыргызстан). Стоимость рассчитывается индивидуально. Для оформления напишите нам на почту <a href="mailto:shop@fashionfuture.ru">fashionfuture@mair.ru</a>.</p>
        
        <div class="delivery-note">
          <p><strong>Важно:</strong> После оформления заказа вы получите трек-номер для отслеживания.</p>
        </div>
      </section>
    </div>
  </main>

  <?php require_once "block/footer.php"; ?>
  
  <script src="js/korzina.js"></script>
</body>
</html>