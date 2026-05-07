<?php
require_once dirname(__DIR__) . '/config.php';
?>
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Панель корзины -->
<div class="cart-panel" id="cartPanel">
    <div class="cart-header">
        <h2>Корзина</h2>
        <button class="close-cart" id="closeCart">&times;</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <!-- НОВЫЙ БЛОК ПРОГРЕСС-БАРА -->
    <div class="cart-free-shipping" id="cartFreeShipping">
        <div class="fs-message" id="fsMessage"></div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBarFill"></div>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Итого:</span>
            <span id="cartTotal">0 ₽</span>
        </div>
        <button class="checkout-btn" id="checkoutBtn">Оформить заказ</button>
    </div>
</div>