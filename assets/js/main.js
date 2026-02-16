// assets/js/main.js
console.log('main.js: Loading...');

window.apiCall = async function (url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (data) options.body = JSON.stringify(data);

    try {
        const response = await fetch(url, options);
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON from server');
        }
        if (!response.ok) throw new Error(result.message || 'Request failed');
        return result;
    } catch (error) {
        alert('Error: ' + error.message);
        throw error;
    }
};

window.formatCurrency = function (amount) {
    const n = Number(amount) || 0;
    return 'Tsh ' + n.toLocaleString('en-US', { minimumFractionDigits: 2 });
};

window.cart_get = function () {
    try {
        const data = localStorage.getItem('food_cart');
        return data ? JSON.parse(data) : [];
    } catch (e) {
        return [];
    }
};

window.cart_save = function (cart) {
    localStorage.setItem('food_cart', JSON.stringify(cart));
    if (typeof updateGlobalCartCount === 'function') updateGlobalCartCount();
};

window.cart_add = function (product) {
    let cart = window.cart_get();
    const productId = Number(product.id);
    const existing = cart.find(item => Number(item.id) === productId);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            id: productId,
            name: product.name,
            price: parseFloat(product.price) || 0,
            image: product.image,
            quantity: 1
        });
    }
    window.cart_save(cart);
    return cart;
};

window.cart_clear = function () {
    localStorage.removeItem('food_cart');
    if (typeof updateGlobalCartCount === 'function') updateGlobalCartCount();
};

window.updateGlobalCartCount = function () {
    const cart = window.cart_get();
    const count = cart.reduce((acc, item) => acc + (item.quantity || 0), 0);
    document.querySelectorAll('#cart-count, .cart-count-badge').forEach(badge => {
        badge.innerText = count;
        badge.classList.remove('pulse');
        void badge.offsetWidth;
        badge.classList.add('pulse');
    });
};

window.logout = function () {
    window.apiCall('api/auth.php?action=logout', 'POST').finally(() => {
        window.location.href = 'index.php';
    });
};

console.log('main.js: Loaded successfully');
window.updateGlobalCartCount();
