console.log('korzina.js loaded');

document.addEventListener('DOMContentLoaded', function() {
  // ===== ГЛОБАЛЬНЫЕ ФУНКЦИИ =====
  window.showToast = function(message, type = 'info') {
    let toast = document.querySelector('.toast-notification');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'toast-notification';
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.remove('success', 'error', 'info');
    toast.classList.add(type);
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  };

  // ===== ВЫБОР РАЗМЕРА НА СТРАНИЦЕ ТОВАРА =====
  const sizeBadges = document.querySelectorAll('.size-badge');
  sizeBadges.forEach(badge => {
    badge.addEventListener('click', function() {
      sizeBadges.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // ===== УЛУЧШЕННОЕ БУРГЕР-МЕНЮ =====
  const burger = document.getElementById('burgerMenu');
  const mobileNav = document.getElementById('mobileNav');
  if (burger && mobileNav) {
    document.addEventListener('click', function(e) {
      if (mobileNav.classList.contains('active') && !burger.contains(e.target) && !mobileNav.contains(e.target)) {
        mobileNav.classList.remove('active');
        burger.classList.remove('active');
      }
    });
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768 && mobileNav.classList.contains('active')) {
        mobileNav.classList.remove('active');
        burger.classList.remove('active');
      }
    });
    burger.addEventListener('click', function(e) {
      e.stopPropagation();
      this.classList.toggle('active');
      mobileNav.classList.toggle('active');
    });
  }

  // ===== КОРЗИНА =====
  let cartHandlerUrl = 'cart_orders/cart_handler.php';
  const cartIcon = document.getElementById('cartIconBtn');
  const cartOverlay = document.getElementById('cartOverlay');
  const cartPanel = document.getElementById('cartPanel');
  const closeCart = document.getElementById('closeCart');
  const cartItemsDiv = document.getElementById('cartItems');
  const cartTotalSpan = document.getElementById('cartTotal');
  const checkoutBtn = document.getElementById('checkoutBtn');

  if (!cartIcon || !cartOverlay || !cartPanel) return;

  let cart = [];

  function escapeHtml(str) {
    if (str === undefined || str === null) return '';
    str = String(str);
    return str.replace(/[&<>]/g, function(m) {
      if (m === '&') return '&amp;';
      if (m === '<') return '&lt;';
      if (m === '>') return '&gt;';
      return m;
    });
  }

  async function loadCart() {
    try {
      const response = await fetch(cartHandlerUrl + '?action=get');
      const data = await response.json();
      if (data.error) {
        console.error('Ошибка загрузки корзины:', data.error);
        return;
      }
      cart = data.items || [];
      renderCart();
      if (cartTotalSpan) {
        cartTotalSpan.textContent = (data.total || 0).toLocaleString() + ' ₽';
      }
      const cartCountSpan = document.getElementById('cart-count');
      if (cartCountSpan) {
        const totalQty = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCountSpan.textContent = totalQty;
        cartCountSpan.style.display = totalQty > 0 ? 'flex' : 'none';
      }
    } catch (err) {
      console.error('Ошибка загрузки корзины:', err);
    }
  }

  async function addToCart(productId, size, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('id', productId);
    formData.append('size', size);
    formData.append('quantity', quantity);
    try {
      const response = await fetch(cartHandlerUrl, { method: 'POST', body: formData });
      const result = await response.json();
      if (result.error) {
        showToast(result.error, 'error');
        return false;
      }
      await loadCart();
      showToast('Товар добавлен в корзину', 'success');
      return true;
    } catch (err) {
      console.error(err);
      showToast('Ошибка добавления товара', 'error');
      return false;
    }
  }

  async function updateQuantity(productId, size, delta) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', productId);
    formData.append('size', size);
    formData.append('delta', delta);
    try {
      const response = await fetch(cartHandlerUrl, { method: 'POST', body: formData });
      const result = await response.json();
      if (result.error) {
        showToast(result.error, 'error');
        return;
      }
      await loadCart();
    } catch (err) {
      console.error(err);
      showToast('Ошибка обновления', 'error');
    }
  }

  async function removeItem(productId, size) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('id', productId);
    formData.append('size', size);
    try {
      const response = await fetch(cartHandlerUrl, { method: 'POST', body: formData });
      const result = await response.json();
      if (result.error) {
        showToast(result.error, 'error');
        return;
      }
      await loadCart();
      showToast('Товар удалён из корзины', 'success');
    } catch (err) {
      console.error(err);
      showToast('Ошибка удаления', 'error');
    }
  }

  function renderCart() {
    if (!cartItemsDiv || !cartTotalSpan) return;
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p class="empty-cart">Корзина пуста</p>';
        cartTotalSpan.textContent = '0 ₽';
        // Скрыть прогресс-бар при пустой корзине
        const fsContainer = document.getElementById('cartFreeShipping');
        if (fsContainer) fsContainer.style.display = 'none';
        return;
    }
    let html = '';
    let totalSum = 0;
    cart.forEach(item => {
        totalSum += item.price * item.quantity;
        html += `
            <div class="cart-item" data-id="${item.id}" data-size="${item.size}">
                <img src="${item.image}" alt="${escapeHtml(item.name)}" class="cart-item-img" onerror="this.src='../img/placeholder.jpg'">
                <div class="cart-item-info">
                    <div class="cart-item-title">${escapeHtml(item.name)}</div>
                    <div class="cart-item-size">Размер: ${escapeHtml(item.size)}</div>
                    <div class="cart-item-price">${item.price.toLocaleString()} ₽</div>
                    <div class="cart-item-actions">
                        <button class="decrease-qty">−</button>
                        <span>${item.quantity}</span>
                        <button class="increase-qty">+</button>
                        <span class="remove-item" title="Удалить">Удалить</span>
                    </div>
                </div>
            </div>
        `;
    });
    cartItemsDiv.innerHTML = html;
    cartTotalSpan.textContent = `${totalSum.toLocaleString()} ₽`;

    // === ПРОГРЕСС-БАР БЕСПЛАТНОЙ ДОСТАВКИ ===
    const fsContainer = document.getElementById('cartFreeShipping');
    if (fsContainer) fsContainer.style.display = 'block';
    const fsMessageDiv = document.getElementById('fsMessage');
    const progressFill = document.getElementById('progressBarFill');
    if (fsMessageDiv && progressFill) {
        if (totalSum >= FREE_SHIPPING_THRESHOLD) {
            fsMessageDiv.innerHTML = '🎉 Бесплатная доставка активирована! 🎉';
            fsMessageDiv.className = 'fs-message success';
            progressFill.style.width = '100%';
            progressFill.style.backgroundColor = '#2e7d32';
        } else {
            const needed = FREE_SHIPPING_THRESHOLD - totalSum;
            const percent = (totalSum / FREE_SHIPPING_THRESHOLD) * 100;
            fsMessageDiv.innerHTML = `Добавьте ещё ${needed.toLocaleString()} ₽ для бесплатной доставки`;
            fsMessageDiv.className = 'fs-message';
            progressFill.style.width = percent + '%';
            progressFill.style.backgroundColor = '#000';
        }
    }
    // ====================================

    document.querySelectorAll('.decrease-qty').forEach(btn => {
        btn.removeEventListener('click', handleDecrease);
        btn.addEventListener('click', handleDecrease);
    });
    document.querySelectorAll('.increase-qty').forEach(btn => {
        btn.removeEventListener('click', handleIncrease);
        btn.addEventListener('click', handleIncrease);
    });
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.removeEventListener('click', handleRemove);
        btn.addEventListener('click', handleRemove);
    });
}

  function handleDecrease(e) {
    const cartItemDiv = e.target.closest('.cart-item');
    if (cartItemDiv) {
      const id = cartItemDiv.dataset.id;
      const size = cartItemDiv.dataset.size;
      updateQuantity(id, size, -1);
    }
  }
  function handleIncrease(e) {
    const cartItemDiv = e.target.closest('.cart-item');
    if (cartItemDiv) {
      const id = cartItemDiv.dataset.id;
      const size = cartItemDiv.dataset.size;
      updateQuantity(id, size, 1);
    }
  }
  function handleRemove(e) {
    const cartItemDiv = e.target.closest('.cart-item');
    if (cartItemDiv) {
      const id = cartItemDiv.dataset.id;
      const size = cartItemDiv.dataset.size;
      removeItem(id, size);
    }
  }

  function openCart() {
    cartOverlay.classList.add('active');
    cartPanel.classList.add('active');
    document.body.style.overflow = 'hidden';
    loadCart();
  }
  function closeCartPanel() {
    cartOverlay.classList.remove('active');
    cartPanel.classList.remove('active');
    document.body.style.overflow = '';
  }

  cartIcon.addEventListener('click', openCart);
  if (closeCart) closeCart.addEventListener('click', closeCartPanel);
  cartOverlay.addEventListener('click', closeCartPanel);

  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
      if (cart.length === 0) {
        showToast('Корзина пуста', 'error');
        return;
      }
      window.location.href = 'user/checkout.php';
    });
  }

  // ===== ДОБАВЛЕНИЕ В КОРЗИНУ СО СТРАНИЦЫ ТОВАРА =====
  document.body.addEventListener('click', async (e) => {
    const addBtn = e.target.closest('.add-to-cart, .add-to-cart-btn');
    if (!addBtn) return;
    e.preventDefault();

    const productContainer = addBtn.closest('.description-section');
    if (!productContainer) {
      console.warn('Не найден контейнер .description-section');
      return;
    }
    const productId = productContainer.dataset.id;
    if (!productId) return;

    let selectedSize = null;
    const activeSize = productContainer.querySelector('.size-badge.active');
    if (activeSize) {
      selectedSize = activeSize.innerText.trim();
    } else {
      const anySize = productContainer.querySelector('.size-badge');
      if (anySize) {
        showToast('Пожалуйста, выберите размер', 'error');
        return;
      }
      selectedSize = 'One size';
    }
    const success = await addToCart(productId, selectedSize, 1);
    if (success) {
      openCart();
    }
  });

  loadCart();
});

