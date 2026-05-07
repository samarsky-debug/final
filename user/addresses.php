<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Обработка добавления адреса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $city_id = (int)$_POST['city_id'];
    $zip = trim($_POST['zip_code']);
    $address = trim($_POST['address']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if ($city_id && $zip && $address) {
        $stmt = $pdo->prepare("SELECT name FROM cities WHERE id = ? AND is_active = 1");
        $stmt->execute([$city_id]);
        $cityName = $stmt->fetchColumn();
        if (!$cityName) {
            $error = 'Выбранный город недоступен.';
        } else {
            if ($isDefault) {
                $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }
            $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, city_id, city, zip_code, address, is_default) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $city_id, $cityName, $zip, $address, $isDefault]);
            $message = 'Адрес успешно добавлен.';
        }
    } else {
        $error = 'Заполните все поля.';
    }
}

// Обработка редактирования адреса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_address'])) {
    $id = (int)$_POST['id'];
    $city_id = (int)$_POST['city_id'];
    $zip = trim($_POST['zip_code']);
    $address = trim($_POST['address']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if (!$stmt->fetch()) {
        $error = 'Доступ запрещён.';
    } elseif ($city_id && $zip && $address) {
        $stmt = $pdo->prepare("SELECT name FROM cities WHERE id = ? AND is_active = 1");
        $stmt->execute([$city_id]);
        $cityName = $stmt->fetchColumn();
        if (!$cityName) {
            $error = 'Выбранный город недоступен.';
        } else {
            if ($isDefault) {
                $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }
            $stmt = $pdo->prepare("UPDATE user_addresses SET city_id=?, city=?, zip_code=?, address=?, is_default=? WHERE id=? AND user_id=?");
            $stmt->execute([$city_id, $cityName, $zip, $address, $isDefault, $id, $userId]);
            $message = 'Адрес обновлён.';
        }
    } else {
        $error = 'Заполните все поля.';
    }
}

// Обработка удаления адреса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $message = 'Адрес удалён.';
}

// Обработка установки адреса по умолчанию
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?")->execute([$id]);
        $message = 'Адрес по умолчанию изменён.';
    } else {
        $error = 'Доступ запрещён.';
    }
}

// Получение списка адресов
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои адреса | Fashion Future</title>
    <link rel="stylesheet" href="../css/account.css">
    <style>
        .suggestions-box {
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .suggestion-item:hover {
            background-color: #f5f5f5;
        }
        .form-group {
            position: relative;
        }
    </style>
</head>
<body>
<div class="account-container">
    <a href="personal_account.php" class="back-link">← Назад в личный кабинет</a>
    <h1>Мои адреса доставки</h1>

    <?php if ($message): ?><div class="message-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Форма добавления нового адреса -->
    <div class="address-form">
        <h3>Добавить новый адрес</h3>
        <form method="post" id="addAddressForm">
            <div class="form-group">
                <label>Город *</label>
                <input type="text" id="cityInput" placeholder="Введите город..." autocomplete="off" required>
                <input type="hidden" name="city_id" id="cityId" required>
                <div id="citySuggestions" class="suggestions-box"></div>
            </div>
            <div class="form-group"><label>Индекс *</label><input type="text" name="zip_code" required></div>
            <div class="form-group"><label>Улица, дом, квартира *</label><textarea name="address" rows="2" required></textarea></div>
            <button type="submit" name="add_address" class="btn-add">Добавить адрес</button>
        </form>
    </div>

    <!-- Список существующих адресов -->
    <?php if (count($addresses) > 0): ?>
        <div class="addresses-list">
            <h3>Сохранённые адреса</h3>
            <?php foreach ($addresses as $addr): ?>
                <div class="address-card" data-id="<?= $addr['id'] ?>" data-city-id="<?= $addr['city_id'] ?>" data-city="<?= htmlspecialchars($addr['city']) ?>" data-zip="<?= htmlspecialchars($addr['zip_code']) ?>" data-address="<?= htmlspecialchars($addr['address']) ?>">
                    <div class="address-info">
                        <strong><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['zip_code']) ?></strong>
                        <p><?= nl2br(htmlspecialchars($addr['address'])) ?></p>
                        <?php if ($addr['is_default']): ?><span class="default-badge">По умолчанию</span><?php endif; ?>
                    </div>
                    <div class="address-actions">
                        <button class="edit-address-btn">✎ Редактировать</button>
                        <?php if (!$addr['is_default']): ?>
                            <form method="post" style="display:inline;"><input type="hidden" name="id" value="<?= $addr['id'] ?>"><button type="submit" name="set_default" class="default-btn">★ Сделать основным</button></form>
                        <?php endif; ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Удалить адрес?');"><input type="hidden" name="id" value="<?= $addr['id'] ?>"><button type="submit" name="delete_address" class="delete-btn">🗑 Удалить</button></form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#666;">У вас пока нет сохранённых адресов.</p>
    <?php endif; ?>
