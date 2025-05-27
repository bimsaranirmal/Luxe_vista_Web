<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php';

$response = ['status' => 'error', 'message' => 'Could not fetch booking cancellation stats.', 'data' => []];

// Optional: Admin role check
/* ... */

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    error_log("DB connection error in get_booking_cancellation_stats.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

$sql = "SELECT booking_status, COUNT(*) as count 
        FROM bookings 
        GROUP BY booking_status";
$result = $conn->query($sql);

if ($result) {
    $status_counts = [];
    $total_bookings = 0;
    while ($row = $result->fetch_assoc()) {
        $status_counts[$row['booking_status']] = (int)$row['count'];
        $total_bookings += (int)$row['count'];
    }
    
    // Prepare data for pie chart (e.g., Confirmed, Cancelled, Other)
    $confirmed_count = $status_counts['Confirmed'] ?? 0;
    $cancelled_count = $status_counts['Cancelled'] ?? 0;
    // You can sum up other relevant statuses or group them
    $other_statuses_count = $total_bookings - $confirmed_count - $cancelled_count;
    
    $response['status'] = 'success';
    $response['data'] = [
        'confirmed' => $confirmed_count,
        'cancelled' => $cancelled_count,
        'other' => $other_statuses_count, // Represents all statuses other than Confirmed/Cancelled
        'total' => $total_bookings
    ];
    $response['message'] = 'Booking cancellation stats fetched.';
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_booking_cancellation_stats.php: " . $conn->error);
}

$conn->close();
$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in get_booking_cancellation_stats.php: " . $accidentalOutput);
}
echo json_encode($response);
?>