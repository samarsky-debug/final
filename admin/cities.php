<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Города | Админ-панель</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .search-box { margin-bottom: 1rem; }
        .search-box input { width: 250px; padding: 0.5rem; border-radius: 30px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar">
        <h2>Админ-панель</h2>
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="products.php">Товары</a></li>
            <li><a href="orders.php">Заказы</a></li>
            <li><a href="users.php">Пользователи</a></li>
            <li><a href="promocodes.php">Промокоды</a></li>
            <li><a href="cities.php" class="active">Города и доставка</a></li>
            <li><a href="logout.php" onclick="return confirm('Выйти?');">Выход</a></li>
        </ul>
    </div>
    <div class="content">
        <h1>Города и стоимость доставки</h1>
        <a href="city_edit.php" class="btn btn-add">+ Добавить город</a>
        <div class="search-box">
            <input type="text" id="searchCity" placeholder="Поиск города...">
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Город</th><th>Регион</th><th>Стоимость доставки (₽)</th><th>Активен</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM cities ORDER BY sort_order, name");
                    while ($city = $stmt->fetch()):
                    ?>
                    <tr>
                        <td><?= $city['id'] ?></td>
                        <td><?= htmlspecialchars($city['name']) ?></td>
                        <td><?= htmlspecialchars($city['region'] ?? '—') ?></td>
                        <td><?= number_format($city['delivery_price'], 0, '.', ' ') ?> ₽</td>
                        <td><?= $city['is_active'] ? ' Да' : ' Нет' ?></td>
                        <td>
                            <a href="city_edit.php?id=<?= $city['id'] ?>"> Редактировать</a>
                            <a href="city_delete.php?id=<?= $city['id'] ?>" onclick="return confirm('Удалить город?');"> Удалить</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const searchInput = document.getElementById('searchCity');
    if (!searchInput) return;

    // Получаем строки таблицы
    const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
    // Кешируем название города в нижнем регистре и разбиваем на слова (по пробелам, дефисам, скобкам)
    const rowData = rows.map(row => {
        const cityName = row.cells[1].innerText.toLowerCase();
        const words = cityName.split(/[\s\-\.\(\)]+/).filter(w => w.length > 0);
        return {
            element: row,
            cityName: cityName,
            words: words
        };
    });

    let timeoutId = null;

    function matchesQuery(item, query) {
        if (query === '') return true;
        // Проверяем, начинается ли хотя бы одно слово с query
        for (let i = 0; i < item.words.length; i++) {
            if (item.words[i].startsWith(query)) return true;
        }
        // Дополнительно проверяем вхождение в полное название (для длинных запросов)
        if (item.cityName.includes(query)) return true;
        return false;
    }

    function filterRows() {
        const query = searchInput.value.trim().toLowerCase();
        if (query === '') {
            for (let i = 0; i < rowData.length; i++) {
                rowData[i].element.style.display = '';
            }
            return;
        }
        requestAnimationFrame(() => {
            for (let i = 0; i < rowData.length; i++) {
                const item = rowData[i];
                const found = matchesQuery(item, query);
                item.element.style.display = found ? '' : 'none';
            }
        });
    }

    searchInput.addEventListener('input', function() {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(filterRows, 200); // debounce 200 мс
    });
})();
</script>
</body>
</html>