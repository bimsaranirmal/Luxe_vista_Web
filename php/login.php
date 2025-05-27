<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Basic validation for presence of email and password
    if (!isset($_POST["email"]) || !isset($_POST["password"])) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit();
    }

    $email = trim($_POST["email"]); // Trim whitespace
    $password = $_POST["password"]; // Password should not be trimmed before verification

    if ($email === 'admin@gmail.com' && $password === 'admin123') {
        $_SESSION['user_id'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['user_name'] = 'Administrator'; // Add a name for the admin
        $_SESSION['login_method'] = 'local_admin'; // Differentiate admin login
        echo json_encode(['status' => 'success', 'redirect' => 'admin-sidebar.html']);
        exit();
    }

    // Check database connection from config.php
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection error: ' . htmlspecialchars($conn->connect_error)]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, email, password, role, name FROM users WHERE email = ?");
    if (!$stmt) {
        // Log detailed error: error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again later.']); // User-friendly message
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'] ?? $email; // Store user's name or email
        $_SESSION['login_method'] = 'local_user';

        if ($user['role'] === 'receptionist') {
            echo json_encode(['status' => 'success', 'redirect' => 'reception-panel.html']);
        } else {
            echo json_encode(['status' => 'success', 'redirect' => 'dashboard.html']);
        }
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }
    $stmt->close();
} else {
    // Handle cases where the request method is not POST
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>