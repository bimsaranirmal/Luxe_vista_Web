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

$promotion_id = isset($input['promotion_id']) ? filter_var($input['promotion_id'], FILTER_VALIDATE_INT) : null;
$new_status = isset($input['is_active']) ? filter_var($input['is_active'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]) : null;

if (!$promotion_id || $new_status === null) {
    $response['message'] = 'Invalid input provided.';
    echo json_encode($response);
    exit;
}

if ($conn->connect_error) {
    $response['message'] = 'Database connection error: ' . $conn->connect_error;
    error_log("DB Connection Error in update_promotion_status.php: " . $conn->connect_error);
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("UPDATE promotions SET is_active = ? WHERE promotion_id = ?");
if ($stmt) {
    $stmt->bind_param("ii", $new_status, $promotion_id);
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Promotion status updated successfully.';
    } else {
        $response['message'] = 'Failed to update promotion status: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare statement: ' . $conn->error;
}

$conn->close();
ob_end_clean();
echo json_encode($response);
?>