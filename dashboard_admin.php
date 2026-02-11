<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" class="brand" style="text-decoration: none;">
                <i class="fas fa-utensils"></i> Misosi Admin
            </a>
            <nav>
                <a href="javascript:void(0)" class="nav-link active" onclick="switchTab('orders', this)"><i
                        class="fas fa-list-alt"></i> Orders</a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchTab('products', this)"><i
                        class="fas fa-hamburger"></i> Menu Items</a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchTab('reports', this)"><i
                        class="fas fa-chart-line"></i> Sales Report</a>
                <a href="javascript:void(0)" class="nav-link" onclick="switchTab('users', this)"><i
                        class="fas fa-users-cog"></i> Manage Users</a>
                <a href="javascript:void(0)" class="nav-link" onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Headers -->
            <div id="header-orders" class="mb-4">
                <h1>Incoming Orders</h1>
                <p class="text-muted">Manage and assign orders to riders</p>
            </div>
            <div id="header-products" class="mb-4" style="display:none;">
                <div class="flex-between">
                    <div>
                        <h1>Menu Management</h1>
                        <p class="text-muted">Add or remove food items</p>
                    </div>
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                </div>
            </div>

            <div id="header-reports" class="mb-4" style="display:none;">
                <h1>Sales Report</h1>
                <p class="text-muted">View daily sales and revenue</p>
            </div>

            <div id="header-users" class="mb-4" style="display:none;">
                <div class="flex-between">
                    <div>
                        <h1>User Management</h1>
                        <p class="text-muted">Register and manage riders/admins</p>
                    </div>
                    <button class="btn btn-primary" onclick="showUserModal()">
                        <i class="fas fa-user-plus"></i> Register New User
                    </button>
                </div>
            </div>

            <!-- Content Areas -->
            <div id="view-orders">
                <div id="orders-list">
                    <!-- Loaded via JS -->
                    <div class="text-center p-5">Loading orders...</div>
                </div>
            </div>

            <div id="view-products" style="display:none;">
                <div id="products-grid" class="menu-grid">
                    <!-- Loaded via JS -->
                </div>
            </div>

            <div id="view-reports" style="display:none;">
                <h1>Sales Report</h1>
                <div class="card mb-4">
                    <div class="flex-between">
                        <input type="date" id="report-date" class="form-control" style="max-width:300px;"
                            onchange="loadReport()">
                        <h3 id="report-total">Total: Tsh 0.00</h3>
                    </div>
                </div>
                <div id="report-results"></div>
            </div>

            <div id="view-users" style="display:none;">
                <div class="card p-0 overflow-hidden">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead style="background: rgba(0,0,0,0.05); text-align: left;">
                            <tr>
                                <th style="padding: 15px;">Name</th>
                                <th style="padding: 15px;">Email</th>
                                <th style="padding: 15px;">Role</th>
                                <th style="padding: 15px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <!-- Loaded via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Product Modal (Simple Generic Overlay) -->
    <div id="product-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; overflow-y:auto; padding:20px;">
        <div class="glass-panel" style="max-width:500px; text-align:left; width:100%;">
            <h3 class="mb-4"><i class="fas fa-plus"></i> Add New Food Item</h3>
            <form id="add-product-form" onsubmit="handleAddProduct(event)">
                <div class="form-group">
                    <label><strong>Food Name</strong></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Biryani" required>
                </div>
                <div class="form-group">
                    <label><strong>Description</strong></label>
                    <textarea name="description" class="form-control" placeholder="Describe the food item..." rows="3"
                        required></textarea>
                </div>
                <div class="form-group">
                    <label><strong>Price</strong></label>
                    <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" min="0"
                        required>
                </div>
                <div class="form-group">
                    <label><strong>Food Image</strong></label>
                    <input type="file" name="image" class="form-control" accept="image/*" required>
                    <small class="text-muted">Recommended size: 400x300 pixels</small>
                </div>
                <div class="flex-between mt-4">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register User Modal -->
    <div id="user-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; overflow-y:auto; padding:20px;">
        <div class="glass-panel" style="max-width:480px; text-align:left; width:100%;">
            <h3 class="mb-4"><i class="fas fa-user-plus"></i> Register Rider/Admin</h3>
            <form id="add-user-form" onsubmit="handleAddUser(event)">
                <div class="form-group">
                    <label><strong>Full Name</strong></label>
                    <input type="text" name="name" id="user-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><strong>Email</strong></label>
                    <input type="email" name="email" id="user-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><strong>Password</strong></label>
                    <input type="password" name="password" id="user-password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><strong>Role</strong></label>
                    <select name="role" id="user-role" class="form-control">
                        <option value="rider">Rider</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex-between mt-4">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; overflow-y:auto; padding:20px;">
        <div class="glass-panel" style="max-width:480px; text-align:left; width:100%;">
            <h3 class="mb-4"><i class="fas fa-user-edit"></i> Edit User</h3>
            <form id="edit-user-form" onsubmit="handleEditUser(event)">
                <input type="hidden" name="id" id="edit-user-id">
                <div class="form-group">
                    <label><strong>Full Name</strong></label>
                    <input type="text" name="name" id="edit-user-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><strong>Email</strong></label>
                    <input type="email" name="email" id="edit-user-email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><strong>New Password</strong></label>
                    <input type="password" name="password" id="edit-user-password" class="form-control"
                        placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label><strong>Role</strong></label>
                    <select name="role" id="edit-user-role" class="form-control">
                        <option value="customer">Customer</option>
                        <option value="rider">Rider</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex-between mt-4">
                    <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // State
        let riders = [];
        let allUsers = [];

        // Init
        // Load riders first, then orders to ensure dropdowns are populated
        loadRiders().then(() => {
            loadOrders();
        });

        function switchTab(tab, el) {
            console.log('[TABS] Switching to tab:', tab);

            // Update active link styling
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            if (el) {
                el.classList.add('active');
            } else {
                // Find link by tab name if el is not provided
                document.querySelectorAll('.nav-link').forEach(l => {
                    if (l.innerText.toLowerCase().includes(tab)) l.classList.add('active');
                });
            }

            // Hide all headers
            ['header-orders', 'header-products', 'header-reports', 'header-users'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            // Hide all views
            ['view-orders', 'view-products', 'view-reports', 'view-users'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            // Show selected tab
            if (tab === 'orders') {
                const headerOrders = document.getElementById('header-orders');
                const viewOrders = document.getElementById('view-orders');
                if (headerOrders) headerOrders.style.display = 'block';
                if (viewOrders) viewOrders.style.display = 'block';
                loadOrders();
            } else if (tab === 'products') {
                const headerProducts = document.getElementById('header-products');
                const viewProducts = document.getElementById('view-products');
                if (headerProducts) headerProducts.style.display = 'block';
                if (viewProducts) viewProducts.style.display = 'block';
                loadProducts();
            } else if (tab === 'reports') {
                const headerReports = document.getElementById('header-reports');
                const viewReports = document.getElementById('view-reports');
                if (headerReports) headerReports.style.display = 'block';
                if (viewReports) viewReports.style.display = 'block';
                const reportDate = document.getElementById('report-date');
                if (reportDate) reportDate.valueAsDate = new Date();
                loadReport();
            } else if (tab === 'users') {
                const headerUsers = document.getElementById('header-users');
                const viewUsers = document.getElementById('view-users');
                if (headerUsers) headerUsers.style.display = 'block';
                if (viewUsers) viewUsers.style.display = 'block';
                loadUsers();
            }

            console.log('[TABS] Tab switch complete');
        }

        async function loadRiders() {
            riders = await apiCall('api/data.php?type=riders');
        }

        async function loadOrders() {
            const list = document.getElementById('orders-list');
            try {
                const orders = await apiCall('api/data.php?type=orders');
                list.innerHTML = orders.map(order => `
                    <div class="card mb-4 fade-in">
                        <div style="margin-bottom: 20px;">
                            <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Order #${order.id}</h2>
                            <p class="text-muted" style="margin-bottom: 10px; font-size: 1.1rem;">Customer: ${order.customer_name} | ${new Date(order.created_at).toLocaleString()}</p>
                            <p style="font-size: 1.3rem; margin-bottom: 15px;"><strong>Total: ${formatCurrency(order.total_price)}</strong></p>
                            <div style="margin-bottom: 20px;">
                                <span class="badge badge-${order.status}">${order.status.toUpperCase()}</span>
                            </div>
                        </div>
                        <div style="padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.05);">
                            ${renderOrderActions(order)}
                        </div>
                    </div>
                `).join('');
                if (orders.length === 0) list.innerHTML = '<p class="text-center text-muted">No orders found.</p>';
            } catch (e) {
                list.innerHTML = '<p class="text-danger">Failed to load orders.</p>';
            }
        }

        function renderOrderActions(order) {
            if (order.status === 'pending') {
                return `
                     <div style="display: flex; gap: 10px; align-items: center;">
                         <select id="rider-select-${order.id}" class="form-control" style="width: 200px;">
                             <option value="">Choose Rider...</option>
                             ${riders.map(r => `<option value="${r.id}">${r.name}</option>`).join('')}
                         </select>
                         <button class="btn btn-primary" onclick="assignOrder(${order.id})" style="padding: 10px 25px;">Assign & Accept</button>
                         <button class="btn btn-secondary" onclick="rejectOrder(${order.id})" style="color: #ff4757; border-color: #fed7d7;">Reject</button>
                     </div>
                 `;
            } if (order.status === 'rejected') {
                return '<button class="btn btn-secondary" style="background:#fed7d7; color:#822727; border:none;" disabled>Order Rejected</button>';
            } if (order.status === 'delivered') {
                return '<button class="btn btn-secondary" disabled style="padding: 15px 40px; font-size: 1.1rem; width: fit-content;">Completed</button>';
            }
            else {
                return `<div class="btn btn-secondary" disabled><i class="fas fa-truck"></i> Assigned: ${order.rider_name || 'Rider'}</div>`;
            }
        }

        async function assignOrder(id) {
            const riderId = document.getElementById(`rider-select-${id}`).value;
            if (!riderId) return alert('Please select a rider first');

            await apiCall('api/data.php?type=update_order', 'POST', {
                order_id: id,
                status: 'accepted',
                rider_id: riderId
            });
            loadOrders();
        }

        async function rejectOrder(id) {
            if (!confirm('Are you sure you want to reject this order?')) return;
            await apiCall('api/data.php?type=update_order', 'POST', {
                order_id: id,
                status: 'rejected'
            });
            loadOrders();
        }

        async function loadProducts() {
            const grid = document.getElementById('products-grid');
            try {
                const products = await apiCall('api/data.php?type=products');
                if (products.length === 0) {
                    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><p class="text-muted">No food items yet. Add your first item!</p></div>';
                    return;
                }
                grid.innerHTML = products.map(p => `
                    <div class="card food-card">
                        <img src="${p.image}" class="food-img" alt="${p.name}" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                        <div style="padding: 10px;">
                            <div class="food-title">${p.name}</div>
                            <div class="food-desc">${p.description}</div>
                            <div class="price" style="font-weight:bold; color:#e74c3c; font-size:1.2rem; margin:10px 0;">${formatCurrency(p.price)}</div>
                            <div class="mt-3" style="display:flex; gap:5px;">
                                <button class="btn btn-secondary" style="flex:1; padding:5px 10px; font-size:0.8rem;" onclick="openEdit(${p.id}, '${p.name}', ${p.price})"><i class="fas fa-edit"></i> Edit Price</button>
                                <button class="btn btn-danger" style="flex:1; padding:5px 10px; font-size:0.8rem; background:var(--danger); color:white; border:none;" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (e) {
                grid.innerHTML = '<p class="text-danger">Failed to load products.</p>';
            }
        }

        function showAddProductModal() {
            console.log('[MODAL] Opening add product modal');
            const modal = document.getElementById('product-modal');
            modal.style.display = 'flex';
            // Ensure it's visible
            setTimeout(() => {
                console.log('[MODAL] Modal display:', window.getComputedStyle(modal).display);
            }, 100);
        }

        function closeModal() {
            console.log('[MODAL] Closing modal');
            document.getElementById('product-modal').style.display = 'none';
        }

        // Close modal when clicking outside (on the overlay)
        document.addEventListener('DOMContentLoaded', () => {
            console.log('[INIT] Dashboard loaded');

            const modal = document.getElementById('product-modal');
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        console.log('[MODAL] Clicked outside modal, closing');
                        closeModal();
                    }
                });
            }

            // Ensure form exists
            const form = document.getElementById('add-product-form');
            if (form) {
                console.log('[INIT] Add product form found');
                form.addEventListener('submit', (e) => {
                    console.log('[FORM] Form submit event fired');
                });
            } else {
                console.error('[INIT] Add product form NOT found!');
            }
        });

        async function handleAddProduct(e) {
            e.preventDefault();
            console.log('[ADD PRODUCT] Form submission started');

            const form = document.getElementById('add-product-form');
            const formData = new FormData(form);

            // Log form data
            console.log('[ADD PRODUCT] Form data:', {
                name: formData.get('name'),
                description: formData.get('description'),
                price: formData.get('price'),
                image: formData.get('image')?.name || 'no file'
            });

            // Validate form inputs
            if (!formData.get('name') || !formData.get('description') || !formData.get('price')) {
                alert('❌ Please fill in all fields (Name, Description, Price)');
                console.error('[ADD PRODUCT] Validation failed: missing fields');
                return;
            }

            const price = parseFloat(formData.get('price'));
            if (isNaN(price) || price <= 0) {
                alert('❌ Price must be a valid number greater than 0');
                console.error('[ADD PRODUCT] Validation failed: invalid price', price);
                return;
            }

            try {
                console.log('[ADD PRODUCT] Sending request to API...');
                const response = await fetch('api/data.php?type=products', {
                    method: 'POST',
                    body: formData
                });

                console.log('[ADD PRODUCT] Response status:', response.status);
                const text = await response.text();
                console.log('[ADD PRODUCT] Raw response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseErr) {
                    console.error('[ADD PRODUCT] JSON parse error:', parseErr);
                    alert('❌ Server error: Invalid response. Check browser console.');
                    return;
                }

                console.log('[ADD PRODUCT] Parsed result:', result);

                if (result.success) {
                    console.log('[ADD PRODUCT] Success! Product added.');
                    alert('✅ Food item added successfully!');
                    closeModal();
                    form.reset();
                    await loadProducts();
                } else {
                    const errorMsg = result.message || 'Failed to add item';
                    console.error('[ADD PRODUCT] API error:', errorMsg);
                    alert('❌ ' + errorMsg);
                }
            } catch (err) {
                console.error('[ADD PRODUCT] Exception:', err);
                alert('❌ Upload failed: ' + err.message);
            }
        }

        /* Edit/Delete Functions */
        function openEdit(id, name, price) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-item-name').value = name;
            document.getElementById('edit-price').value = price;
            document.getElementById('edit-modal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('edit-modal').style.display = 'none';
            document.getElementById('edit-price').value = '';
        }

        async function submitEdit() {
            const id = document.getElementById('edit-id').value;
            const price = document.getElementById('edit-price').value;

            if (!price || parseFloat(price) < 0) {
                alert('Please enter a valid price');
                return;
            }

            try {
                await apiCall('api/data.php?type=update_product', 'POST', { id, price: parseFloat(price) });
                alert('Price updated successfully!');
                closeEditModal();
                loadProducts();
            } catch (err) {
                alert('Failed to update price: ' + err.message);
            }
        }

        async function deleteProduct(id) {
            const confirmed = confirm("⚠️ Are you sure you want to delete this food item? This action cannot be undone.");
            if (!confirmed) return;

            try {
                await apiCall('api/data.php?type=delete_product', 'POST', { id });
                alert('Item deleted successfully!');
                loadProducts();
            } catch (err) {
                alert('Failed to delete item: ' + err.message);
            }
        }

        /* Report Functions */
        async function loadReport() {
            const date = document.getElementById('report-date').value;
            const res = await apiCall('api/data.php?type=sales_report', 'POST', { date });
            document.getElementById('report-total').innerText = 'Total: ' + formatCurrency(res.total);
            document.getElementById('report-results').innerHTML = res.orders.map(o => `
                <div class="card mb-2 flex-between">
                    <div><strong>#${o.id}</strong> - ${o.customer}</div>
                    <div>${formatCurrency(o.total_price)}</div>
                </div>
            `).join('') || '<p class="text-muted">No sales found for this date.</p>';
        }

        // User registration modal controls
        function showUserModal() {
            const modal = document.getElementById('user-modal');
            if (modal) modal.style.display = 'flex';
        }

        function closeUserModal() {
            const modal = document.getElementById('user-modal');
            if (modal) modal.style.display = 'none';
            const form = document.getElementById('add-user-form');
            if (form) form.reset();
        }

        async function handleAddUser(e) {
            e.preventDefault();
            const name = document.getElementById('user-name').value.trim();
            const email = document.getElementById('user-email').value.trim();
            const password = document.getElementById('user-password').value;
            const role = document.getElementById('user-role').value;

            if (!name || !email || !password) {
                alert('Please fill in all fields');
                return;
            }

            try {
                const res = await apiCall('api/data.php?type=create_user', 'POST', { name, email, password, role });
                if (res.success) {
                    alert('User created successfully');
                    closeUserModal();
                    if (document.getElementById('view-users').style.display !== 'none') {
                        loadUsers();
                    }
                    loadRiders();
                }
            } catch (err) {
                // apiCall shows alerts for errors
            }
        }

        async function loadUsers() {
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">Loading users...</td></tr>';

            try {
                allUsers = await apiCall('api/data.php?type=users');
                tbody.innerHTML = allUsers.map(u => `
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <td style="padding: 15px;">${u.name}</td>
                        <td style="padding: 15px;">${u.email}</td>
                        <td style="padding: 15px;"><span class="badge badge-${u.role}">${u.role.toUpperCase()}</span></td>
                        <td style="padding: 15px;">
                            <button class="btn btn-secondary btn-sm" onclick="openEditUserById(${u.id})"><i class="fas fa-edit"></i> Edit</button>
                            ${u.id != currentUserId ? `<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})"><i class="fas fa-trash"></i> Delete</button>` : ''
                    }
                        </td >
                    </tr >
                `).join('');
                if (allUsers.length === 0) tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">No users found.</td></tr>';
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-danger">Failed to load users.</td></tr>';
            }
        }

        function openEditUserById(id) {
            const user = allUsers.find(u => u.id == id);
            if (!user) return;
            document.getElementById('edit-user-id').value = user.id;
            document.getElementById('edit-user-name').value = user.name;
            document.getElementById('edit-user-email').value = user.email;
            document.getElementById('edit-user-role').value = user.role;
            document.getElementById('edit-user-password').value = '';
            document.getElementById('edit-user-modal').style.display = 'flex';
        }

        function closeEditUserModal() {
            document.getElementById('edit-user-modal').style.display = 'none';
        }

        async function handleEditUser(e) {
            e.preventDefault();
            const id = document.getElementById('edit-user-id').value;
            const name = document.getElementById('edit-user-name').value;
            const email = document.getElementById('edit-user-email').value;
            const role = document.getElementById('edit-user-role').value;
            const password = document.getElementById('edit-user-password').value;

            try {
                const res = await apiCall('api/data.php?type=update_user', 'POST', { id, name, email, role, password });
                if (res.success) {
                    alert('User updated successfully');
                    closeEditUserModal();
                    loadUsers();
                    loadRiders();
                }
            } catch (err) { }
        }

        async function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user?')) return;
            try {
                const res = await apiCall('api/data.php?type=delete_user', 'POST', { id });
                if (res.success) {
                    alert('User deleted successfully');
                    loadUsers();
                    loadRiders();
                }
            } catch (err) { }
        }
    </script>
    </bo