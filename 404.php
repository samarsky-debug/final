<?php
// 404.php
http_response_code(404);
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена — Fashion Future</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Дополнительные стили для 404 */
        .error-404-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 2rem;
        }
        .error-content {
            max-width: 600px;
            animation: fadeUp 0.6s ease-out;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
            color: #000;
            line-height: 1;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            text-shadow: 4px 4px 12px rgba(0,0,0,0.05);
        }
        .error-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            color: #1e1e1e;
        }
        .error-text {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-secondary {
            background-color: transparent;
            border: 2px solid #000;
            color: #000;
        }
        .btn-secondary:hover {
            background-color: #000;
            color: #fff;
        }
        @media (max-width: 600px) {
            .error-code { font-size: 5rem; }
            .error-title { font-size: 1.5rem; }
            .error-text { font-size: 1rem; }
            .error-actions { flex-direction: column; align-items: center; }
            .error-actions .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

<?php require_once 'block/header.php'; ?>

<main>
    <div class="error-404-container">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">Страница не найдена</h1>
            <p class="error-text">
                К сожалению, такой страницы не существует.<br>
                Возможно, она была удалена или вы перешли по неверной ссылке.
            </p>
            <div class="error-actions">
                <a href="index.php" class="btn">На главную</a>
                <a href="category.php" class="btn btn-secondary">Перейти в каталог</a>
            </div>
        </div>
    </div>
</main>

<?php require_once 'block/footer.php'; ?>

</body>
</html>