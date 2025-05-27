<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $phone = isset($_POST["phone"]) ? $_POST["phone"] : null; // Optional field
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate password and confirm password
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Account created successfully!'); window.location.href = '../login.html';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
}
?>