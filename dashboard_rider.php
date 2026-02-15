<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rider') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" class="brand"
                style="text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #00b894 0%, #00cec9 100%); border-radius: 12px; margin-bottom: 10px;">
                <div style="display: flex; flex-direction: column; text-align: center;">
                    <span style="font-size: 24px; font-weight: 700; color: white; letter-spacing: 1px;">MISOSI</span>
                    <span style="font-size: 11px; color: rgba(255,255,255,0.8); font-weight: 500;">Rider
                        Dashboard</span>
                </div>
            </a>
            <nav>
                <a href="javascript:void(0)" class="nav-link active"><i class="fas fa-clipboard-list"></i> My Tasks</a>
                <a href="javascript:void(0)" class="nav-link" onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="mb-4" style="text-align: center;">
                <h1>🚗 Active Deliveries</h1>
                <p class="text-muted">Manage your assigned orders</p>
            </div>

            <!-- Delivery Report Summary -->
            <div id="delivery-report" class="mb-4">
                <!-- Loaded via JS -->
            </div>

            <div id="orders-list">
                <!-- Loaded via JS -->
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        console.log('[RIDER] Dashboard loaded');

        // Load data on init
        loadDeliveryReport();
        loadOrders();

        // Refresh every 5 seconds
        setInterval(() => {
            console.log('[RIDER] Auto-refreshing orders');
            loadOrders();
        }, 5000);

        async function loadDeliveryReport() {
            const reportDiv = document.getElementById('delivery-report');
            try {
                console.log('[RIDER] Loading delivery report');
                const data = await apiCall('api/data.php?type=delivery_report');

                console.log('[RIDER] Report data:', data);
                const summary = data.summary;
                const orders = data.orders;

                reportDiv.innerHTML = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div class="card" style="border-top: 4px solid #667eea;">
                            <h3 style="color: #667eea; margin: 0 0 10px 0;">📦 Total Orders</h3>
                            <div style="font-size: 32px; font-weight: bold; color: #333;">${summary.total_orders || 0}</div>
                        </div>
                        <div class="card" style="border-top: 4px solid #3498db;">
                            <h3 style="color: #3498db; margin: 0 0 10px 0;">🔄 Accepted</h3>
                            <div style="font-size: 32px; font-weight: bold; color: #333;">${summary.in_delivery || 0}</div>
                        </div>
                        <div class="card" style="border-top: 4px solid #FFA500;">
                            <h3 style="color: #FFA500; margin: 0 0 10px 0;">⏳ Waiting Acceptance</h3>
                            <div style="font-size: 32px; font-weight: bold; color: #333;">${summary.waiting_pickup || 0}</div>
                        </div>
                        <div class="card" style="border-top: 4px solid #00b894;">
                            <h3 style="color: #00b894; margin: 0 0 10px 0;">✅ Completed</h3>
                            <div style="font-size: 32px; font-weight: bold; color: #333;">${summary.completed || 0}</div>
                        </div>
                        <div class="card" style="border-top: 4px solid #e74c3c;">
                            <h3 style="color: #e74c3c; margin: 0 0 10px 0;">❌ Rejected</h3>
                            <div style="font-size: 32px; font-weight: bold; color: #333;">${summary.rejected || 0}</div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <h3>📋 Orders & Customers</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                                    <th style="padding: 10px; text-align: left;">Order ID</th>
                                    <th style="padding: 10px; text-align: left;">Customer Name</th>
                                    <th style="padding: 10px; text-align: left;">Status</th>
                                    <th style="padding: 10px; text-align: left;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${orders.map(o => `
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 10px;"><strong>#${o.id}</strong></td>
                                        <td style="padding: 10px;">${o.customer_name}</td>
                                        <td style="padding: 10px;"><span class="badge badge-${o.status}">${o.status.toUpperCase().replace('_', ' ')}</span></td>
                                        <td style="padding: 10px; color: #667eea; font-weight: bold;">${formatCurrency(o.total_price)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } catch (e) {
                console.error('[RIDER] Report error:', e);
                reportDiv.innerHTML = '<p class="text-danger">Failed to load delivery report.</p>';
            }
        }

        async function loadOrders() {
            const list = document.getElementById('orders-list');
            try {
                console.log('[RIDER] Loading orders');
                const orders = await apiCall('api/data.php?type=orders');

                console.log('[RIDER] Orders received:', orders.length);

                if (orders.length === 0) {
                    list.innerHTML = '<p class="text-muted text-center">No active deliveries.</p>';
                    return;
                }

                list.innerHTML = orders.map(order => {
                    console.log('[RIDER] Processing order:', order.id, 'status:', order.status);

                    let actionButton = '';
                    let statusColor = '#999';

                    if (order.status === 'assigned') {
                        // Show accept/deny buttons for assigned orders
                        actionButton = `
                            <div style="display: flex; gap: 8px; flex-direction: column;">
                                <button class="btn btn-success" onclick="acceptOrder(${order.id})" style="background: #00b894; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn btn-danger" onclick="denyOrder(${order.id})" style="background: #e74c3c; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                    <i class="fas fa-times"></i> Deny
                                </button>
                            </div>
                        `;
                        statusColor = '#FFA500';
                    } else if (order.status === 'accepted') {
                        actionButton = `<button class="btn btn-primary" onclick="updateStatus(${order.id}, 'picked_up')" style="background: #667eea; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">✓ Ready for Pickup</button>`;
                        statusColor = '#3498db';
                    } else if (order.status === 'picked_up') {
                        actionButton = `<button class="btn btn-success" onclick="updateStatus(${order.id}, 'delivered')" style="background: #00b894; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">✓ Mark Delivered</button>`;
                        statusColor = '#FFD700';
                    } else if (order.status === 'delivered') {
                        actionButton = `<span class="badge" style="background: #00b894; color: white; padding: 8px 12px; border-radius: 4px;">✅ COMPLETED</span>`;
                        statusColor = '#00b894';
                    } else if (order.status === 'rejected') {
                        actionButton = `<span class="badge" style="background: #e74c3c; color: white; padding: 8px 12px; border-radius: 4px;">❌ REJECTED</span>`;
                        statusColor = '#c0392b';
                    } else {
                        actionButton = `<span class="badge" style="background: #999; color: white; padding: 8px 12px; border-radius: 4px;">WAITING</span>`;
                    }

                    return `
                    <div class="card mb-4" style="border-left: 5px solid ${statusColor};">
                        <div class="flex-between" style="flex-wrap: wrap; gap: 15px;">
                            <div style="flex: 1; min-width: 250px;">
                                <h2 style="margin-bottom: 10px;">Order #${order.id}</h2>
                                <p style="margin: 5px 0;"><strong>👤 Customer:</strong> ${order.customer_name}</p>
                                <p style="margin: 5px 0;"><strong>📍 Address:</strong> ${order.delivery_address || 'No Address'}</p>
                                <p style="margin: 5px 0; color: #999;">Created: ${new Date(order.created_at).toLocaleString()}</p>
                            </div>
                            <div style="text-align: right; min-width: 150px;">
                                <h3 style="color: #667eea; margin-bottom: 15px;">${formatCurrency(order.total_price)}</h3>
                                <p style="background: ${statusColor}; color: white; padding: 8px; border-radius: 4px; margin-bottom: 10px; font-weight: bold;">
                                    ${order.status.toUpperCase().replace('_', ' ')}
                                </p>
                                ${actionButton}
                            </div>
                        </div>
                    </div>`;
                }).join('');
            } catch (e) {
                console.error('[RIDER] Orders error:', e);
                list.innerHTML = '<p class="text-danger">Failed to load orders.</p>';
            }
        }

        async function acceptOrder(id) {
            if (!confirm(`Accept order #${id}?`)) return;

            try {
                console.log('[RIDER] Accepting order', id);
                const response = await apiCall('api/data.php?type=accept_order', 'POST', {
                    order_id: id
                });

                console.log('[RIDER] Accept response:', response);

                if (response.success || response.message) {
                    alert('✅ ' + (response.message || 'Order accepted successfully!'));
                    loadDeliveryReport();
                    loadOrders();
                } else {
                    alert('❌ Error: ' + (response.error || 'Failed to accept order'));
                }
            } catch (e) {
                console.error('[RIDER] Accept error:', e);
                alert('❌ Error: ' + e.message);
            }
        }

        async function denyOrder(id) {
            if (!confirm(`Are you sure you want to deny order #${id}? This cannot be undone.`)) return;

            try {
                console.log('[RIDER] Denying order', id);
                const response = await apiCall('api/data.php?type=deny_order', 'POST', {
                    order_id: id
                });

                console.log('[RIDER] Deny response:', response);

                if (response.success || response.message) {
                    alert('⚠️ ' + (response.message || 'Order rejected!'));
                    loadDeliveryReport();
                    loadOrders();
                } else {
                    alert('❌ Error: ' + (response.error || 'Failed to reject order'));
                }
            } catch (e) {
                console.error('[RIDER] Deny error:', e);
                alert('❌ Error: ' + e.message);
            }
        }

        async function updateStatus(id, status) {
            const statusLabel = status.replace('_', ' ').toUpperCase();
            const confirmMsg = status === 'delivered' ? `Mark order #${id} as DELIVERED? You will not be able to change this.` : `Update order #${id} to ${statusLabel}?`;
            if (!confirm(confirmMsg)) return;

            try {
                console.log('[RIDER] Updating order', id, 'to status:', status);
                const response = await apiCall('api/data.php?type=update_order', 'POST', {
                    order_id: id,
                    status: status
                });

                console.log('[RIDER] Update response:', response);

                if (response.success || response.message) {
                    alert('✅ ' + (response.message || 'Order updated successfully!'));
                    loadDeliveryReport();
                    loadOrders();
                } else {
                    alert('❌ Error: ' + (response.error || 'Failed to update order'));
                }
            } catch (e) {
                console.error('[RIDER] Update error:', e);
                alert('❌ Error: ' + e.message);
            }
        }
    </script>
</body>

</html>