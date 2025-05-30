<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep 0 for production, 1 for debugging
ini_set('log_errors', 1);
ob_start(); // Start output buffering

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'An unknown error occurred.', 'services' => []];

// Optional: Admin role check (if you want to restrict who can see all services)
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}
*/

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    error_log("DB connection error in get_all_services.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

$base_sql = "SELECT service_id, name, description, price, category, icon_class, image_path, is_active, created_at FROM services";
$conditions = [];
$params = [];
$param_types = "";

// Filter by keyword (name or description)
if (!empty($_GET['keyword'])) {
    $keyword = "%" . $_GET['keyword'] . "%";
    $conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = $keyword;
    $params[] = $keyword;
    $param_types .= "ss";
}

// Filter by category
if (!empty($_GET['category'])) {
    $category = "%" . $_GET['category'] . "%";
    $conditions[] = "category LIKE ?";
    $params[] = $category;
    $param_types .= "s";
}

// Filter by price
if (!empty($_GET['min_price'])) {
    $conditions[] = "price >= ?";
    $params[] = (float)$_GET['min_price'];
    $param_types .= "d";
}
if (!empty($_GET['max_price'])) {
    $conditions[] = "price <= ?";
    $params[] = (float)$_GET['max_price'];
    $param_types .= "d";
}

// Filter by active status
if (isset($_GET['is_active_status']) && $_GET['is_active_status'] !== 'all' && $_GET['is_active_status'] !== '') {
    $conditions[] = "is_active = ?";
    $params[] = (int)$_GET['is_active_status'];
    $param_types .= "i";
}

if (count($conditions) > 0) {
    $base_sql .= " WHERE " . implode(" AND ", $conditions);
}

$base_sql .= " ORDER BY is_active DESC, name ASC";

$stmt = $conn->prepare($base_sql);

if ($stmt === false) {
    $response['message'] = 'Failed to prepare statement: ' . $conn->error;
    error_log("SQL Prepare Error in get_all_services.php: " . $conn->error . " | SQL: " . $base_sql);
} else {
    if (count($params) > 0) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    $response['status'] = 'success';
    $response['services'] = $services;
    $response['message'] = count($services) > 0 ? 'Services fetched successfully.' : 'No services found matching your criteria.';
    if ($stmt) $stmt->close();
}

$conn->close();

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in get_all_services.php: " . $accidentalOutput);
}

echo json_encode($response);
?>