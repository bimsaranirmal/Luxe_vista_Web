<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Optional: Admin role check
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$booking_id = isset($input['booking_id']) ? filter_var($input['booking_id'], FILTER_VALIDATE_INT) : null;
$new_status = isset($input['new_status']) ? trim($input['new_status']) : null;

// Validate inputs
$allowed_statuses = ['confirmed', 'cancelled', 'completed', 'pending_confirmation']; // Add 'pending_confirmation' if admin can revert
if (!$booking_id) {
    $response['message'] = 'Invalid Booking ID.';
    echo json_encode($response);
    exit;
}
if (empty($new_status) || !in_array($new_status, $allowed_statuses)) {
    $response['message'] = 'Invalid new status provided. Allowed: ' . implode(', ', $allowed_statuses);
    echo json_encode($response);
    exit;
}

if ($conn->connect_error) {
    $response['message'] = 'Database connection error: ' . $conn->connect_error;
    error_log("DB Connection Error in update_service_booking_status.php: " . $conn->connect_error);
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("UPDATE service_bookings SET booking_status = ? WHERE booking_id = ?");
if ($stmt) {
    $stmt->bind_param("si", $new_status, $booking_id);
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Booking status updated successfully to ' . htmlspecialchars($new_status) . '.';
    } else {
        $response['message'] = 'Failed to update booking status: ' . $stmt->error;
        error_log("SQL Execute Error in update_service_booking_status.php: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare statement: ' . $conn->error;
    error_log("SQL Prepare Error in update_service_booking_status.php: " . $conn->error);
}

$conn->close();
ob_end_clean();
echo json_encode($response);
?>