<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Ensures $conn is established

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

// Admin authentication/authorization check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access. Admin privileges required.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? null); // Optional
    $discount_percentage = !empty($_POST['discount_percentage']) ? (float)$_POST['discount_percentage'] : null;
    $discount_amount = !empty($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : null;
    $promo_code = !empty($_POST['promo_code']) ? trim($_POST['promo_code']) : null; // Optional
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

    // Validation
    if (empty($title)) {
        $response['message'] = 'Promotion title is required.';
        echo json_encode($response);
        exit;
    }
    if ($discount_percentage === null && $discount_amount === null) {
        $response['message'] = 'Either discount percentage or amount must be provided.';
        echo json_encode($response);
        exit;
    }
    if ($discount_percentage !== null && $discount_amount !== null) {
        $response['message'] = 'Provide either a discount percentage OR a fixed amount, not both.';
        echo json_encode($response);
        exit;
    }
    if ($discount_percentage !== null && ($discount_percentage <= 0 || $discount_percentage > 100)) {
        $response['message'] = 'Discount percentage must be between 0 and 100.';
        echo json_encode($response);
        exit;
    }
    if ($discount_amount !== null && $discount_amount <= 0) {
        $response['message'] = 'Discount amount must be greater than 0.';
        echo json_encode($response);
        exit;
    }
    if (empty($start_date) || empty($end_date)) {
        $response['message'] = 'Start date and end date are required.';
        echo json_encode($response);
        exit;
    }
    if (strtotime($end_date) < strtotime($start_date)) {
        $response['message'] = 'End date cannot be before the start date.';
        echo json_encode($response);
        exit;
    }
    // Promo code uniqueness check (if provided)
    if ($promo_code !== null) {
        $stmt_check = $conn->prepare("SELECT promotion_id FROM promotions WHERE promo_code = ?");
        $stmt_check->bind_param("s", $promo_code);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $response['message'] = 'This promo code already exists. Please choose a unique one.';
            $stmt_check->close();
            echo json_encode($response);
            exit;
        }
        $stmt_check->close();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO promotions (title, description, discount_percentage, discount_amount, promo_code, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddsssi", $title, $description, $discount_percentage, $discount_amount, $promo_code, $start_date, $end_date, $is_active);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Promotion added successfully!';
        } else {
            throw new Exception('Database execute statement failed: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        error_log('Add promotion error: ' . $e->getMessage());
    }
    $conn->close();
} else {
    $response['message'] = 'Invalid request method.';
}

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output captured in add_promotion.php: " . $accidentalOutput);
}
echo json_encode($response);
?>