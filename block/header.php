<?php
require_once dirname(__DIR__) . '/config.php';

// Определяем текущий город (из сессии или по умолчанию)
if (!isset($_SESSION['user_city'])) {
    // Попробуем определить город по IP (для примера берём Москву)
    // Можно расширить через ip-api.com
    $defaultCity = ['id' => 1, 'name' => 'Москва'];
    $_SESSION['user_city'] = $defaultCity;
}
$userCity = $_SESSION['user_city'];
?>
<header>
    <div class="header-content">
        <!-- Логотип -->
        <a href="index.php" class="logo-link">
            <img src="img/logo.png" alt="Fashion Future" class="header-logo">
        </a>
    
        <!-- Десктопная навигация -->
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="index.php">Главная</a></li>
                <li><a href="category.php">Каталог</a></li>
                <li><a href="about-us.php">О нас</a></li>
                <li><a href="delivery.php">Доставка</a></li>
                <li><a href="faq.php">FAQ</a></li>
            </ul>
        </nav>

        <!-- Блок иконок пользователя и город -->
        <div class="user-actions">
            <!-- Виджет выбора города -->
            <div class="city-selector" id="citySelector">
                <div class="city-display">
                    <span class="city-name"><?= htmlspecialchars($userCity['name']) ?></span>
                    <button class="city-change-btn" id="changeCityBtn">▼</button>
                </div>
                <div class="city-popup" id="cityPopup">
                    <div class="popup-header">
                        <span>Ваш город <strong id="currentCityName"><?= htmlspecialchars($userCity['name']) ?></strong>?</span>
                        <button class="close-popup">&times;</button>
                    </div>
                    <div class="popup-buttons">
                        <button id="confirmCityBtn" class="btn-confirm">Всё верно</button>
                        <button id="chooseAnotherBtn" class="btn-another">Другой город</button>
                    </div>
                    <div class="search-city-block" id="searchCityBlock" style="display: none;">
                        <input type="text" id="citySearchInput" placeholder="Начните вводить город...">
                        <div id="citySearchResults" class="search-results"></div>
                        <div id="searchSpinner" class="search-spinner" style="display: none;">⏳</div>
                    </div>
                </div>
            </div>

            <!-- Иконка профиля -->
            <a href="<?= BASE_URL ?>user/personal_account.php" class="icon-btn profile-trigger" id="profileBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/>
                </svg>
                <span class="tooltip">Личный кабинет</span>
            </a>
            <!-- Кнопка корзины -->
            <button class="icon-btn cart-toggle" id="cartIconBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 20C9 21.1 8.1 22 7 22 5.9 22 5 21.1 5 20 5 18.9 5.9 18 7 18 8.1 18 9 18.9 9 20zM20 20C20 21.1 19.1 22 18 22 16.9 22 16 21.1 16 20 16 18.9 16.9 18 18 18 19.1 18 20 18.9 20 20zM8.2 10.8L7 7h13.5c.4 0 .7.3.7.6l-1.5 6.8c-.1.4-.5.6-.9.6H8.9c-.4 0-.7-.2-.8-.5L8.2 10.8z"/>
                    <circle cx="7" cy="20" r="1.5" fill="currentColor"/>
                    <circle cx="18" cy="20" r="1.5" fill="currentColor"/>
                </svg>
                <span class="tooltip">Корзина</span>
            </button>
        </div>

        <!-- Бургер-меню -->
        <div class="burger-menu" id="burgerMenu">
            <span></span><span></span><span></span>
        </div>
    </div>

    <!-- Мобильное меню -->
    <div class="mobile-nav" id="mobileNav">
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="category.php">Каталог</a></li>
            <li><a href="about-us.php">О бренде</a></li>
            <li><a href="delivery.php">Доставка</a></li>
            <li><a href="returns.php">Возврат</a></li>
            <li><a href="faq.php">FAQ</a></li>
        </ul>
    </div>
</header>

