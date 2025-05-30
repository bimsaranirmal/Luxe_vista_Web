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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['service_id'])) {
    if (!isset($conn) || $conn->connect_error) {
        $response['message'] = 'Database connection error.';
        error_log("DB connection error in delete_service.php: " . ($conn ? $conn->connect_error : 'N/A'));
        echo json_encode($response);
        exit;
    }

    $service_id = (int)$input['service_id'];

    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Service deleted successfully.';
    } else {
        $response['message'] = 'Failed to delete service or service not found. Error: ' . $stmt->error;
        error_log("SQL Execute Error in delete_service.php: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
} else {
    $response['message'] = 'Invalid request or missing service ID.';
}

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in delete_service.php: " . $accidentalOutput);
}
echo json_encode($response);
?>