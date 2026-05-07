document.addEventListener('DOMContentLoaded', async function() {
    const wishlistBtn = document.querySelector('.wishlist-btn');
    if (!wishlistBtn) return;   
    const productId = wishlistBtn.dataset.id;
    
    // Проверка статуса
    try {
        const checkResponse = await fetch(`cart_orders/wishlist_handler.php?action=check&product_id=${productId}`);
        const checkResult = await checkResponse.json();
        if (checkResult.in_wishlist) {
            wishlistBtn.classList.add('active');
            wishlistBtn.textContent = '✔️ В избранном';
        }
    } catch(e) { console.error(e); }
    
    wishlistBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        const isActive = this.classList.contains('active');
        const action = isActive ? 'remove' : 'add';
        const formData = new FormData();
        formData.append('action', action);
        formData.append('product_id', productId);
        
        try {
            const response = await fetch('cart_orders/wishlist_handler.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                if (action === 'add') {
                    this.classList.add('active');
                    this.textContent = '✔️ В избранном';
                } else {
                    this.classList.remove('active');
                    this.textContent = '❤️ В избранное';
                }
            } else if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                alert(result.error || 'Ошибка');
            }
        } catch(e) {
            console.error(e);
            alert('Ошибка соединения');
        }
    });
});