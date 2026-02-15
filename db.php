<?php
// db.php - MySQL Database Connection & Initialization
error_reporting(0);

// Database Configuration
$host = 'localhost';
$dbname = 'food_delivery';
$username = 'fooduser';
$password = 'Jojujo@2020*()';

try {
    // First connect to MySQL server to ensure DB exists
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize Schema
    $commands = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('customer', 'admin', 'rider') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) UNIQUE NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(255),
            category_id INT,
            FOREIGN KEY(category_id) REFERENCES categories(id)
        )",
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            rider_id INT,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            total_price DECIMAL(10,2) NOT NULL,
            delivery_address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(customer_id) REFERENCES users(id),
            FOREIGN KEY(rider_id) REFERENCES users(id)
        )",
        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY(product_id) REFERENCES products(id)
        )"
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }

    // Seed default admin if none exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin@food.com', $pass, 'admin']);
    }

} catch (PDOException $e) {
    if (isset($enable_json_errors) && $enable_json_errors) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => "Database Connection Error: " . $e->getMessage()]));
    }
    // Fallback for non-API context
    $db_error = $e->getMessage();
}

