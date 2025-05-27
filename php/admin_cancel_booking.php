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
    $booking_id_to_cancel = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

    if (empty($booking_id_to_cancel) || $booking_id_to_cancel <= 0) {
        $response['message'] = 'Invalid Booking ID provided.';
        echo json_encode($response);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Check current status to avoid cancelling already cancelled/completed bookings (optional but good practice)
        $stmt_check = $conn->prepare("SELECT booking_status FROM bookings WHERE booking_id = ?");
        if (!$stmt_check) throw new Exception("DB error (check status): " . $conn->error);
        $stmt_check->bind_param("i", $booking_id_to_cancel);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $current_booking = $result_check->fetch_assoc();
        $stmt_check->close();

        if (!$current_booking) {
            throw new Exception("Booking not found.");
        }

        if ($current_booking['booking_status'] === 'Cancelled' || $current_booking['booking_status'] === 'Completed') {
            throw new Exception("Booking is already '" . $current_booking['booking_status'] . "' and cannot be cancelled again.");
        }

        // Update booking status to 'Cancelled'
        $stmt_update = $conn->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = ?");
        if (!$stmt_update) throw new Exception("DB error (update status): " . $conn->error);
        
        $stmt_update->bind_param("i", $booking_id_to_cancel);

        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            $conn->commit();
            $response['status'] = 'success';
            $response['message'] = 'Booking #' . $booking_id_to_cancel . ' has been successfully cancelled by admin.';
        } else {
            $conn->rollback(); // Rollback if no rows affected or execute failed
            throw new Exception("Could not cancel the booking. It might have been already cancelled or an error occurred.");
        }
        $stmt_update->close();
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
        error_log("Admin Booking Cancellation Error (Booking ID: $booking_id_to_cancel, Admin ID: {$_SESSION['user_id']}): " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output captured in admin_cancel_booking.php: " . $accidentalOutput);
}
echo json_encode($response);
?>