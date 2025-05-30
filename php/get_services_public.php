<?php
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'An unknown error occurred.', 'services' => []];

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    error_log("DB connection error in get_services_public.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

$sql = "SELECT service_id, name, description, price, category, icon_class, image_path FROM services WHERE is_active = 1";
$params = [];
$types = "";

// Basic Filtering Examples (can be expanded)
if (!empty($_GET['category'])) {
    $sql .= " AND category LIKE ?";
    $params[] = "%" . $_GET['category'] . "%";
    $types .= "s";
}

if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $sql .= " AND (price IS NULL OR price <= ?)"; // Handle services with no price or price within range
    $params[] = (float)$_GET['max_price'];
    $types .= "d";
}

if (!empty($_GET['keyword'])) {
    $keyword = "%" . $_GET['keyword'] . "%";
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "ss";
}

$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $services_data = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure price is formatted correctly or null
            $row['price'] = $row['price'] !== null ? (float)$row['price'] : null;
            $services_data[] = $row;
        }
        $response['status'] = 'success';
        $response['message'] = 'Services fetched successfully.';
        $response['services'] = $services_data;
    } else {
        $response['message'] = 'Failed to execute statement: ' . $stmt->error;
        error_log("SQL Execute Error in get_services_public.php: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare statement: ' . $conn->error;
    error_log("SQL Prepare Error in get_services_public.php: " . $conn->error);
}

$conn->close();
echo json_encode($response);
?>