// Поиск городов
const cityInput = document.getElementById('cityInput');
const cityIdHidden = document.getElementById('cityId');
const suggestionsBox = document.getElementById('citySuggestions');
let selectedCityPrice = 0;

cityInput.addEventListener('input', function() {
    const query = this.value.trim();
    if (query.length < 2) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
        return;
    }
    fetch(`../ajax_search_cities.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                suggestionsBox.innerHTML = '<div class="suggestion-item no-results">Ничего не найдено</div>';
                suggestionsBox.style.display = 'block';
                return;
            }
            let html = '';
            data.forEach(city => {
                html += `<div class="suggestion-item" data-id="${city.id}" data-price="${city.delivery_price}">
                            ${city.name} ${city.region ? `(${city.region})` : ''} — доставка ${Number(city.delivery_price).toLocaleString()} ₽
                         </div>`;
            });
            suggestionsBox.innerHTML = html;
            suggestionsBox.style.display = 'block';
        });
});

suggestionsBox.addEventListener('click', (e) => {
    const item = e.target.closest('.suggestion-item');
    if (item && item.dataset.id) {
        cityInput.value = item.textContent.split(' —')[0];
        cityIdHidden.value = item.dataset.id;
        selectedCityPrice = parseFloat(item.dataset.price);
        document.getElementById('deliveryAmount').innerText = selectedCityPrice.toLocaleString() + ' ₽';
        document.getElementById('deliveryPrice').value = selectedCityPrice;
        updateTotalWithDelivery();
        suggestionsBox.style.display = 'none';
    }
});

document.addEventListener('click', (e) => {
    if (!cityInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.style.display = 'none';
    }
});

function updateTotalWithDelivery() {
    const final = subtotal - currentDiscount + selectedCityPrice;
    document.getElementById('finalTotal').innerText = final.toLocaleString() + ' ₽';
}
