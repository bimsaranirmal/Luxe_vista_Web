<?php
require 'config.php';
header('Content-Type: application/json');

// Base SQL query
$sql = "SELECT r.room_id, r.room_type, r.capacity, r.description, r.price_per_night, r.availability 
        FROM rooms r";
$conditions = [];
$params = [];
$param_types = "";
$has_date_filter = false; // Flag to track if date filtering is active

// --- Filtering Options ---
if (!empty($_GET['room_type']) && $_GET['room_type'] !== 'all') {
    $conditions[] = "r.room_type = ?";
    $params[] = $_GET['room_type'];
    $param_types .= "s";
}

if (!empty($_GET['min_capacity'])) {
    $conditions[] = "r.capacity >= ?";
    $params[] = (int)$_GET['min_capacity'];
    $param_types .= "i";
}

if (!empty($_GET['max_price'])) {
    $conditions[] = "r.price_per_night <= ?";
    $params[] = (float)$_GET['max_price'];
    $param_types .= "d";
}

// --- Date Filtering ---
if (!empty($_GET['check_in_date']) && !empty($_GET['check_out_date'])) {
    $check_in_date_str = $_GET['check_in_date'];
    $check_out_date_str = $_GET['check_out_date'];

    // Basic validation for date format and range
    $check_in_dt = DateTime::createFromFormat('Y-m-d', $check_in_date_str);
    $check_out_dt = DateTime::createFromFormat('Y-m-d', $check_out_date_str);

    if (!$check_in_dt || $check_in_dt->format('Y-m-d') !== $check_in_date_str ||
        !$check_out_dt || $check_out_dt->format('Y-m-d') !== $check_out_date_str) {
        echo json_encode(['error' => 'Invalid date format. Please use YYYY-MM-DD.']);
        exit;
    }
    if ($check_out_dt <= $check_in_dt) {
        echo json_encode(['error' => 'Check-out date must be after Check-in date.']);
        exit;
    }

    $has_date_filter = true;
    // Add condition to check for NO overlapping bookings
    // A room is considered booked for the range [booking_in_date, booking_out_date - 1 day]
    // Overlap condition: NewIn < ExistingOut AND NewOut > ExistingIn
    $conditions[] = "NOT EXISTS (
        SELECT 1 FROM bookings b
        WHERE b.room_id = r.room_id
        AND b.booking_status = 'Confirmed' 
        AND b.booking_in_date < ? 
        AND b.booking_out_date > ?
    )";
    $params[] = $check_out_date_str; // Parameter for ExistingOut comparison (NewOut)
    $params[] = $check_in_date_str;  // Parameter for ExistingIn comparison (NewIn)
    $param_types .= "ss";
}

// By default, only show rooms with r.availability = 'Available' 
// UNLESS a date filter is active (which handles availability for the range) 
// OR 'show_all_availability' is checked.
if (empty($_GET['show_all_availability']) && !$has_date_filter) {
    $conditions[] = "r.availability = 'Available'";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY r.price_per_night ASC"; // Example default ordering

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("SQL Prepare Error in view-rooms.php: " . $conn->error . " | SQL: " . $sql);
    echo json_encode(['error' => 'Failed to prepare statement: ' . htmlspecialchars($conn->error)]);
    exit;
}

if (count($params) > 0) {
    // PHP 5.6+ spread operator. For older versions, use call_user_func_array.
    if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
        $stmt->bind_param($param_types, ...$params);
    } else {
        $bind_args = [$param_types];
        foreach ($params as $key => &$param_val) { // Pass by reference
            $bind_args[] = &$param_val;
        }
        unset($param_val); // Unset reference to last element
        call_user_func_array([$stmt, 'bind_param'], $bind_args);
    }
}

if (!$stmt->execute()) {
    error_log("SQL Execute Error in view-rooms.php: " . $stmt->error);
    echo json_encode(['error' => 'Failed to execute statement: ' . htmlspecialchars($stmt->error)]);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$rooms_data = [];

// Prepare statement for fetching images once, outside the loop
$imgSqlPrepared = "SELECT image_path FROM room_images WHERE room_id = ? ORDER BY id ASC LIMIT 1"; // Added ORDER BY id ASC for consistency
$imgStmtPrepared = $conn->prepare($imgSqlPrepared);
if ($imgStmtPrepared === false) {
    error_log("SQL Prepare Error for images in view-rooms.php: " . $conn->error);
    // If image statement fails, script can continue; rooms will use default image.
}

while ($room = $result->fetch_assoc()) {
    $room_id_val = $room['room_id'];
    $room['main_image'] = 'images/default-room.jpg'; // Set default

    if ($imgStmtPrepared) { // Check if the prepared statement for images is valid
        $imgStmtPrepared->bind_param("s", $room_id_val);
        $imgStmtPrepared->execute();
        $imgResult = $imgStmtPrepared->get_result();
        if ($image = $imgResult->fetch_assoc()) {
            $room['main_image'] = $image['image_path'];
        }
    }
    $rooms_data[] = $room;
}
$stmt->close();
if ($imgStmtPrepared) { // Close the image prepared statement if it was created
    $imgStmtPrepared->close();
}
$conn->close();

echo json_encode($rooms_data);
?>
