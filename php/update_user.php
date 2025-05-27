<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"];
    $role = $_POST["role"];
    $name = $_POST["name"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = ?");
    $stmt->bind_param("sssis", $name, $email, $phone, $id, $role);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update user details.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>