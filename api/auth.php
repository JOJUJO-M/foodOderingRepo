<?php
// api/auth.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');
$enable_json_errors = true; // Tell db.php to output JSON on error

require_once '../db.php';

if (isset($db_error)) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Database Error: " . $db_error]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'register') {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = isset($data['role']) ? $data['role'] : 'customer'; // Default to customer

        if (!$name || !$email || !$password) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Name, email and password are required']);
            exit;
        }

        // Hash password before storing
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Registration successful']);
        }
        catch (PDOException $e) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Registration Error: ' . $e->getMessage()]);
        }
    }
    elseif ($action === 'login') {
        $email = $data['email'];
        $password = $data['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $login_ok = false;
        if ($user) {
            // Primary: verify hashed password
            if (password_verify($password, $user['password'])) {
                $login_ok = true;
            }
            elseif ($password === $user['password']) {
                // Legacy plaintext password stored — accept and upgrade to hashed
                try {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->execute([$newHash, $user['id']]);
                }
                catch (Exception $e) {
                // Ignore update failure, allow login if plaintext matched
                }
                $login_ok = true;
            }
        }

        if ($login_ok) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            ob_clean();
            echo json_encode(['success' => true, 'role' => $user['role'], 'redirect' => "dashboard_{$user['role']}.php"]);
        }
        else {
            http_response_code(401);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }
    elseif ($action === 'logout') {
        session_destroy();
        ob_clean();
        echo json_encode(['success' => true]);
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'current_user') {
        if (isset($_SESSION['user_id'])) {
            ob_clean();
            echo json_encode([
                'id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'name' => $_SESSION['name']
            ]);
        }
        else {
            http_response_code(401);
            ob_clean();
            echo json_encode(['error' => 'Not logged in']);
        }
    }
}
