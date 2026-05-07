// js/canvas.js — наручные часы с фотографией циферблата + плавные стрелки
(function() {
    const canvas = document.getElementById('analogWatch');
    if (!canvas) return;

    // Переменная авторизации (устанавливается в header.php)
    const isLoggedIn = typeof isUserLoggedIn !== 'undefined' ? isUserLoggedIn : false;
    const MOSCOW_OFFSET = 3 * 60 * 60 * 1000; // UTC+3

    // Путь к изображению циферблата (замените на свой)
    const watchFaceImageSrc = 'img/canvas.jpg';

    let watchFaceImage = new Image();
    let imageLoaded = false;
    let animationId = null;

    // Получение текущего момента времени (с учётом авторизации)
    function getCurrentDateTime() {
        if (isLoggedIn) return new Date();
        const now = new Date();
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        return new Date(utc + MOSCOW_OFFSET);
    }

    // Плавное получение углов с миллисекундами
    function getSmoothAngles() {
        const now = getCurrentDateTime();
        const hours = now.getHours() % 12;
        const minutes = now.getMinutes();
        const seconds = now.getSeconds();
        const milliseconds = now.getMilliseconds();

        // Дробные значения для плавности
        const hoursFraction = hours + minutes / 60 + seconds / 3600;
        const minutesFraction = minutes + seconds / 60 + milliseconds / 60000;
        const secondsFraction = seconds + milliseconds / 1000;

        // Углы в радианах (0° = 12 часов, поворот по часовой)
        const hourAngle = (hoursFraction * 360 / 12 - 90) * Math.PI / 180;
        const minuteAngle = (minutesFraction * 360 / 60 - 90) * Math.PI / 180;
        const secondAngle = (secondsFraction * 360 / 60 - 90) * Math.PI / 180;

        return { hourAngle, minuteAngle, secondAngle };
    }

    // Рисование одной стрелки
    function drawHand(ctx, x, y, length, angle, width, color, hasTail = true) {
        ctx.beginPath();
        ctx.moveTo(x, y);
        ctx.lineTo(x + length * Math.cos(angle), y + length * Math.sin(angle));
        ctx.lineWidth = width;
        ctx.strokeStyle = color;
        ctx.lineCap = 'round';
        ctx.stroke();
        
        if (hasTail) {
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x - (length * 0.12) * Math.cos(angle), y - (length * 0.12) * Math.sin(angle));
            ctx.stroke();
        }
    }

    // Основная отрисовка
    function drawWatch() {
        if (!imageLoaded) return;

        const ctx = canvas.getContext('2d');
        const size = canvas.width;
        const center = size / 2;
        const radius = size * 0.42; // радиус циферблата для расчёта длины стрелок

        // Очистка и рисование фото циферблата
        ctx.clearRect(0, 0, size, size);
        ctx.drawImage(watchFaceImage, 0, 0, size, size);
        ctx.shadowBlur = 0;

        // Получаем плавные углы
        const { hourAngle, minuteAngle, secondAngle } = getSmoothAngles();

        // Часовая стрелка (короткая, широкая)
        drawHand(ctx, center, center, radius * 0.35, hourAngle, 5, '#111', true);
        // Минутная стрелка
        drawHand(ctx, center, center, radius * 0.45, minuteAngle, 3, '#222', true);
        // Секундная стрелка (красная, без хвоста)
        drawHand(ctx, center, center, radius * 0.5, secondAngle, 1.5, '#c33', false);

        // Центральная заглушка (имитация крепления стрелок)
        ctx.beginPath();
        ctx.arc(center, center, 6.5, 0, 2 * Math.PI);
        ctx.fillStyle = '#111';
        ctx.fill();
        ctx.beginPath();
        ctx.arc(center, center, 3, 0, 2 * Math.PI);
        ctx.fillStyle = '#c33';
        ctx.fill();
    }

    // Адаптация размера canvas под контейнер
    function resizeCanvas() {
        const container = canvas.parentElement;
        const maxSize = Math.min(container.clientWidth, 280);
        canvas.width = maxSize;
        canvas.height = maxSize;
        if (imageLoaded) drawWatch();
    }

    // Анимационный цикл (плавное обновление ~60 fps)
    function startAnimation() {
        if (animationId) cancelAnimationFrame(animationId);
        
        function animate() {
            drawWatch();
            animationId = requestAnimationFrame(animate);
        }
        animate();
    }

    // Загрузка изображения циферблата
    watchFaceImage.onload = function() {
        imageLoaded = true;
        resizeCanvas();
        startAnimation(); // запускаем плавную анимацию
    };
    watchFaceImage.src = watchFaceImageSrc;

    // Следим за изменением размера окна
    window.addEventListener('resize', () => {
        resizeCanvas();
    });

    // Начальный вызов (если изображение уже загружено из кеша)
    if (watchFaceImage.complete) {
        imageLoaded = true;
        resizeCanvas();
        startAnimation();
    }
})();