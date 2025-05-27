<?php
// Configure error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser (breaks JSON)
ini_set('log_errors', 1);    // Log errors to server logs
ob_start(); // Start output buffering

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'Could not fetch reviews.', 'reviews' => []];

// Optional: Admin role check
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access. Admin privileges required.';
    echo json_encode($response);
    exit;
}
*/

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    error_log("DB connection error in get_all_reviews.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

// Assumes your reviews table has columns: id, guest_name, rating, review_text, created_at
$sql = "SELECT id, guest_name, rating, review_text, created_at 
        FROM reviews 
        ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['reviews'][] = $row;
    }
    $response['status'] = 'success';
    $response['message'] = 'Reviews fetched successfully.';
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_all_reviews.php: " . $conn->error);
}

$conn->close();
$accidentalOutput = ob_get_clean(); // Get any accidental buffered output
if (!empty($accidentalOutput)) {
    error_log("Accidental output in get_all_reviews.php: " . $accidentalOutput);
}
echo json_encode($response);
?>