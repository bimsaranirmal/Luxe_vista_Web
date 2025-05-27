<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php';

$response = ['status' => 'error', 'message' => 'Could not fetch user registration stats.', 'data' => []];

// Optional: Admin role check
/* ... */

if (!isset($conn) || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    error_log("DB connection error in get_monthly_user_registration_stats.php: " . ($conn ? $conn->connect_error : 'N/A'));
    echo json_encode($response);
    exit;
}

$selected_year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : null;

if ($selected_year) {
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month_year, COUNT(*) as count 
            FROM users 
            WHERE YEAR(created_at) = ?
            GROUP BY month_year 
            ORDER BY month_year ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();
} else { // Default to last 12 months if no year is specified
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month_year, COUNT(*) as count 
            FROM users 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month_year 
            ORDER BY month_year ASC";
$result = $conn->query($sql);
}

if ($result) {
    $monthly_data = [];
    while ($row = $result->fetch_assoc()) {
        $monthly_data[] = ['month_year' => $row['month_year'], 'count' => (int)$row['count']];
    }
    $response['status'] = 'success';
    $response['data'] = $monthly_data;
    $response['message'] = 'Monthly user registration stats fetched.';
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_monthly_user_registration_stats.php: " . $conn->error);
}
if (isset($stmt)) $stmt->close();

$conn->close();
$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in get_monthly_user_registration_stats.php: " . $accidentalOutput);
}
echo json_encode($response);
?>