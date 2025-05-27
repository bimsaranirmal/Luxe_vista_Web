<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate password and confirm password
    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
        exit();
    }

    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the receptionist into the database
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'receptionist')");
    $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to register receptionist.']);
    }

    $stmt->close();
}
?>