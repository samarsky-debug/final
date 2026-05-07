<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>О бренде | Fashion Future</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php require_once 'block/header.php'; ?>

<main>
    <!-- HERO-СЕКЦИЯ (используем общий класс .hero, но с фоновым изображением) -->
    <section class="hero hero-about" style="background-image: url('img/about_us.avif'); background-size: cover; background-position: center; background-blend-mode: overlay; background-color: rgba(0,0,0,0.3);">
        <div class="hero-text">
            <h2>Мы создаём не просто одежду</h2>
            <p>Мы формируем будущее моды. Смелые формы, инновационные материалы и бескомпромиссное качество.</p>
            <a href="category.php" class="btn">Исследовать коллекцию</a>
        </div>
        <div class="hero-image" style="display: none;"></div> <!-- скрываем вторую колонку, так как фон уже есть -->
    </section>

    <!-- Философия и ценности -->
    <div class="page-header">
        <h1>Философия бренда</h1>
    </div>
    <div class="content-container">
        <p style="font-size: 1.2rem; text-align: center; max-width: 800px; margin: 0 auto 2rem auto;">Fashion Future — это синтез архитектурного минимализма и авангардной эстетики. Мы верим, что одежда — это вторая кожа, которая отражает внутренний мир и формирует будущее.</p>
        
        <div class="products values-grid">
            <div class="product value-card">
                <div class="value-icon">♻️</div>
                <h3>Этичное производство</h3>
                <p>Мы используем только переработанные и органические материалы, сокращая углеродный след.</p>
            </div>
            <div class="product value-card">
                <div class="value-icon">🎨</div>
                <h3>Инновационный дизайн</h3>
                <p>Каждая коллекция — эксперимент с формами, текстурами и технологиями.</p>
            </div>
            <div class="product value-card">
                <div class="value-icon">🤝</div>
                <h3>Честность и качество</h3>
                <p>Прозрачность на всех этапах: от эскиза до доставки. Гарантия на каждое изделие.</p>
            </div>
        </div>
    </div>

    <!-- История бренда (таймлайн) -->
    <div class="page-header">
        <h1>История движения</h1>
    </div>
    <div class="content-container">
        <p style="text-align: center; margin-bottom: 2rem;">Как небольшое творческое объединение выросло в международный бренд.</p>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2018</div>
                    <div class="timeline-text">Основание в Кемерово. Первая капсульная коллекция «Zero» распродана за 2 дня.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2020</div>
                    <div class="timeline-text">Запуск онлайн-платформы. Нас заметили в Москве и Санкт-Петербурге.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2022</div>
                    <div class="timeline-text">Участие в Mercedes-Benz Fashion Week Russia. Коллекция «Digital Nature» получила признание критиков.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-year">2025</div>
                    <div class="timeline-text">Открытие первого флагманского магазина в Кемерово и запуск программы устойчивого развития.</div>
                </div>
                <div class="timeline-dot"></div>
            </div>
        </div>
    </div>

    <!-- Вдохновляющая цитата -->
    <section class="quote-block">
        <blockquote>
            «Мода — это не то, что вы носите. Мода — это то, как вы живёте. Мы создаём одежду для тех, кто смотрит вперёд».
        </blockquote>
    </section>
</main>

<?php require_once 'block/footer.php'; ?>

<!-- Скрипт для анимации при скролле (использует общие классы) -->
<script>
    const fadeElements = document.querySelectorAll('.value-card, .timeline-item');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2, rootMargin: '0px 0px -50px 0px' });
    fadeElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
</script>
</body>
</html>