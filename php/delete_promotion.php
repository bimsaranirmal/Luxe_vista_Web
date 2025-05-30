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

if (!$promotion_id) {
    $response['message'] = 'Invalid Promotion ID.';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("DELETE FROM promotions WHERE promotion_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $promotion_id);
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Promotion deleted successfully.';
    } else {
        $response['message'] = 'Failed to delete promotion: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare statement: ' . $conn->error;
}

$conn->close();
ob_end_clean();
echo json_encode($response);
?>