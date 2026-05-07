(function() {
    // Маска телефона
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length === 0) {
                this.value = '';
                return;
            }
            if (value.length > 11) value = value.slice(0, 11);
            let formatted = '+7';
            if (value.length > 1) formatted += ' ' + value.slice(1, 4);
            if (value.length >= 5) formatted += ' ' + value.slice(4, 7);
            if (value.length >= 8) formatted += ' ' + value.slice(7, 9);
            if (value.length >= 10) formatted += ' ' + value.slice(9, 11);
            this.value = formatted;
        });
    }

    const form = document.getElementById('orderForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const address = document.getElementById('address').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('email').value.trim();
            const comment = document.getElementById('comment').value.trim();

            if (!address || !phone || !email) {
                alert('Заполните все обязательные поля');
                return;
            }

            // Валидация телефона (11 цифр)
            const phoneDigits = phone.replace(/\D/g, '');
            if (phoneDigits.length !== 11) {
                alert('Введите корректный номер телефона (11 цифр). Пример: +7 999 123 45 67');
                phoneInput.focus();
                return;
            }

            // Получаем актуальную корзину с сервера
            let cart = [];
            try {
                const resp = await fetch('../cart_orders/cart_handler.php?action=get');
                const data = await resp.json();
                if (data.items && data.items.length) {
                    cart = data.items;
                } else {
                    alert('Корзина пуста');
                    return;
                }
            } catch (err) {
                alert('Ошибка получения корзины: ' + err.message);
                return;
            }

            if (cart.length === 0) {
                alert('Корзина пуста');
                return;
            }

            // Отправка заказа
            try {
                const response = await fetch('../cart_orders/process_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cart: cart.map(item => ({
                            id: item.id,
                            quantity: item.quantity,
                            price: item.price
                        })),
                        address: address,
                        phone: phone,
                        email: email,
                        comment: comment
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Заказ успешно оформлен! Номер: ' + data.order_number);
                    window.location.href = '../order_success.php?order=' + encodeURIComponent(data.order_number);
                } else {
                    alert('Ошибка: ' + data.error);
                }
            } catch (err) {
                alert('Ошибка соединения: ' + err.message);
            }
        });
    }
})();