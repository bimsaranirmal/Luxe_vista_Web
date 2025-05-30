<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep 0 for production, 1 for debugging
ini_set('log_errors', 1);
ob_start(); // Start output buffering

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'An unknown error occurred.', 'bookings' => []];

// Optional: Admin role check
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}
*/

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    error_log("DB connection error in get_all_service_bookings.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

$base_sql = "SELECT sb.booking_id, sb.user_id, sb.service_id, sb.service_name_at_booking, 
                    sb.price_at_booking, sb.booking_date, sb.start_time, 
                    sb.payment_status, sb.booking_status, sb.booked_at,
                    u.name as user_name, u.email as user_email
             FROM service_bookings sb
             LEFT JOIN users u ON sb.user_id = u.id";

$conditions = [];
$params = [];
$param_types = "";

// Filter by keyword (service name, user name/email, booking ID)
if (!empty($_GET['keyword'])) {
    $keyword_like = "%" . $_GET['keyword'] . "%";
    $conditions[] = "(sb.service_name_at_booking LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR sb.booking_id LIKE ?)";
    array_push($params, $keyword_like, $keyword_like, $keyword_like, $keyword_like);
    $param_types .= "ssss";
}

// Filter by booking status
if (!empty($_GET['booking_status'])) {
    $conditions[] = "sb.booking_status = ?";
    $params[] = $_GET['booking_status'];
    $param_types .= "s";
}

// Filter by payment status
if (!empty($_GET['payment_status'])) {
    $conditions[] = "sb.payment_status = ?";
    $params[] = $_GET['payment_status'];
    $param_types .= "s";
}

// Filter by date range (booking_date)
if (!empty($_GET['date_from'])) {
    $conditions[] = "sb.booking_date >= ?";
    $params[] = $_GET['date_from'];
    $param_types .= "s";
}
if (!empty($_GET['date_to'])) {
    $conditions[] = "sb.booking_date <= ?";
    $params[] = $_GET['date_to'];
    $param_types .= "s";
}

if (count($conditions) > 0) {
    $base_sql .= " WHERE " . implode(" AND ", $conditions);
}

$base_sql .= " ORDER BY sb.booking_date DESC, sb.start_time DESC";

$stmt = $conn->prepare($base_sql);

if ($stmt === false) {
    $response['message'] = 'Failed to prepare statement: ' . $conn->error;
    error_log("SQL Prepare Error in get_all_service_bookings.php: " . $conn->error . " | SQL: " . $base_sql);
} else {
    if (count($params) > 0) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $bookings_data = [];
    while ($row = $result->fetch_assoc()) {
        $bookings_data[] = $row;
    }
    $response['status'] = 'success';
    $response['bookings'] = $bookings_data;
    $response['message'] = count($bookings_data) > 0 ? 'Service bookings fetched successfully.' : 'No service bookings found matching your criteria.';
    if ($stmt) $stmt->close();
}

$conn->close();
ob_end_clean(); // Clean (erase) the output buffer and turn off output buffering
echo json_encode($response);
?>