<?php
// Configure error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser (breaks JSON)
ini_set('log_errors', 1);    // Log errors to server logs
ob_start(); // Start output buffering

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'Could not fetch room stats.', 'data' => ['Available' => 0, 'Not Available' => 0]];

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
    error_log("DB connection error in get_room_availability_stats.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

$sql = "SELECT availability, COUNT(*) as count FROM rooms GROUP BY availability";
$result = $conn->query($sql);

if ($result) {
    $stats = ['Available' => 0, 'Not Available' => 0]; // Initialize with expected keys
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['availability'], $stats)) {
            $stats[$row['availability']] = (int)$row['count'];
        }
    }
    $response['status'] = 'success';
    $response['data'] = $stats;
    $response['message'] = 'Room availability stats fetched.';
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_room_availability_stats.php: " . $conn->error);
}

$conn->close();
$accidentalOutput = ob_get_clean(); // Get any accidental buffered output
if (!empty($accidentalOutput)) {
    error_log("Accidental output in get_room_availability_stats.php: " . $accidentalOutput);
}
echo json_encode($response);
?>