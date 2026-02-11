<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gourmet Delivery - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-panel {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 400px;
            background: white;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        .cart-open .cart-panel {
            transform: translateX(0);
        }

        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
            margin: 20px 0;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #EEE;
            padding-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" class="brand" style="text-decoration: none;">
                <i class="fas fa-utensils"></i> Misosi Kiganjani
            </a>
            <nav>
                <a href="javascript:void(0)" class="nav-link active" onclick="switchTab('menu', this)"><i
                        class="fas fa-book-open"></i> Browse Menu</a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchTab('orders', this)"><i
                        class="fas fa-clock"></i>
                    My Orders</a>
                <a href="javascript:void(0)" class="nav-link" onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="flex-between mb-4">
                <h1>Delicious Menu</h1>
                <button class="btn btn-primary" onclick="toggleCart()">
                    <i class="fas fa-shopping-cart"></i> Cart <span id="cart-count" class="badge badge-pending"
                        style="background: white; color: var(--primary);">0</span>
                </button>
            </div>

            <div id="view-menu">
                <div id="menu-grid" class="menu-grid">
                    <!-- Loaded via JS -->
                </div>
            </div>

            <div id="view-orders" style="display:none;">
                <h2>Order History</h2>
                <div id="orders-list">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </main>
    </div>

    <!-- Cart Sidebar -->
    <div id="cart-overlay"
        style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.3); display:none; z-index:900;"
        onclick="toggleCart()"></div>
    <div class="cart-panel">
        <div class="flex-between">
            <h2>Your Cart</h2>
            <button class="btn btn-secondary" onclick="toggleCart()" style="padding: 5px 10px;"><i
                    class="fas fa-times"></i></button>
        </div>
        <div class="cart-items" id="cart-items">
            <p class="text-muted text-center mt-5">Your cart is empty.</p>
        </div>
        <div class="cart-footer">
            <div class="flex-between mb-3">
                <h3>Total</h3>
                <h3 id="cart-total">Tsh 0.00</h3>
            </div>
            <div class="form-group">
                <input type="text" id="delivery-address" class="form-control" placeholder="Delivery Address" required>
            </div>
            <button class="btn btn-primary btn-block" onclick="checkout()">Place Order</button>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        let cart = [];
        let products = [];

        // Init
        loadMenu();
        loadOrders();

        function switchTab(tab, element) {
            // Remove active class from all links
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

            // Add active class to clicked element or find by tab name
            if (element) {
                element.classList.add('active');
            } else {
                // Find link by onclick attribute content if element is not provided (programmatic call)
                const link = document.querySelector(`.nav-link[onclick*="'${tab}'"]`);
                if (link) link.classList.add('active');
            }

            if (tab === 'orders') {
                document.getElementById('view-menu').style.display = 'none';
                document.getElementById('view-orders').style.display = 'block';
                loadOrders();
            } else {
                document.getElementById('view-menu').style.display = 'block';
                document.getElementById('view-orders').style.display = 'none';
            }
        }

        async function loadMenu() {
            try {
                products = await apiCall('api/data.php?type=products');
                const grid = document.getElementById('menu-grid');
                grid.innerHTML = products.map(p => `
                    <div class="card food-card">
                        <img src="${p.image}" class="food-img" alt="${p.name}">
                        <div style="padding: 10px;">
                            <div class="flex-between">
                                <div class="food-title">${p.name}</div>
                                <div class="price">${formatCurrency(p.price)}</div>
                            </div>
                            <div class="food-desc">${p.description}</div>
                            <button class="btn btn-primary btn-block mt-3" onclick="addToCart(${p.id})">
                                <i class="fas fa-plus"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                `).join('');
            } catch (e) { }
        }

        function addToCart(id) {
            // Use loose comparison (==) to handle potential string/number mismatch
            const product = products.find(p => p.id == id);

            if (!product) {
                console.error('Product not found for ID:', id);
                return;
            }

            const existing = cart.find(item => item.id == id);

            if (existing) {
                existing.quantity++;
            } else {
                // Ensure price is a number
                const price = parseFloat(product.price);
                cart.push({ ...product, price: price, quantity: 1 });
            }
            updateCartUI();
            toggleCart(true);
        }

        function updateCartUI() {
            const container = document.getElementById('cart-items');
            const countBadge = document.getElementById('cart-count');
            const totalEl = document.getElementById('cart-total');

            countBadge.innerText = cart.reduce((acc, item) => acc + item.quantity, 0);

            if (cart.length === 0) {
                container.innerHTML = '<p class="text-muted text-center mt-5">Your cart is empty.</p>';
                totalEl.innerText = formatCurrency(0);
                return;
            }

            let total = 0;
            container.innerHTML = cart.map((item, index) => {
                total += item.price * item.quantity;
                return `
                    <div class="cart-item">
                        <div>
                            <strong>${item.name}</strong> x${item.quantity}<br>
                            <small class="text-muted">${formatCurrency(item.price)}</small>
                        </div>
                        <div>
                            ${formatCurrency(item.price * item.quantity)}
                            <i class="fas fa-trash text-danger" style="cursor:pointer; margin-left:10px;" onclick="removeFromCart(${index})"></i>
                        </div>
                    </div>
                `;
            }).join('');
            totalEl.innerText = formatCurrency(total);
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function toggleCart(forceOpen = false) {
            const body = document.body;
            const overlay = document.getElementById('cart-overlay');
            if (forceOpen || !body.classList.contains('cart-open')) {
                body.classList.add('cart-open');
                overlay.style.display = 'block';
            } else {
                body.classList.remove('cart-open');
                overlay.style.display = 'none';
            }
        }

        async function checkout() {
            if (cart.length === 0) return alert('Cart is empty!');
            const address = document.getElementById('delivery-address').value;
            if (!address) return alert('Please enter a delivery address');

            const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);

            try {
                await apiCall('api/data.php?type=orders', 'POST', {
                    items: cart,
                    total: total,
                    address: address
                });
                alert('Order placed successfully!');
                cart = [];
                updateCartUI();
                toggleCart();
                switchTab('orders');
                loadOrders();
            } catch (e) {
                alert('Order failed: ' + e.message);
                console.error(e);
            }
        }

        async function loadOrders() {
            const list = document.getElementById('orders-list');
            try {
                const orders = await apiCall('api/data.php?type=orders');
                list.innerHTML = orders.map(order => `
                    <div class="card mb-3">
                         <div class="flex-between">
                            <div>
                                <h4>Order #${order.id}</h4>
                                <span class="badge badge-${order.status}">${order.status.toUpperCase()}</span>
                                <p class="text-muted mt-2">${order.created_at}</p>
                            </div>
                            <div class="text-right">
                     <h3>${formatCurrency(order.total_price)}</h3>
                            </div>
                         </div>
                    </div>
                `).join('');
                if (orders.length === 0) list.innerHTML = '<p class="text-muted">No past orders.</p>';
            } catch (e) { }
        }
    </script>
</body>

</html>