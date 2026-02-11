<?php
require_once 'db.php';

$userId = 4; // from dump
$total = 2000;
$address = "Test Address";

echo "Testing DB Connection...\n";

try {
    $pdo->beginTransaction();
    echo "Transaction started.\n";

    $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_price, delivery_address) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $total, $address]);
    $orderId = $pdo->lastInsertId();
    echo "Order inserted. ID: $orderId\n";

    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    // Using product ID 1 which exists in dump
    $stmtItem->execute([$orderId, 1, 1, 2000]);
    echo "Order item inserted.\n";

    $pdo->commit();
    echo "Success! Transaction committed.\n";
}
catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Failed: " . $e->getMessage() . "\n";
}
?>