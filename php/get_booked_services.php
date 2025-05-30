<?php
session_start();
require_once 'config.php';
header('Content-Type: text/html; charset=utf-8');

// If the user is not logged in, display a message
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning text-center">Please log in to view your service bookings.</div>';
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Prepare query to retrieve bookings for the logged in user
$sql = "SELECT booking_id, service_id, service_name_at_booking, price_at_booking, booking_date, start_time, payment_status, booking_status, booked_at 
        FROM service_bookings 
        WHERE user_id = ? 
        ORDER BY booking_date DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo '<div class="alert alert-danger text-center">Database error: ' . htmlspecialchars($conn->error) . '</div>';
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-info text-center">No service bookings found.</div>';
    $stmt->close();
    $conn->close();
    exit;
}

while ($booking = $result->fetch_assoc()) {
    // Format dates/times as needed
    $bookingDate = date("F j, Y", strtotime($booking['booking_date']));
    $startTime = date("g:i A", strtotime($booking['start_time']));
    $bookedAt = date("F j, Y g:i A", strtotime($booking['booked_at']));
    ?>
    <div class="booking-card mb-4 border rounded shadow-sm">
      <div class="d-flex justify-content-between align-items-center bg-secondary text-white p-2 rounded-top">
        <h5 class="mb-0"><?php echo htmlspecialchars($booking['service_name_at_booking']); ?></h5>
        <span class="badge bg-warning text-dark">ID: <?php echo $booking['booking_id']; ?></span>
      </div>
      <div class="booking-card-body p-3">
        <p class="mb-1"><strong>Service ID:</strong> <?php echo $booking['service_id']; ?></p>
        <p class="mb-1"><strong>Price at Booking:</strong> $<?php echo number_format($booking['price_at_booking'], 2); ?></p>
        <p class="mb-1"><strong>Date:</strong> <?php echo $bookingDate; ?></p>
        <p class="mb-1"><strong>Time:</strong> <?php echo $startTime; ?></p>
        <p class="mb-1"><strong>Payment Status:</strong> <?php echo ucfirst($booking['payment_status']); ?></p>
        <p class="mb-1"><strong>Booking Status:</strong> <?php echo ucfirst($booking['booking_status']); ?></p>
        <p class="text-muted mb-0"><small>Booked on <?php echo $bookedAt; ?></small></p>
      </div>
    </div>
    <?php
}

$conn->close();
?>