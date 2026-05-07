   // Переключение формы редактирования профиля
            const toggleProfileBtn = document.getElementById('toggleProfileBtn');
            const profileForm = document.getElementById('profileEditForm');
            if (toggleProfileBtn && profileForm) {
                toggleProfileBtn.addEventListener('click', function() {
                    if (profileForm.style.display === 'none') {
                        profileForm.style.display = 'block';
                        toggleProfileBtn.textContent = '✎ Скрыть форму';
                    } else {
                        profileForm.style.display = 'none';
                        toggleProfileBtn.textContent = '✎ Редактировать профиль';
                    }
                });
            }

            // Переключение формы смены пароля
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const passwordForm = document.getElementById('passwordChangeForm');
            if (togglePasswordBtn && passwordForm) {
                togglePasswordBtn.addEventListener('click', function() {
                    if (passwordForm.style.display === 'none') {
                        passwordForm.style.display = 'block';
                        togglePasswordBtn.textContent = '🔑 Скрыть форму';
                    } else {
                        passwordForm.style.display = 'none';
                        togglePasswordBtn.textContent = '🔑 Сменить пароль';
                    }
                });
            }

            // Переключение состава заказа
            function toggleOrderItems(header) {
                if (event.target.closest('.action-button')) return;
                const itemsDiv = header.nextElementSibling;
                const icon = header.querySelector('.toggle-icon');
                if (itemsDiv.classList.contains('show')) {
                    itemsDiv.classList.remove('show');
                    icon.classList.remove('rotated');
                } else {
                    itemsDiv.classList.add('show');
                    icon.classList.add('rotated');
                }
            }

            // Удаление из избранного
            document.querySelectorAll('.wishlist-remove').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = this.dataset.id;
                    const card = this.closest('.wishlist-card');
                    try {
                        const formData = new FormData();
                        formData.append('action', 'remove');
                        formData.append('product_id', productId);
                        const resp = await fetch('../cart_orders/wishlist_handler.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await resp.json();
                        if (data.success) {
                            card.remove();
                            if (document.querySelectorAll('.wishlist-card').length === 0) {
                                document.querySelector('.wishlist-section').innerHTML = '<h3>❤️ Избранное</h3><p style="color:#666;">У вас пока нет избранных товаров. <a href="category.php">Перейти в каталог</a></p>';
                            }
                        } else {
                            alert(data.error || 'Ошибка при удалении');
                        }
                    } catch(e) {
                        console.error(e);
                        alert('Ошибка соединения');
                    }
                });
            });

            // Добавление в корзину из избранного
            document.querySelectorAll('.wishlist-add-to-cart').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = this.dataset.id;
                    try {
                        const formData = new FormData();
                        formData.append('action', 'add');
                        formData.append('id', productId);
                        formData.append('size', 'M');
                        formData.append('quantity', 1);
                        const resp = await fetch('../cart_orders/cart_handler.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await resp.json();
                        if (data.success) {
                            alert('Товар добавлен в корзину');
                        } else {
                            alert('Ошибка добавления');
                        }
                    } catch(e) {
                        console.error(e);
                        alert('Ошибка соединения');
                    }
                });
            });