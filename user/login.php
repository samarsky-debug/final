<?php
session_start();
require_once '../config.php';

$error = '';

// Проверяем, что запрос к серверу выполнен методом POST (отправлена форма)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Получаем данные из формы, обрезаем пробелы у логина
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ВАЛИДАЦИЯ 1: проверяем, что поля не пустые
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';   // Если пусто – сообщаем ошибку
    } else {
        
        // ВАЛИДАЦИЯ 2: проверяем, существует ли подключение к базе данных
        if (!isset($pdo)) {
            $error = 'Ошибка подключения к базе данных';
        } else {
            
            // Подготавливаем SQL-запрос для поиска пользователя по логину
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);   // Выполняем запрос с переданным логином
            $user = $stmt->fetch();        // Получаем данные пользователя (или false, если не найден)

            // ВАЛИДАЦИЯ 3: пользователь существует И пароль совпадает с хэшем в БД
            if ($user && password_verify($password, $user['password'])) {
                
                // Успешная авторизация – сохраняем ID и логин в сессию
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Запись в историю входов
                $ip = getUserIP();
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $stmt = $pdo->prepare("INSERT INTO login_history (user_id, ip, user_agent) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $ip, $userAgent]);
                
                // Перенаправляем пользователя на страницу, куда он пытался попасть до входа,
                // либо на главную, если такой страницы нет
                $redirect = $_SESSION['redirect_after_login'] ?? '../index.php';
                unset($_SESSION['redirect_after_login']); // удаляем временную переменную
                header('Location: ' . $redirect);          // отправляем заголовок перенаправления
                exit; // завершаем скрипт, чтобы дальнейший код не выполнялся
            } else {
                // ВАЛИДАЦИЯ 4: если пользователь не найден или пароль неверный
                $error = 'Неверное имя пользователя или пароль';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/login.css">
</head>
<body>

<header>
    <div class="header-content">
        <div class="logo">
            <a href="../index.php"><h1>Fashion Future</h1></a>
        </div>
        <div style="display: flex; gap: 20px; align-items: center;">
        </div>
    </div>
</header>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Вход в систему</h2>

        <?php if ($error): ?>
            <div class="message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login">Войти</button>
        </form>

        <div class="auth-footer">
            Нет аккаунта? <a href="registration.php">Зарегистрироваться</a>
        </div>
        <div class="forgot-password">
            <a href="forgot_password.php">Забыли пароль?</a>
        </div>
    </div>
</div>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>Fashion Future</h3>
            <p>Стиль и качество с 2020 года</p>
        </div>
        <div class="footer-col">
            <h3>Помощь</h3>
            <ul>
                <li><a href="delivery.php">Доставка и оплата</a></li>
                <li><a href="returns.php">Возврат</a></li>
                <li><a href="faq.php">FAQ</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h3>Контакты</h3>
            <p>Email: FashionFuture@brand.ru</p>
            <p>Тел: +7 (999) 123-45-67</p>
        </div>
    </div>
    <div class="legal-info" style="text-align: center; margin-top: 40px;">
        © 2026 Fashion Future. Все права защищены.
    </div>
</footer>

</body>
</html>