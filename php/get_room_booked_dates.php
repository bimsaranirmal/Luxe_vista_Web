<?php
require 'config.php'; // Includes $conn
header('Content-Type: application/json');

$response = ['status' => 'error', 'booked_dates' => [], 'message' => 'An error occurred.'];

if (!isset($_GET['room_id']) || empty($_GET['room_id'])) {
    $response['message'] = 'Room ID is required.';
    echo json_encode($response);
    exit;
}

$room_id = $_GET['room_id'];
$booked_dates_list = [];

if ($conn && !$conn->connect_error) {
    // Fetch confirmed bookings for the given room_id
    $stmt = $conn->prepare("SELECT booking_in_date, booking_out_date FROM bookings WHERE room_id = ? AND booking_status = 'Confirmed'");
    if ($stmt) {
        $stmt->bind_param("s", $room_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($booking_row = $result->fetch_assoc()) {
                $current_date = new DateTime($booking_row['booking_in_date']);
                $end_date = new DateTime($booking_row['booking_out_date']); // Room is free ON this day

                // Add all dates from check-in up to (but not including) check-out
                while ($current_date < $end_date) {
                    $booked_dates_list[] = $current_date->format('Y-m-d');
                    $current_date->modify('+1 day');
                }
            }
            $response['status'] = 'success';
            $response['booked_dates'] = array_unique($booked_dates_list); // Ensure no duplicate dates
            $response['message'] = 'Booked dates fetched successfully.';
        } else {
            $response['message'] = 'Failed to execute statement: ' . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Failed to prepare statement: ' . htmlspecialchars($conn->error);
    }
    $conn->close();
} else {
    $response['message'] = 'Database connection error.';
}

echo json_encode($response);
?>