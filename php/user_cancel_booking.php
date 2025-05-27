<?php
session_start();
header('Content-Type: application/json');
require 'config.php'; // Includes $conn

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in. Please login to manage your bookings.';
    echo json_encode($response);
    exit;
}

// Ensure the user_id from session is numeric for this operation
if (!is_numeric($_SESSION['user_id'])) {
    $response['message'] = 'Invalid user session for this action.';
    echo json_encode($response);
    exit;
}
$current_user_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $booking_id_to_cancel = $_POST['booking_id'] ?? null;

    if ($action !== 'user_direct_cancel') {
        $response['message'] = 'Invalid action specified.';
        echo json_encode($response);
        exit;
    }

    if (empty($booking_id_to_cancel) || !is_numeric($booking_id_to_cancel)) {
        $response['message'] = 'Valid Booking ID is required for cancellation.';
        echo json_encode($response);
        exit;
    }
    $booking_id_to_cancel = (int)$booking_id_to_cancel;

    $conn->begin_transaction();
    try {
        // Fetch booking to verify ownership and eligibility
        $stmt_fetch = $conn->prepare("SELECT user_id, booking_in_date, booking_status FROM bookings WHERE booking_id = ?");
        if (!$stmt_fetch) {
            throw new Exception("Database error (fetch booking): " . $conn->error);
        }
        
        $stmt_fetch->bind_param("i", $booking_id_to_cancel);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        $booking_data = $result_fetch->fetch_assoc();
        $stmt_fetch->close();

        if (!$booking_data) {
            throw new Exception("Booking not found.");
        }

        if ((int)$booking_data['user_id'] !== $current_user_id) {
            throw new Exception("You are not authorized to cancel this booking.");
        }

        $current_booking_status_lower = strtolower($booking_data['booking_status']);
        if ($current_booking_status_lower !== 'confirmed' && $current_booking_status_lower !== 'upcoming') {
            throw new Exception("Only 'Confirmed' or 'Upcoming' bookings can be cancelled. Current status: " . htmlspecialchars($booking_data['booking_status']));
        }

        $check_in_date_obj = new DateTime($booking_data['booking_in_date']);
        $check_in_date_obj->setTime(0,0,0); 

        $comparison_date = new DateTime('today');
        $comparison_date->modify('+7 days'); 

        if ($check_in_date_obj <= $comparison_date) {
            throw new Exception("Booking cancellation can only be done if the check-in date is more than 7 days away.");
        }

        // Update booking status to 'Cancelled'
        $stmt_update = $conn->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = ? AND user_id = ? AND (booking_status = 'Confirmed' OR booking_status = 'Upcoming')");
        if (!$stmt_update) {
            throw new Exception("Database error (update booking): " . $conn->error);
        }
        $stmt_update->bind_param("ii", $booking_id_to_cancel, $current_user_id);
        
        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            $conn->commit();
            $response['status'] = 'success';
            $response['message'] = 'Booking #' . $booking_id_to_cancel . ' has been successfully cancelled.';
        } else {
            $conn->rollback();
            throw new Exception("Could not cancel the booking. It might have been already cancelled or no longer meets the criteria.");
        }
        $stmt_update->close();

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
        error_log("User Booking Cancellation Error (Booking ID: $booking_id_to_cancel, User ID: $current_user_id): " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
echo json_encode($response);
?>