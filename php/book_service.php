<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

session_start();
require_once 'config.php';

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Retrieve posted values
$service_id      = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$user_email      = isset($_POST['user_email']) ? trim($_POST['user_email']) : '';
$price_at_booking= isset($_POST['price_at_booking']) ? floatval($_POST['price_at_booking']) : 0.0;
$booking_date    = isset($_POST['booking_date']) ? trim($_POST['booking_date']) : '';
$start_time      = isset($_POST['start_time']) ? trim($_POST['start_time']) : '';
// If your form sends the service name as a hidden input, use its name accordingly. 
// For this example, we're assuming a hidden field name "service_name" is sent.
$service_name    = isset($_POST['service_name']) ? trim($_POST['service_name']) : '';

// Validate required fields
if (empty($service_id) || empty($user_email) || empty($price_at_booking) || empty($booking_date) || empty($start_time) || empty($service_name)) {
    $response['message'] = 'Missing required booking information.';
    echo json_encode($response);
    exit;
}

// Retrieve user_id from session if available; otherwise use 0 or handle as needed.
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Prepare SQL to insert booking details into service_bookings table.
// Payment status defaults to 'pending' and booking_status to 'pending_confirmation'
$sql = "INSERT INTO service_bookings (service_id, user_id, user_email, service_name_at_booking, price_at_booking, booking_date, start_time, payment_status, booking_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_confirmation')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $response['message'] = "Database error: " . $conn->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param("iissdss", $service_id, $user_id, $user_email, $service_name, $price_at_booking, $booking_date, $start_time);

if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'Service booking confirmed!';
} else {
    $response['message'] = 'Failed to save booking: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

