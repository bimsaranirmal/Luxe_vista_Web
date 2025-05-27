<?php
// Configure error handling to log errors but not display them, to avoid breaking JSON
error_reporting(E_ALL); // Report all PHP errors.
ini_set('display_errors', 0); // Do not display errors to the browser.
ini_set('log_errors', 1); // Log errors to the server's error log.
// Optional: Specify a custom log file: ini_set('error_log', '/path/to/your/php-error.log');

// Start output buffering
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Ensures $conn is established

$response = ['status' => 'error', 'message' => 'Could not fetch bookings.', 'bookings' => []];

// Admin role check - uncomment and adapt if you have role-based access control
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access. Admin privileges required.';
    echo json_encode($response);
    exit;
}
*/

if (!isset($conn) || !$conn) { // Check if $conn is even set
    $response['message'] = 'Database connection object not found. Check config.php.';
    error_log("Database connection object not initialized in get_all_bookings.php.");
    echo json_encode($response);
    exit;
} elseif ($conn->connect_error) { // Now check for connection errors if $conn is set
    $response['message'] = 'Database connection error.';
    error_log("Database connection error in get_all_bookings.php: " . $conn->connect_error);
    echo json_encode($response);
    exit;
}

$base_sql = "SELECT 
            b.booking_id, 
            b.user_id,
            b.guest_email,
            b.guest_phone,
            b.room_id, 
            b.booking_in_date, 
            b.booking_out_date, 
            b.total_price, 
            b.payment_status, 
            b.booking_status,
            b.booked_at as booking_created_at,
            r.room_type,
            u.name as user_name 
        FROM bookings b
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN users u ON b.user_id = u.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($_GET['keyword'])) {
    $keyword_trim = trim($_GET['keyword']);
    $keyword_like = "%" . $keyword_trim . "%";
    
    // Add conditions for text-based fields
    $text_conditions = "(b.guest_email LIKE ? OR u.name LIKE ? OR r.room_type LIKE ?)";
    $params[] = $keyword_like;
    $params[] = $keyword_like;
    $params[] = $keyword_like;
    $types .= "sss";

    // If keyword is numeric, also check booking_id
    if (is_numeric($keyword_trim)) {
        $conditions[] = "(" . $text_conditions . " OR b.booking_id = ?)";
        $params[] = (int)$keyword_trim; // Add the numeric booking_id to params
        $types .= "i";
    } else {
        $conditions[] = $text_conditions;
    }
}

if (!empty($_GET['status'])) {
    $status = trim($_GET['status']);
    $conditions[] = "b.booking_status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql = $base_sql;
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY b.booking_in_date DESC, b.booked_at DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($types) && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['bookings'][] = $row;
        }
        $response['status'] = 'success';
        $response['message'] = 'Bookings fetched successfully.';
    } else {
        $response['message'] = 'Error fetching results: ' . $stmt->error;
        error_log("SQL Result Error in get_all_bookings.php: " . $stmt->error);
    }
    $stmt->close();
} else {
    $response['message'] = 'Error executing query: ' . $conn->error;
    error_log("SQL Error in get_all_bookings.php: " . $conn->error);
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

// Get any buffered content and clean the buffer
$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    // Log any unexpected output that was captured. This helps find stray echo/print statements.
    error_log("Accidental output captured in get_all_bookings.php: " . $accidentalOutput);
}

echo json_encode($response);
?>