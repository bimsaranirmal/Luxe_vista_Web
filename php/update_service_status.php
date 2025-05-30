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

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['service_id']) && isset($input['is_active'])) {
    if (!isset($conn) || $conn->connect_error) {
        $response['message'] = 'Database connection error.';
        error_log("DB connection error in update_service_status.php: " . ($conn ? $conn->connect_error : 'N/A'));
        echo json_encode($response);
        exit;
    }

    $service_id = (int)$input['service_id'];
    $is_active = (int)$input['is_active'] === 1 ? 1 : 0; // Ensure it's 0 or 1

    $stmt = $conn->prepare("UPDATE services SET is_active = ? WHERE service_id = ?");
    $stmt->bind_param("ii", $is_active, $service_id);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Service status updated successfully.';
    } else {
        $response['message'] = 'Failed to update service status: ' . $stmt->error;
        error_log("SQL Execute Error in update_service_status.php: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
} else {
    $response['message'] = 'Invalid request or missing parameters.';
}

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in update_service_status.php: " . $accidentalOutput);
}
echo json_encode($response);
?>