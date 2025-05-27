<?php
// Configure error handling to log errors but not display them, to avoid breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Optional: Specify a custom log file: ini_set('error_log', '/path/to/your/php-error.log');

// Start output buffering
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Ensures $conn is established

$response = ['status' => 'error', 'message' => 'Could not fetch contact messages.', 'messages' => []];

// Optional: Add admin role check if this endpoint should be restricted
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access. Admin privileges required.';
    echo json_encode($response);
    exit;
}
*/

if (!isset($conn) || !$conn) {
    $response['message'] = 'Database connection object not found. Check config.php.';
    error_log("Database connection object not initialized in get_all_contact_messages.php.");
    echo json_encode($response);
    exit;
} elseif ($conn->connect_error) {
    $response['message'] = 'Database connection error: ' . $conn->connect_error;
    error_log("Database connection error in get_all_contact_messages.php: " . $conn->connect_error);
    echo json_encode($response);
    exit;
}

$base_sql = "SELECT cm.id, cm.user_id, cm.user_email, cm.subject, cm.message, cm.submitted_at, 
               cm.admin_reply_message, cm.admin_replied_at, cm.replied_by_admin_id, u.name as user_name
        FROM contact_messages cm
        LEFT JOIN users u ON cm.user_id = u.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($_GET['keyword'])) {
    $keyword = "%" . trim($_GET['keyword']) . "%";
    $conditions[] = "(cm.subject LIKE ? OR cm.message LIKE ?)";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "ss";
}

if (!empty($_GET['sender'])) {
    $sender_email = trim($_GET['sender']);
    $conditions[] = "cm.user_email = ?";
    $params[] = $sender_email;
    $types .= "s";
}

if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'replied') {
        $conditions[] = "cm.admin_reply_message IS NOT NULL";
    } elseif ($_GET['status'] === 'unreplied') {
        $conditions[] = "cm.admin_reply_message IS NULL";
    }
}

$sql = $base_sql;
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY cm.submitted_at DESC";


$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($types) && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['messages'][] = $row;
        }
        $response['status'] = 'success';
        $response['message'] = 'Contact messages fetched successfully.';
    } else {
        $response['message'] = 'Error fetching results: ' . $stmt->error;
        error_log("SQL Result Error in get_all_contact_messages.php: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_all_contact_messages.php: " . $conn->error);
}


$conn->close();
$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output captured in get_all_contact_messages.php: " . $accidentalOutput);
}
echo json_encode($response);
?>