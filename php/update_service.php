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
    $response['message'] = 'Unauthorized access. Admin privileges required.';
    echo json_encode($response);
    exit;
}
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($conn) || $conn->connect_error) {
        $response['message'] = 'Database connection error.';
        error_log("DB connection error in update_service.php: " . ($conn ? $conn->connect_error : 'N/A'));
        echo json_encode($response);
        exit;
    }

    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
    $category = !empty($_POST['category']) ? trim($_POST['category']) : null;
    $icon_class = !empty($_POST['icon_class']) ? trim($_POST['icon_class']) : null;
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

    if (empty($name) || empty($description) || $service_id <= 0) {
        $response['message'] = 'Service ID, name, and description are required.';
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE services SET name = ?, description = ?, price = ?, category = ?, icon_class = ?, is_active = ? WHERE service_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters: s = string, d = double, i = integer
        $stmt->bind_param("ssdssii", $name, $description, $price, $category, $icon_class, $is_active, $service_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['status'] = 'success';
                $response['message'] = 'Service updated successfully!';
            } else {
                $response['status'] = 'info'; // Or success, if no change is not an error
                $response['message'] = 'No changes were made to the service.';
            }
        } else {
            $response['message'] = 'Failed to update service: ' . $stmt->error;
            error_log("SQL Execute Error in update_service.php: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Failed to prepare statement: ' . $conn->error;
        error_log("SQL Prepare Error in update_service.php: " . $conn->error);
    }
    $conn->close();
} else {
    $response['message'] = 'Invalid request method.';
}

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in update_service.php: " . $accidentalOutput);
}
echo json_encode($response);
?>