<?php
require 'config.php'; // Includes your database connection ($conn)
header('Content-Type: application/json');

$response = ['status' => 'error', 'count' => 0, 'message' => 'An error occurred.'];

// Check connection status from config.php
if (!$conn || $conn->connect_error) {
    $response['message'] = 'Database connection failed: ' . htmlspecialchars($conn->connect_error);
    echo json_encode($response);
    exit;
}

// Query to count available rooms
$sql = "SELECT COUNT(*) AS available_count FROM rooms WHERE availability = 'Available'";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $response['status'] = 'success';
    $response['count'] = (int)$row['available_count']; // Cast to integer
} else {
    $response['message'] = 'Failed to fetch available room count: ' . htmlspecialchars($conn->error);
}

echo json_encode($response);
?>