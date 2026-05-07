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
  <title>Возврат и обмен — Fashion Future</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php 
  require_once "block/header.php";
  require_once "block/cart.php";
  ?>

  <main>
    <div class="page-header">
      <h1>Возврат и обмен</h1>
    </div>
    
    <div class="content-container">
      <section class="returns-section">
        <h2>Условия возврата</h2>
        <p>Мы заботимся о вашем комфорте, поэтому вы можете вернуть или обменять товар в течение <strong>14 дней</strong> с момента получения заказа.</p>
        
        <h3>Требования к товару</h3>
        <ul>
          <li>Товар не был в употреблении (нет следов носки, стирки, пятен, повреждений).</li>
          <li>Сохранены все фабричные ярлыки, бирки и упаковка.</li>
          <li>Товар полностью укомплектован (включая подарочные аксессуары, если они были).</li>
        </ul>
        
        <h3>Как оформить возврат</h3>
        <ol>
          <li>Заполните заявление на возврат (скачать бланк можно <a href="#">по ссылке</a> или запросить у менеджера).</li>
          <li>Упакуйте товар вместе с заявлением и чеком (или распечаткой электронного чека).</li>
          <li>Отправьте посылку любым удобным для вас способом (рекомендуем СДЭК или Почту России).</li>
          <li>После получения и проверки товара мы вернем деньги в течение 10 рабочих дней.</li>
        </ol>
        
        <h3>Обмен</h3>
        <p>Если вам не подошел размер или цвет, вы можете обменять товар на другой. Для этого:</p>
        <ul>
          <li>Свяжитесь с нами по почте <a href="mailto:returns@fashionfuture.ru">returns@fashionfuture.ru</a> с темой «Обмен заказа №...».</li>
          <li>Укажите артикул и размер желаемого товара.</li>
          <li>Мы забронируем нужную позицию и отправим вам инструкцию для отправки возврата.</li>
        </ul>
        
        <div class="return-note">
          <p><strong>Обратите внимание:</strong> Стоимость обратной доставки при возврате или обмене оплачивается покупателем, если брак не является производственным. При обнаружении брака мы компенсируем расходы.</p>
          <p>Адрес для возвратов: 123456, г. Москва, ул. Тверская, д. 12, стр. 1, магазин «Fashion Future» (отдел возвратов).</p>
        </div>
      </section>
    </div>
  </main>

  <?php require_once "block/footer.php"; ?>
  
  <script src="js/korzina.js"></script>
</body>
</html>