// ===== ВАЛИДАЦИЯ ФОРМ (ОФОРМЛЕНИЕ ЗАКАЗА) =====
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('orderForm');
  if (form) {
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const addressInput = document.getElementById('address');

    function validatePhone() {
      const phoneDigits = phoneInput.value.replace(/\D/g, '');
      if (phoneDigits.length !== 11) {
        phoneInput.classList.add('is-invalid');
        phoneInput.classList.remove('is-valid');
        return false;
      } else {
        phoneInput.classList.remove('is-invalid');
        phoneInput.classList.add('is-valid');
        return true;
      }
    }
    function validateEmail() {
      const email = emailInput.value.trim();
      const re = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
      if (!re.test(email)) {
        emailInput.classList.add('is-invalid');
        emailInput.classList.remove('is-valid');
        return false;
      } else {
        emailInput.classList.remove('is-invalid');
        emailInput.classList.add('is-valid');
        return true;
      }
    }
    function validateAddress() {
      if (addressInput.value.trim().length < 5) {
        addressInput.classList.add('is-invalid');
        addressInput.classList.remove('is-valid');
        return false;
      } else {
        addressInput.classList.remove('is-invalid');
        addressInput.classList.add('is-valid');
        return true;
      }
    }
    if (phoneInput) phoneInput.addEventListener('input', validatePhone);
    if (emailInput) emailInput.addEventListener('input', validateEmail);
    if (addressInput) addressInput.addEventListener('input', validateAddress);

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const isPhoneValid = phoneInput ? validatePhone() : true;
      const isEmailValid = emailInput ? validateEmail() : true;
      const isAddressValid = addressInput ? validateAddress() : true;
      if (!isPhoneValid || !isEmailValid || !isAddressValid) {
        if (window.showToast) window.showToast('Заполните все поля корректно', 'error');
        return;
      }
      const address = addressInput ? addressInput.value.trim() : '';
      const phone = phoneInput ? phoneInput.value.trim() : '';
      const email = emailInput ? emailInput.value.trim() : '';
      const comment = document.getElementById('comment') ? document.getElementById('comment').value.trim() : '';

      let cart = [];
      try {
        const resp = await fetch('../cart_orders/cart_handler.php?action=get');
        const data = await resp.json();
        if (data.items && data.items.length) {
          cart = data.items;
        } else {
          if (window.showToast) window.showToast('Корзина пуста', 'error');
          return;
        }
      } catch (err) {
        if (window.showToast) window.showToast('Ошибка получения корзины', 'error');
        return;
      }
      if (cart.length === 0) {
        if (window.showToast) window.showToast('Корзина пуста', 'error');
        return;
      }
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
          if (window.showToast) window.showToast('Заказ оформлен!', 'success');
          window.location.href = '../order_success.php?order=' + encodeURIComponent(data.order_number);
        } else {
          if (window.showToast) window.showToast('Ошибка: ' + data.error, 'error');
        }
      } catch (err) {
        if (window.showToast) window.showToast('Ошибка соединения', 'error');
      }
    });
  }
});