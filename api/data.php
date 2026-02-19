<?php
// api/data.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();
session_write_close(); // Prevent session locking for GET requests
$enable_json_errors = true;
require_once '../db.php';
file_put_contents('../debug.log', date('[Y-m-d H:i:s] ') . "API Call: " . $_SERVER['REQUEST_URI'] . " Session: " . json_encode($_SESSION) . "\n", FILE_APPEND);

if (isset($db_error)) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Database Error: " . $db_error]);
    exit;
}

// Publicly accessible GET type
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? '';

if ($method === 'GET' && $type === 'products') {
    $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC");
    $result = $stmt->fetchAll();
    if (ob_get_length())
        ob_clean();
    echo json_encode($result);
    exit;
}

// Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    if (ob_get_length())
        ob_clean();
    die(json_encode(['error' => 'Unauthorized']));
}

if ($method === 'GET') {
    if ($type === 'products') {
        $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC");
        $result = $stmt->fetchAll();
        if (ob_get_length())
            ob_clean();
        echo json_encode($result);
        exit;
    }
    elseif ($type === 'orders') {
        $role = $_SESSION['role'];
        $userId = $_SESSION['user_id'];

        if ($role === 'admin') {
            $sql = "SELECT o.*, u.name as customer_name, r.name as rider_name 
                    FROM orders o 
                    JOIN users u ON o.customer_id = u.id 
                    LEFT JOIN users r ON o.rider_id = r.id 
                    ORDER BY o.created_at DESC";
            $stmt = $pdo->query($sql);
        }
        elseif ($role === 'rider') {
            $sql = "SELECT o.*, u.name as customer_name 
                FROM orders o 
                JOIN users u ON o.customer_id = u.id 
                WHERE o.rider_id = ?
                ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
        else {
            // Customer
            $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
        ob_clean();
        echo json_encode($stmt->fetchAll());
    }
    elseif ($type === 'order_items') {
        $orderId = $_GET['order_id'];
        $stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE order_id = ?");
        $stmt->execute([$orderId]);
        ob_clean();
        echo json_encode($stmt->fetchAll());
    }
    elseif ($type === 'riders') {
        // For admin to assign
        if ($_SESSION['role'] !== 'admin')
            die(json_encode([]));
        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'rider'");
        ob_clean();
        echo json_encode($stmt->fetchAll());
    }
    elseif ($type === 'users') {
        if ($_SESSION['role'] !== 'admin')
            die(json_encode(['error' => 'Unauthorized']));
        $stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY role, name");
        ob_clean();
        echo json_encode($stmt->fetchAll());
    }
    elseif ($type === 'delivery_report') {
        // For riders to see their delivery summary
        if ($_SESSION['role'] !== 'rider')
            die(json_encode(['error' => 'Unauthorized']));

        $riderId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('picked_up', 'accepted') THEN 1 ELSE 0 END) as in_delivery,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as waiting_pickup,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM orders 
            WHERE rider_id = ? OR (status = 'assigned' AND rider_id IS NULL)
        ");
        $stmt->execute([$riderId]);
        $summary = $stmt->fetch();

        // Get list of assigned orders with customer names
        $orderStmt = $pdo->prepare("
            SELECT 
                o.id,
                o.status,
                o.total_price,
                o.delivery_address,
                u.name as customer_name
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            WHERE o.rider_id = ? AND o.status IN ('assigned', 'accepted', 'picked_up', 'delivered')
            ORDER BY 
                CASE WHEN o.status = 'assigned' THEN 1
                     WHEN o.status = 'accepted' THEN 2
                     WHEN o.status = 'picked_up' THEN 3
                     WHEN o.status = 'delivered' THEN 4
                END,
                o.created_at DESC
        ");
        $orderStmt->execute([$riderId]);
        $orders = $orderStmt->fetchAll();

        ob_clean();
        echo json_encode([
            'summary' => $summary,
            'orders' => $orders
        ]);
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($type === 'products' && $_SESSION['role'] === 'admin') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        error_log('[API] Add product request: name=' . $name . ', price=' . $price);

        // Validation
        if (empty($name) || empty($description) || $price <= 0) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid input. Name, description, and valid price required.']);
            error_log('[API] Add product validation failed');
            exit;
        }

        // Handle Image Upload
        $imagePath = 'https://source.unsplash.com/random/400x300/?food'; // Default

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                error_log('[API] Created upload directory: ' . $uploadDir);
            }

            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'assets/uploads/' . $fileName;
                error_log('[API] Image uploaded: ' . $imagePath);
            }
            else {
                error_log('[API] Image upload failed: ' . $_FILES['image']['error']);
            }
        }

        try {
            error_log('[API] Inserting product into database');
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $imagePath]);
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Food item added successfully']);
            error_log('[API] Product inserted successfully');
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            error_log('[API] Database error: ' . $e->getMessage());
        }
    }
    elseif ($type === 'delete_product' && $_SESSION['role'] === 'admin') {
        if (!isset($data['id']) || empty($data['id'])) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }

        try {
            $id = intval($data['id']);
            $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            }
            else {
                http_response_code(404);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

    }
    elseif ($type === 'update_product' && $_SESSION['role'] === 'admin') {
        if (!isset($data['id']) || empty($data['id']) || !isset($data['price'])) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Product ID and price are required']);
            exit;
        }

        $id = intval($data['id']);
        $price = floatval($data['price']);

        if ($price <= 0) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
            $stmt->execute([$price, $id]);

            if ($stmt->rowCount() > 0) {
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Price updated successfully']);
            }
            else {
                http_response_code(404);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

    }
    elseif ($type === 'orders') {
        // Place Order
        // ... (existing code for orders) ...
        if (!is_array($data)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }

        $items = $data['items'] ?? [];
        $total = $data['total'] ?? 0;
        $address = $data['address'] ?? '';

        if (empty($items) || !is_array($items)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Cart is empty or invalid']);
            exit;
        }

        if (empty($address)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Delivery address is required']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            // Verify items exist
            foreach ($items as $item) {
                if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
                    throw new Exception("Invalid item data in cart");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_price, delivery_address) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $address]);
            $orderId = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmtItem->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            }
            $pdo->commit();
            ob_clean();
            echo json_encode(['success' => true]);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($type === 'sales_report') {
        if ($_SESSION['role'] !== 'admin')
            die(json_encode(['error' => 'Unauthorized']));

        $date = $data['date'] ?? date('Y-m-d');
        // Retrieve all delivered orders for a specific date
        // SQLite date function usage: strftime('%Y-%m-%d', created_at)
        $stmt = $pdo->prepare("
            SELECT o.id, o.total_price, o.created_at, u.name as customer 
            FROM orders o 
            JOIN users u ON o.customer_id = u.id 
            WHERE o.status = 'delivered' AND date(o.created_at) = ?
        ");
        $stmt->execute([$date]);
        $orders = $stmt->fetchAll();

        $totalSales = 0;
        foreach ($orders as $o)
            $totalSales += $o['total_price'];

        ob_clean();
        echo json_encode(['orders' => $orders, 'total' => $totalSales]);

    }
    elseif ($type === 'update_order') {
        $orderId = $data['order_id'];
        $status = $data['status'];

        error_log('[UPDATE_ORDER] Request: order_id=' . $orderId . ', status=' . $status . ', role=' . $_SESSION['role']);

        // Validation
        if ($_SESSION['role'] === 'customer') {
            if ($status !== 'delivered' && $status !== 'rejected') {
                http_response_code(403);
                ob_clean();
                die(json_encode(['error' => 'Customers can only confirm delivery or reject orders']));
            }
            try {
                $feedback = $data['feedback'] ?? null;
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, customer_feedback = ? WHERE id = ? AND customer_id = ?");
                $stmt->execute([$status, $feedback, $orderId, $_SESSION['user_id']]);
                if ($stmt->rowCount() > 0) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
                    exit;
                }
                else {
                    http_response_code(404);
                    ob_clean();
                    die(json_encode(['error' => 'Order not found or already processed']));
                }
            }
            catch (PDOException $e) {
                http_response_code(500);
                ob_clean();
                die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            }
        }

        if ($_SESSION['role'] === 'rider') {
            // Rider can update orders assigned to them
            $riderId = $_SESSION['user_id'];

            // Verify order is assigned to this rider
            $checkStmt = $pdo->prepare("SELECT rider_id FROM orders WHERE id = ?");
            $checkStmt->execute([$orderId]);
            $orderData = $checkStmt->fetch();

            if (!$orderData || $orderData['rider_id'] !== $riderId) {
                http_response_code(403);
                ob_clean();
                echo json_encode(['error' => 'Order not assigned to you']);
                error_log('[UPDATE_ORDER] Rider ' . $riderId . ' tried to update unassigned order');
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND rider_id = ?");
                $result = $stmt->execute([$status, $orderId, $riderId]);

                if ($stmt->rowCount() > 0) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Order status updated to ' . $status]);
                    error_log('[UPDATE_ORDER] Status updated successfully');
                }
                else {
                    http_response_code(404);
                    ob_clean();
                    echo json_encode(['error' => 'Order not found or not assigned to rider']);
                    error_log('[UPDATE_ORDER] Order not found');
                }
            }
            catch (PDOException $e) {
                http_response_code(500);
                ob_clean();
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                error_log('[UPDATE_ORDER] Database error: ' . $e->getMessage());
            }
        }
        else {
            // Admin
            try {
                if (isset($data['rider_id'])) {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ?, rider_id = ? WHERE id = ?");
                    $stmt->execute([$status, $data['rider_id'], $orderId]);
                }
                else {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $orderId]);
                }

                if ($stmt->rowCount() > 0) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Order updated']);
                    error_log('[UPDATE_ORDER] Admin updated order');
                }
                else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Order not found']);
                }
            }
            catch (PDOException $e) {
                http_response_code(500);
                ob_clean();
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        }
    }
    elseif ($type === 'delete_order' && $_SESSION['role'] === 'admin') {
        $orderId = $data['order_id'];
        if (!$orderId) {
            http_response_code(400);
            ob_clean();
            die(json_encode(['success' => false, 'message' => 'Order ID is required']));
        }

        try {
            // Check status first to ensure it's delivered or rejected
            $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                http_response_code(404);
                ob_clean();
                die(json_encode(['success' => false, 'message' => 'Order not found']));
            }

            if ($order['status'] !== 'delivered' && $order['status'] !== 'rejected') {
                http_response_code(403);
                ob_clean();
                die(json_encode(['success' => false, 'message' => 'Only delivered or rejected orders can be deleted']));
            }

            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    elseif ($type === 'accept_order') {
        // Rider accepts an assigned order
        $orderId = $data['order_id'];
        error_log('[ACCEPT_ORDER] Request: order_id=' . $orderId . ', rider_id=' . $_SESSION['user_id']);

        if ($_SESSION['role'] !== 'rider') {
            http_response_code(401);
            ob_clean();
            echo json_encode(['error' => 'Only riders can accept orders']);
            exit;
        }

        $riderId = $_SESSION['user_id'];

        try {
            // Check order is assigned to this rider
            $checkStmt = $pdo->prepare("SELECT id, status, rider_id FROM orders WHERE id = ? AND rider_id = ?");
            $checkStmt->execute([$orderId, $riderId]);
            $order = $checkStmt->fetch();

            if (!$order) {
                http_response_code(404);
                ob_clean();
                echo json_encode(['error' => 'Order not found or not assigned to you']);
                error_log('[ACCEPT_ORDER] Order not found');
                exit;
            }

            if ($order['status'] !== 'assigned') {
                http_response_code(400);
                ob_clean();
                echo json_encode(['error' => 'Only assigned orders can be accepted']);
                exit;
            }

            // Update status to accepted
            $stmt = $pdo->prepare("UPDATE orders SET status = 'accepted' WHERE id = ?");
            $stmt->execute([$orderId]);

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Order accepted successfully']);
            error_log('[ACCEPT_ORDER] Order ' . $orderId . ' accepted by rider ' . $riderId);
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            error_log('[ACCEPT_ORDER] Database error: ' . $e->getMessage());
        }
    }
    elseif ($type === 'deny_order') {
        // Rider denies an assigned order
        $orderId = $data['order_id'];
        error_log('[DENY_ORDER] Request: order_id=' . $orderId . ', rider_id=' . $_SESSION['user_id']);

        if ($_SESSION['role'] !== 'rider') {
            http_response_code(401);
            ob_clean();
            echo json_encode(['error' => 'Only riders can deny orders']);
            exit;
        }

        $riderId = $_SESSION['user_id'];

        try {
            // Check order is assigned to this rider
            $checkStmt = $pdo->prepare("SELECT id, status, rider_id FROM orders WHERE id = ? AND rider_id = ?");
            $checkStmt->execute([$orderId, $riderId]);
            $order = $checkStmt->fetch();

            if (!$order) {
                http_response_code(404);
                ob_clean();
                echo json_encode(['error' => 'Order not found or not assigned to you']);
                error_log('[DENY_ORDER] Order not found');
                exit;
            }

            if ($order['status'] !== 'assigned') {
                http_response_code(400);
                ob_clean();
                echo json_encode(['error' => 'Only assigned orders can be denied']);
                exit;
            }

            // Update status to rejected and clear rider_id
            $stmt = $pdo->prepare("UPDATE orders SET status = 'rejected', rider_id = NULL WHERE id = ?");
            $stmt->execute([$orderId]);

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Order rejected successfully']);
            error_log('[DENY_ORDER] Order ' . $orderId . ' rejected by rider ' . $riderId);
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            error_log('[DENY_ORDER] Database error: ' . $e->getMessage());
        }
    }
    elseif ($type === 'create_user') {
        // Admin creating riders or other admins
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(401);
            ob_clean();
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';

        if (!$name || !$email || !$password || !$role) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Name, email, password and role are required']);
            exit;
        }

        try {
            // Check email uniqueness
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                http_response_code(409);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Email already in use']);
                exit;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    elseif ($type === 'update_user' && $_SESSION['role'] === 'admin') {
        $id = $data['id'];
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $role = $data['role'] ?? '';
        $password = $data['password'] ?? '';

        if (!$id || !$name || !$email || !$role) {
            http_response_code(400);
            ob_clean();
            die(json_encode(['success' => false, 'message' => 'Missing required fields']));
        }

        try {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $hashed, $id]);
            }
            else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $id]);
            }
            ob_clean();
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($type === 'delete_user' && $_SESSION['role'] === 'admin') {
        $id = $data['id'];
        if (!$id) {
            http_response_code(400);
            ob_clean();
            die(json_encode(['success' => false, 'message' => 'ID required']));
        }

        if ($id == $_SESSION['user_id']) {
            http_response_code(403);
            ob_clean();
            die(json_encode(['success' => false, 'message' => 'Cannot delete yourself']));
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            ob_clean();
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>