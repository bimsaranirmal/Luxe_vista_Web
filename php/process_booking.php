<?php
// Start output buffering
ob_start();

session_start();

// Set content type to JSON early
header('Content-Type: application/json');

require 'config.php'; // Includes $conn

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in. Please login to book a room.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id_session = $_SESSION['user_id']; // Could be INT or 'admin'
    $logged_in_user_email = $_POST['user_email'] ?? ''; // Email from pre-filled form
    $logged_in_user_phone = $_POST['user_phone'] ?? ''; // Phone from pre-filled form

    $room_id = $_POST['room_id'] ?? null;
    $booking_in_date_str = $_POST['booking_in_date'] ?? null;
    $booking_out_date_str = $_POST['booking_out_date'] ?? null;
    
    // Card details (SIMULATED - DO NOT STORE FULL DETAILS IN PRODUCTION)
    $card_number = preg_replace('/[^0-9]/', '', $_POST['card_number'] ?? ''); // Remove non-digits
    $card_expiry_month = $_POST['card_expiry_month'] ?? '';
    $card_expiry_year = $_POST['card_expiry_year'] ?? '';
    $card_cvv = $_POST['card_cvv'] ?? ''; // We will NOT store this

    // --- Validation ---
    if (empty($room_id) || empty($booking_in_date_str) || empty($booking_out_date_str) || empty($card_number) || empty($card_expiry_month) || empty($card_expiry_year) || empty($card_cvv) || empty($logged_in_user_email)) {
        $response['message'] = 'All booking and payment fields are required.';
        echo json_encode($response);
        exit;
    }

    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        $response['message'] = 'Invalid card number length.';
        echo json_encode($response);
        exit;
    }
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $card_expiry_month) || !preg_match('/^20(2[4-9]|[3-9][0-9])$/', $card_expiry_year)) { // Valid from 2024-2099
        $response['message'] = 'Invalid card expiry date.';
        echo json_encode($response);
        exit;
    }
     if (!preg_match('/^[0-9]{3,4}$/', $card_cvv)) {
        $response['message'] = 'Invalid CVV.';
        echo json_encode($response);
        exit;
    }

    $booking_in_date = new DateTime($booking_in_date_str);
    $booking_out_date = new DateTime($booking_out_date_str);

    if ($booking_out_date <= $booking_in_date) {
        $response['message'] = 'Check-out date must be after check-in date.';
        echo json_encode($response);
        exit;
    }
    if ($booking_in_date < new DateTime('today')) {
        $response['message'] = 'Check-in date cannot be in the past.';
        echo json_encode($response);
        exit;
    }

    $interval = $booking_in_date->diff($booking_out_date);
    $total_nights = $interval->days;

    if ($total_nights <= 0) {
        $response['message'] = 'Booking must be for at least one night.';
        echo json_encode($response);
        exit;
    }

    // --- Fetch room price and check availability ---
    $stmt_room = $conn->prepare("SELECT price_per_night, availability FROM rooms WHERE room_id = ?");
    if (!$stmt_room) {
        $response['message'] = 'Database error (room fetch).'; error_log($conn->error); echo json_encode($response); exit;
    }
    $stmt_room->bind_param("s", $room_id);
    $stmt_room->execute();
    $result_room = $stmt_room->get_result();
    $room_data = $result_room->fetch_assoc();
    $stmt_room->close();

    if (!$room_data) {
        $response['message'] = 'Room not found.'; echo json_encode($response); exit;
    }
    if ($room_data['availability'] !== 'Available') {
        // More complex availability check for date ranges would be needed in a real system
        $response['message'] = 'This room is currently not available for booking.'; echo json_encode($response); exit;
    }

    // --- Check for booking conflicts ---
    // A room is considered booked for the range [booking_in_date, booking_out_date - 1 day]
    // Overlap condition: NewIn < ExistingOut AND NewOut > ExistingIn
    $stmt_conflict = $conn->prepare(
        "SELECT booking_id FROM bookings 
         WHERE room_id = ? 
         AND booking_status = 'Confirmed' 
         AND booking_in_date < ? 
         AND booking_out_date > ?"
    );
    if (!$stmt_conflict) {
        $response['message'] = 'Database error (conflict check).'; error_log("Conflict check prepare error: ".$conn->error); echo json_encode($response); exit;
    }
    $stmt_conflict->bind_param("sss", $room_id, $booking_out_date_str, $booking_in_date_str);
    $stmt_conflict->execute();
    $result_conflict = $stmt_conflict->get_result();
    if ($result_conflict->num_rows > 0) {
        $response['message'] = 'Sorry, this room is already booked for some or all of the selected dates. Please choose different dates.';
        $stmt_conflict->close();
        echo json_encode($response);
        exit;
    }
    $stmt_conflict->close();

    $price_per_night = (float)$room_data['price_per_night'];
    $total_price = $total_nights * $price_per_night;
    $advance_payment_amount = $total_price / 2;

    // --- Database Transaction (Recommended) ---
    $conn->begin_transaction();

    try {
        // Insert into bookings table
        $user_id_for_db = (is_numeric($user_id_session)) ? (int)$user_id_session : null;

        $stmt_booking = $conn->prepare("INSERT INTO bookings (user_id, guest_email, guest_phone, room_id, booking_in_date, booking_out_date, total_nights, price_per_night, total_price, advance_payment_amount, payment_status, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid - Advance', 'Confirmed')");
        if (!$stmt_booking) {  throw new Exception('Booking prepare failed: ' . $conn->error); }
        
        $stmt_booking->bind_param("isssssidds", $user_id_for_db, $logged_in_user_email, $logged_in_user_phone, $room_id, $booking_in_date_str, $booking_out_date_str, $total_nights, $price_per_night, $total_price, $advance_payment_amount);
        if (!$stmt_booking->execute()) { throw new Exception('Booking execution failed: ' . $stmt_booking->error); }
        
        $new_booking_id = $stmt_booking->insert_id;
        $stmt_booking->close();

        // Insert into booking_payments table (SIMULATED)
        $masked_card_number = "XXXX-XXXX-XXXX-" . substr($card_number, -4);
        
        $stmt_payment = $conn->prepare("INSERT INTO booking_payments (booking_id, amount_paid, masked_card_number, card_expiry_month, card_expiry_year, transaction_status) VALUES (?, ?, ?, ?, ?, 'Simulated Success - Advance Paid')");
        if (!$stmt_payment) { throw new Exception('Payment prepare failed: ' . $conn->error); }

        $stmt_payment->bind_param("idsss", $new_booking_id, $advance_payment_amount, $masked_card_number, $card_expiry_month, $card_expiry_year);
        if (!$stmt_payment->execute()) { throw new Exception('Payment execution failed: ' . $stmt_payment->error); }
        $stmt_payment->close();

        // Optionally, update room availability (complex for date ranges, simplified here)
        // For a real system, you'd check for overlapping bookings.
        // $stmt_update_room = $conn->prepare("UPDATE rooms SET availability = 'Not Available' WHERE room_id = ?");
        // $stmt_update_room->bind_param("s", $room_id);
        // $stmt_update_room->execute();
        // $stmt_update_room->close();

        $conn->commit();
        $response['status'] = 'success';
        $response['message'] = 'Room booked successfully! Your booking ID is ' . $new_booking_id . '. Advance payment of $' . number_format($advance_payment_amount, 2) . ' considered paid.';
        $response['booking_id'] = $new_booking_id;

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Booking failed: ' . $e->getMessage();
        error_log("Booking Error: " . $e->getMessage());
    }

} else {
    $response['message'] = 'Invalid request method.';
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

// Get any buffered content and clean the buffer
$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput) && json_encode($response) !== $accidentalOutput) {
    // error_log("Unexpected output in process_booking.php: " . $accidentalOutput); // Uncomment to log
}

echo json_encode($response);
?>