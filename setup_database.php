<?php
/**
 * Database Setup Script
 * This script imports the SQL file and seeds the database with sample data
 * Run this once during initial setup
 */

// Database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'food_ordering';

echo "=== Food Delivery Database Setup ===\n\n";

// Connect to MySQL server
$connection = new mysqli($host, $user, $password);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error . "\n");
}

echo "[✓] Connected to MySQL server\n";

// Read the SQL file
$sqlFile = __DIR__ . '/food_ordering.sql';

if (!file_exists($sqlFile)) {
    die("[✗] SQL file not found: $sqlFile\n");
}

$sqlScript = file_get_contents($sqlFile);
echo "[✓] SQL file loaded\n";

// Split the SQL script by statements (handling GO or ;)
$queries = array_filter(array_map('trim', explode(';', $sqlScript)), function ($q) {
    return !empty($q) && strpos($q, '--') !== 0;
});

$successCount = 0;
$errorCount = 0;

// Execute each query
foreach ($queries as $query) {
    if (trim($query) !== '') {
        if ($connection->multi_query($query)) {
            do {
                if ($result = $connection->store_result()) {
                    $result->free();
                }
            } while ($connection->more_results() && $connection->next_result());

            $successCount++;
        }
        else {
            echo "[✗] Error executing query: " . $connection->error . "\n";
            echo "Query: " . substr($query, 0, 50) . "...\n";
            $errorCount++;
        }
    }
}

echo "\n[✓] Database setup completed!\n";
echo "    Successful queries: $successCount\n";
if ($errorCount > 0) {
    echo "    Failed queries: $errorCount\n";
}

// Verify data
$connection->select_db($database);
$tables = [
    'users' => 'SELECT COUNT(*) as count FROM users',
    'categories' => 'SELECT COUNT(*) as count FROM categories',
    'products' => 'SELECT COUNT(*) as count FROM products',
    'orders' => 'SELECT COUNT(*) as count FROM orders',
    'order_items' => 'SELECT COUNT(*) as count FROM order_items'
];

echo "\n=== Data Summary ===\n";
foreach ($tables as $table => $query) {
    $result = $connection->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "$table: " . $row['count'] . " records\n";
    }
}

echo "\n=== Login Credentials ===\n";
echo "Admin Email: admin@food.com\n";
echo "Admin Password: admin123\n";
echo "\nSample Customer Accounts:\n";
$result = $connection->query("SELECT email FROM users WHERE role = 'customer' LIMIT 4");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['email'] . " (password: password123)\n";
    }
}

$connection->close();

echo "\n[✓] Setup complete! Open http://localhost/food_ordering/ to get started.\n";
?>