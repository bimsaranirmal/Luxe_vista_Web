<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Ensures $conn is established

$response = ['status' => 'error', 'message' => 'Could not fetch booking count.', 'count' => 0];

// Optional: Add admin role check if this endpoint should be restricted
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     $response['message'] = 'Unauthorized access.';
//     echo json_encode($response);
//     exit;
// }

if (!$conn || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    if ($conn) {
        error_log("Database connection error in get_active_booking_count.php: " . $conn->connect_error);
    }
    echo json_encode($response);
    exit;
}

$sql = "SELECT COUNT(*) as active_count FROM bookings WHERE booking_status = 'Confirmed' OR booking_status = 'Upcoming'";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    $response['status'] = 'success';
    $response['count'] = (int)$row['active_count'];
    $response['message'] = 'Active booking count fetched successfully.';
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_active_booking_count.php: " . $conn->error);
}

$conn->close();
echo json_encode($response);
?>