</div>

<!-- Модальное окно редактирования адреса -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Редактировать адрес</h3>
        <form method="post" id="editForm">
            <input type="hidden" name="id" id="editId">
            <div class="form-group">
                <label>Город *</label>
                <input type="text" id="editCityInput" placeholder="Начните вводить город..." autocomplete="off" required>
                <input type="hidden" name="city_id" id="editCityIdHidden">
                <div id="editCitySuggestions" class="suggestions-box"></div>
            </div>
            <div class="form-group"><label>Индекс *</label><input type="text" name="zip_code" id="editZip" required></div>
            <div class="form-group"><label>Улица, дом, квартира *</label><textarea name="address" id="editAddress" rows="2" required></textarea></div>
            <div class="form-group"><label><input type="checkbox" name="is_default" value="1" id="editDefault"> Сделать адресом по умолчанию</label></div>
            <button type="submit" name="edit_address">Сохранить изменения</button>
        </form>
    </div>
</div>

<script>
// Функция для создания поиска по городам
function initCitySearch(inputElement, hiddenElement, suggestionsElement) {
    let timeoutId = null;
    inputElement.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            suggestionsElement.innerHTML = '';
            suggestionsElement.style.display = 'none';
            hiddenElement.value = '';
            return;
        }
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            fetch(`../ajax_search_cities.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        suggestionsElement.innerHTML = '<div class="suggestion-item no-results">Ничего не найдено</div>';
                        suggestionsElement.style.display = 'block';
                        return;
                    }
                    let html = '';
                    data.forEach(city => {
                        html += `<div class="suggestion-item" data-id="${city.id}" data-name="${city.name}">${city.name} ${city.region ? `(${city.region})` : ''}</div>`;
                    });
                    suggestionsElement.innerHTML = html;
                    suggestionsElement.style.display = 'block';
                });
        }, 200);
    });

    suggestionsElement.addEventListener('click', (e) => {
        const item = e.target.closest('.suggestion-item');
        if (item && item.dataset.id) {
            inputElement.value = item.dataset.name;
            hiddenElement.value = item.dataset.id;
            suggestionsElement.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!inputElement.contains(e.target) && !suggestionsElement.contains(e.target)) {
            suggestionsElement.style.display = 'none';
        }
    });
}

// Инициализация для формы добавления
const addCityInput = document.getElementById('cityInput');
const addCityHidden = document.getElementById('cityId');
const addSuggestions = document.getElementById('citySuggestions');
if (addCityInput) initCitySearch(addCityInput, addCityHidden, addSuggestions);

// Для модального окна редактирования
const editModal = document.getElementById('editModal');
const closeModal = document.querySelector('.close-modal');
const editBtns = document.querySelectorAll('.edit-address-btn');
const editId = document.getElementById('editId');
const editCityInput = document.getElementById('editCityInput');
const editCityHidden = document.getElementById('editCityIdHidden');
const editZip = document.getElementById('editZip');
const editAddress = document.getElementById('editAddress');
const editDefault = document.getElementById('editDefault');
const editSuggestions = document.getElementById('editCitySuggestions');

if (editCityInput) initCitySearch(editCityInput, editCityHidden, editSuggestions);

editBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        const card = btn.closest('.address-card');
        editId.value = card.dataset.id;
        const cityName = card.dataset.city;
        const cityId = card.dataset.cityId;
        editCityInput.value = cityName;
        editCityHidden.value = cityId;
        editZip.value = card.dataset.zip;
        editAddress.value = card.dataset.address;
        editDefault.checked = false;
        editModal.style.display = 'flex';
    });
});
closeModal.addEventListener('click', () => editModal.style.display = 'none');
window.addEventListener('click', (e) => { if (e.target === editModal) editModal.style.display = 'none'; });
</script>
</body>
</html>