<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Введите email';
    } else {
        // Проверяем, существует ли такой email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Не сообщаем, что email не найден (безопасность)
            $message = 'Если такой email зарегистрирован, вы получите инструкцию по сбросу пароля.';
        } else {
            // Генерируем токен
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Удаляем старые токены для этого email
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            
            // Сохраняем новый
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);
            
            // Формируем ссылку для сброса
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/reset_password.php?token=" . $token;
            
            // Отправляем письмо (можно использовать mail() или PHPMailer)
            $subject = "Восстановление пароля на Fashion Future";
            $messageBody = "Здравствуйте!\n\nВы запросили сброс пароля. Перейдите по ссылке, чтобы установить новый пароль:\n$resetLink\n\nСсылка действительна 1 час.\n\nЕсли вы не запрашивали сброс, просто проигнорируйте это письмо.\n\nС уважением, Fashion Future.";
            $headers = "From: noreply@fashionfuture.ru\r\nContent-Type: text/plain; charset=utf-8";
            
            if (mail($email, $subject, $messageBody, $headers)) {
                $message = 'Инструкция по сбросу отправлена на ваш email.';
            } else {
                // Для локального тестирования, если mail() не работает, выведем ссылку в лог
                error_log("Reset link for $email: $resetLink");
                $message = 'Ссылка для сброса сгенерирована, но не удалось отправить письмо. Проверьте настройки почты. (Для отладки ссылка сохранена в лог)';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Восстановление пароля</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Забыли пароль?</h2>
        <?php if ($message): ?>
            <div class="message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Ваш email</label>
                <input type="email" name="email" required>
            </div>
                <button type="submit">Отправить ссылку</button>
        </form>
        <div class="auth-footer">
            <a href="login.php">Вспомнили пароль? Войти</a>
        </div>
    </div>
</div>
</body>
</html>