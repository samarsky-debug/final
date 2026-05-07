<?php
session_start();
if (!isset($_GET['order'])) {
    header('Location: index.php');
    exit;
}
$orderNumber = htmlspecialchars($_GET['order']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Заказ оформлен | Fashion Future</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-container { text-align: center; margin: 100px auto; max-width: 600px; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #000; color: #fff; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #333; }
    </style>
</head>
<body>

<?php
    require_once 'block/header.php';
?>

<div class="success-container">
    <h1>Спасибо за заказ!</h1>
    <p>Ваш номер заказа: <strong><?= $orderNumber ?></strong></p>
    <p>Мы свяжемся с вами в ближайшее время.</p>
    <a href="index.php" class="btn">Вернуться на главную</a>
</div>


<script src="js/korzina.js"></script>

</body>
</html>