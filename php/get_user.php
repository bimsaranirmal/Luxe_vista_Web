<?php
require 'config.php';
header('Content-Type: application/json');

if (isset($_GET['id']) && isset($_GET['role'])) {
    $id = $_GET['id'];
    $role = $_GET['role'];

    $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ? AND role = ?");
    $stmt->bind_param("is", $id, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>