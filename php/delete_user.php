<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'];
    $role = $data['role'];

    // Validate input
    if (empty($id) || empty($role)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        exit();
    }

    // Delete the user from the database
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = ?");
    $stmt->bind_param("is", $id, $role);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>