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
    <link rel="stylesheet" href="assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-panel {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 400px;
            max-width: 90%;
            background: white;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 2000;
            padding: 20px;
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 0.3s ease-out;
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
                <a href="javascript:void(0)" class="nav-link" onclick="switchTab('orders', this)">
                    <i class="fas fa-clock"></i> My Orders 
                    <span id="orders-count-badge" class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.7rem; padding: 2px 8px; margin-left: auto; display: none;">0</span>
                </a>
                <a href="javascript:void(0)" class="nav-link" onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="flex-between mb-5" style="gap: 15px; flex-wrap: wrap; text-align: center;">
                <h1 id="view-title" style="margin: 0; flex: 1; min-width: 200px;">Delicious Menu</h1>
                <button id="cart-btn" class="btn btn-primary" onclick="toggleCart()" style="min-width: 120px;">
                    <i class="fas fa-shopping-cart"></i> Cart <span id="cart-count" class="badge badge-pending"
                        style="background: white; color: var(--primary); margin-left: 5px;">0</span>
                </button>
            </div>

            <div id="view-menu">
                <div id="menu-grid" class="menu-grid">
                    <p class="text-muted text-center" style="grid-column: 1/-1;">Loading menu items...</p>
                </div>
            </div>

            <div id="view-orders" style="display:none;">
                <h2>Order History</h2>
                <div id="orders-list">
                    <p class="text-muted">Loading your orders...</p>
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

    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initial states
        let cart = [];
        let products = [];

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof cart_get === 'function') {
                cart = cart_get();
            } else {
                console.error('cart_get not found on load!');
            }
            loadMenu();
            loadOrders();
            updateCartUI();
        });

        function switchTab(tab, element) {
            // Remove active class from all links
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

            // Add active class to clicked element or find by tab name
            if (element) {
                element.classList.add('active');
            } else {
                const link = document.querySelector(`.nav-link[onclick*="'${tab}'"]`);
                if (link) link.classList.add('active');
            }

            const viewTitle = document.getElementById('view-title');
            const cartBtn = document.getElementById('cart-btn');

            if (tab === 'orders') {
                document.getElementById('view-menu').style.display = 'none';
                document.getElementById('view-orders').style.display = 'block';
                if (viewTitle) viewTitle.innerText = 'Order History';
                if (cartBtn) cartBtn.style.display = 'none';
                loadOrders();
            } else {
                document.getElementById('view-menu').style.display = 'block';
                document.getElementById('view-orders').style.display = 'none';
                if (viewTitle) viewTitle.innerText = 'Delicious Menu';
                if (cartBtn) cartBtn.style.display = 'block';
                loadMenu(); // Explicitly re-load when switching back
            }
        }

        async function loadMenu() {
            try {
                const result = await apiCall('api/data.php?type=products&_t=' + Date.now());
                products = Array.isArray(result) ? result : [];
                const grid = document.getElementById('menu-grid');
                
                if (!grid) {
                    console.error('loadMenu: Grid element not found!');
                    return;
                }
                if (!products || products.length === 0) {
                    grid.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                            <p class="text-muted">No menu items available at the moment.</p>
                            <button class="btn btn-secondary mt-3" onclick="loadMenu()"><i class="fas fa-sync"></i> Try Refreshing</button>
                        </div>`;
                } else {
                    let html = '';
                    products.forEach(p => {
                        try {
                            html += `
                                <div class="card food-card fade-in">
                                    <img src="${p.image || 'https://via.placeholder.com/400x300?text=Food'}" class="food-img" alt="${p.name || 'Food'}">
                                    <div style="padding: 10px;">
                                        <div class="flex-between">
                                            <div class="food-title">${p.name || 'Unnamed Item'}</div>
                                            <div class="price">${formatCurrency(p.price)}</div>
                                        </div>
                                        <div class="food-desc">${p.description || ''}</div>
                                        <button class="btn btn-primary btn-block mt-3" onclick="addToCart(${p.id})">
                                            <i class="fas fa-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>`;
                        } catch (err) {
                            console.error("Error rendering product:", p, err);
                        }
                    });
                    grid.innerHTML = html;
                }
            } catch (e) {
                document.getElementById('menu-grid').innerHTML = 
                    `<p class="text-danger text-center" style="grid-column: 1/-1;">Error loading menu: ${e.message}</p>`;
            }
        }


        function addToCart(id) {
            const product = products.find(p => p.id == id);
            if (!product) return;
            
            // Use shared logic from main.js
            window.cart_add(product);
            
            // Refresh local state
            cart = window.cart_get();
            updateCartUI();
            toggleCart(true);
        }

        // Shared saveCart is in main.js

        function updateCartUI() {
            const container = document.getElementById('cart-items');
            const countBadge = document.getElementById('cart-count');
            const totalEl = document.getElementById('cart-total');

            const totalCount = cart.reduce((acc, item) => acc + item.quantity, 0);
            
            if (countBadge) {
                countBadge.innerText = totalCount;
                countBadge.classList.remove('pulse');
                void countBadge.offsetWidth; // Trigger reflow
                countBadge.classList.add('pulse');
            }

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
            cart_save(cart);
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
                cart_clear(); // Use shared cart_clear
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
            const badge = document.getElementById('orders-count-badge');
            try {
                const orders = await apiCall('api/data.php?type=orders');
                
                if (badge) {
                    badge.innerText = orders.length;
                    badge.style.display = orders.length > 0 ? 'inline-block' : 'none';
                }

                if (orders.length === 0) {
                    list.innerHTML = '<p class="text-muted">No past orders.</p>';
                } else {
                    list.innerHTML = orders.map(order => `
                        <div class="card mb-3">
                             <div class="flex-between">
                                <div>
                                    <h4>Order #${order.id}</h4>
                                    <span class="badge badge-${order.status}">${order.status.toUpperCase()}</span>
                                    <p class="text-muted mt-2">${order.created_at}</p>
                                     ${(order.status !== 'delivered' && order.status !== 'rejected') ? `
                                         <div class="mt-2" style="display: flex; gap: 10px; flex-wrap: wrap;">
                                             ${(order.status === 'picked_up') ? `
                                                 <button class="btn btn-sm btn-success" onclick="confirmDelivery(${order.id})">
                                                     <i class="fas fa-check-circle"></i> Confirm Receipt
                                                 </button>
                                             ` : ''}
                                             <button class="btn btn-sm btn-danger" onclick="rejectOrder(${order.id})">
                                                 <i class="fas fa-times-circle"></i> Reject Order
                                             </button>
                                         </div>
                                     ` : ''}
                                </div>
                                <div class="text-right">
                                    <h3>${formatCurrency(order.total_price)}</h3>
                                    <p class="text-muted"><i class="fas fa-shopping-bag"></i> Item(s) recorded</p>
                                </div>
                             </div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                list.innerHTML = `<p class="text-danger">Error loading orders: ${e.message}</p>`;
            }
        }

        async function confirmDelivery(orderId) {
            const feedback = prompt('Glad you received your food! Any feedback for us? (Optional)');
            if (feedback === null) return; // Cancelled the whole action
            
            try {
                await apiCall('api/data.php?type=update_order', 'POST', {
                    order_id: orderId,
                    status: 'delivered',
                    feedback: feedback.trim() || 'Food received successfully'
                });
                alert('Order confirmed! Enjoy your meal.');
                loadOrders(); // Refresh the list
            } catch (e) {
                alert('Failed to confirm delivery: ' + e.message);
            }
        }

        async function rejectOrder(orderId) {
            const reason = prompt('Please tell us why you are rejecting this order:');
            if (reason === null) return; // Cancelled
            if (!reason.trim()) return alert('A reason is required to reject an order.');
            
            try {
                await apiCall('api/data.php?type=update_order', 'POST', {
                    order_id: orderId,
                    status: 'rejected',
                    feedback: 'REJECTED: ' + reason.trim()
                });
                alert('Order rejected. We are sorry for the inconvenience.');
                loadOrders(); // Refresh the list
            } catch (e) {
                alert('Failed to reject order: ' + e.message);
            }
        }
    </script>
</body>

</html>