<script>
    const isUserLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    const BASE_URL = '<?= BASE_URL ?>';
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Бургер-меню
    const burger = document.getElementById('burgerMenu');
    const mobileNav = document.getElementById('mobileNav');
    if (burger && mobileNav) {
        burger.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileNav.classList.toggle('active');
        });
    }

    // Анимация счётчика корзины
    function animateCartCount() {
        const counter = document.getElementById('cart-count');
        if (counter) {
            counter.classList.add('cart-count-bump');
            setTimeout(() => counter.classList.remove('cart-count-bump'), 300);
        }
    }
    window.animateCartCount = animateCartCount;
});
</script>

<script>
async function loadNotifCount() {
    try {
        const resp = await fetch(BASE_URL + 'user/get_notifications_count.php');
        const data = await resp.json();
        const countSpan = document.getElementById('notifications-count');
        if (countSpan && data.count > 0) {
            countSpan.textContent = data.count;
            countSpan.style.display = 'flex';
            countSpan.classList.add('notif-count-bump');
            setTimeout(() => countSpan.classList.remove('notif-count-bump'), 300);
        } else if (countSpan) {
            countSpan.style.display = 'none';
        }
    } catch(e) {}
}
loadNotifCount();
</script>

<script>
    const FREE_SHIPPING_THRESHOLD = <?= defined('FREE_SHIPPING_LIMIT') ? FREE_SHIPPING_LIMIT : 5000 ?>;
</script>

<script src="<?= BASE_URL ?>js/korzina.js"></script>

<script>
// Городской виджет с анимациями
const cityPopup = document.getElementById('cityPopup');
const changeCityBtn = document.getElementById('changeCityBtn');
const closePopupBtn = document.querySelector('.close-popup');
const confirmCityBtn = document.getElementById('confirmCityBtn');
const chooseAnotherBtn = document.getElementById('chooseAnotherBtn');
const searchBlock = document.getElementById('searchCityBlock');
const citySearchInput = document.getElementById('citySearchInput');
const searchResults = document.getElementById('citySearchResults');
const spinner = document.getElementById('searchSpinner');

function showPopup() {
    cityPopup.classList.add('show');
    searchBlock.style.display = 'none';
    citySearchInput.value = '';
    searchResults.innerHTML = '';
    if (spinner) spinner.style.display = 'none';
}
function hidePopup() {
    cityPopup.classList.remove('show');
}

if (changeCityBtn) changeCityBtn.addEventListener('click', showPopup);
if (closePopupBtn) closePopupBtn.addEventListener('click', hidePopup);
if (confirmCityBtn) confirmCityBtn.addEventListener('click', hidePopup);

if (chooseAnotherBtn) {
    chooseAnotherBtn.addEventListener('click', () => {
        searchBlock.style.display = 'block';
        citySearchInput.focus();
    });
}

// Поиск городов с дебаунсом и спиннером
let searchTimeout;
if (citySearchInput) {
    citySearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        if (query.length < 2) {
            searchResults.innerHTML = '';
            if (spinner) spinner.style.display = 'none';
            return;
        }
        if (spinner) spinner.style.display = 'block';
        searchTimeout = setTimeout(() => {
            fetch(BASE_URL + 'ajax_search_cities.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    if (spinner) spinner.style.display = 'none';
                    searchResults.innerHTML = '';
                    data.forEach(city => {
                        const div = document.createElement('div');
                        div.textContent = `${city.name} ${city.region ? `(${city.region})` : ''}`;
                        div.dataset.id = city.id;
                        div.dataset.name = city.name;
                        div.addEventListener('click', () => selectCity(city.id, city.name));
                        searchResults.appendChild(div);
                    });
                })
                .catch(err => {
                    if (spinner) spinner.style.display = 'none';
                    console.error('Ошибка поиска:', err);
                });
        }, 300);
    });
}

function selectCity(id, name) {
    fetch(BASE_URL + 'ajax_set_city.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `city_id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const cityNameSpan = document.querySelector('.city-name');
            const currentCitySpan = document.getElementById('currentCityName');
            if (cityNameSpan) cityNameSpan.textContent = name;
            if (currentCitySpan) currentCitySpan.textContent = name;
            hidePopup();
            location.reload(); // перезагрузка для обновления стоимости доставки
        } else {
            alert('Ошибка при смене города');
        }
    })
    .catch(err => console.error('Ошибка:', err));
}
</script>
