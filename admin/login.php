<?php
session_name('admin_session');
session_start();
require_once '../config.php';
setup_session_security();
require_once '../config.php';
setup_session_security();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Ошибка безопасности';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password']) && $user['is_admin']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = true;
            $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверные данные или недостаточно прав';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Вход в админ панель</title><link rel="stylesheet" href="css/admin.css"></head>
<body>
<div class="login-container">
    <h2>Вход в панель управления</h2>
    <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="text" name="username" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
</div>
</body>
</html>