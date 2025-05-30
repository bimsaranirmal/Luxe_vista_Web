<?php
// Configure error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser (breaks JSON)
ini_set('log_errors', 1);    // Log errors to server logs
ob_start(); // Start output buffering

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'Could not fetch promotions.', 'promotions' => []];

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
    error_log("DB connection error in get_all_promotions.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

// Fetch all promotions, ordered by end date (soonest ending first) or start date
$base_sql = "SELECT promotion_id, title, description, discount_percentage, discount_amount, promo_code, start_date, end_date, is_active, created_at FROM promotions";
$conditions = [];
$params = [];
$param_types = "";

// Filter by keyword (title, description, promo_code)
if (!empty($_GET['keyword'])) {
    $keyword_like = "%" . $_GET['keyword'] . "%";
    $conditions[] = "(title LIKE ? OR description LIKE ? OR promo_code LIKE ?)";
    array_push($params, $keyword_like, $keyword_like, $keyword_like);
    $param_types .= "sss";
}

// Filter by is_active status (direct column check)
if (isset($_GET['is_active_filter']) && $_GET['is_active_filter'] !== 'all' && $_GET['is_active_filter'] !== '') {
    $conditions[] = "is_active = ?";
    $params[] = (int)$_GET['is_active_filter'];
    $param_types .= "i";
}

// Filter by activity_status (derived from dates and is_active)
if (!empty($_GET['activity_status']) && $_GET['activity_status'] !== 'all') {
    $today = date('Y-m-d');
    switch ($_GET['activity_status']) {
        case 'active_now':
            $conditions[] = "(start_date <= ? AND end_date >= ? AND is_active = 1)";
            $params[] = $today;
            $params[] = $today;
            $param_types .= "ss";
            break;
        case 'upcoming':
            $conditions[] = "(start_date > ? AND is_active = 1)";
            $params[] = $today;
            $param_types .= "s";
            break;
        case 'expired':
            $conditions[] = "(end_date < ? OR is_active = 0)"; // Expired or manually set to inactive
            $params[] = $today;
            $param_types .= "s";
            break;
    }
}

if (!empty($_GET['start_date_after'])) {
    $conditions[] = "start_date >= ?";
    $params[] = $_GET['start_date_after'];
    $param_types .= "s";
}

if (!empty($_GET['end_date_before'])) {
    $conditions[] = "end_date <= ?";
    $params[] = $_GET['end_date_before'];
    $param_types .= "s";
}

$sql = $base_sql;
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY is_active DESC, end_date ASC, start_date ASC, created_at DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['promotions'][] = $row;
    }
    $response['status'] = 'success';
    $response['message'] = count($response['promotions']) > 0 ? 'Promotions fetched successfully.' : 'No promotions found matching your criteria.';
    $stmt->close();
} else {
    $response['message'] = 'Error preparing query: ' . $conn->error;
    error_log("SQL Prepare Error in get_all_promotions.php: " . $conn->error . " | SQL: " . $sql);
}

$conn->close();
$accidentalOutput = ob_get_clean(); // Get any accidental buffered output
if (!empty($accidentalOutput)) {
    error_log("Accidental output in get_all_promotions.php: " . $accidentalOutput);
}
echo json_encode($response);
?>