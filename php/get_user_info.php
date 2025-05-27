<?php
session_start();
// config.php is included first to establish $conn
require_once 'config.php'; 
header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'user_id'   => null,
    'user_name' => '',
    'email'     => '',
    'phone'     => '',
    'role'      => '',
    'reviews'   => [],
    'bookings'  => [], // Initialize bookings array
    'contact_messages' => [], // Initialize contact_messages array
];

// Check connection status *after* config.php has run
if (!$conn || $conn->connect_error) {
    $response['message'] = 'Database connection error.';
    if ($conn && $conn->connect_error) {
        // Log the detailed error on the server for debugging
        error_log("Database connection error in get_user_info.php: " . $conn->connect_error);
    }
    echo json_encode($response);
    exit;
}

// Proceed if connection is okay
if (isset($_SESSION['user_id'])) {
    $response['logged_in'] = true;
    $response['user_id']   = $_SESSION['user_id'];
    // For the hardcoded admin, provide details directly
    if ($_SESSION['user_id'] === 'admin' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $response['user_name'] = $_SESSION['user_name'] ?? 'Administrator';
        $response['email']     = 'admin@gmail.com'; // Assuming this is the admin's email
        $response['phone']     = ''; // Admin might not have a phone in this system
        $response['role']      = 'admin';
        // Admin typically wouldn't have personal bookings or reviews in this system model
        // If admin needs to see ALL bookings/reviews, that's a separate admin-specific script.
    } 
    else {
        // Fetch details for regular users
        // Ensure user_id from session is treated as an integer for binding if it's numeric
        $session_user_id_param = $_SESSION['user_id']; 
        $param_type = is_numeric($session_user_id_param) ? "i" : "s";


        $stmt = $conn->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
        if ($stmt) {
            // The user_id from session should be an integer for 'users.id'
            // If your admin user_id is 'admin' (string), this part is for non-admin users.
            // We assume $_SESSION['user_id'] for regular users is their integer ID.
            $stmt->bind_param($param_type, $session_user_id_param);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($user_data = $result->fetch_assoc()) {
                    $response['user_name'] = $user_data['name'];
                    $response['email']     = $user_data['email'];
                    $response['phone']     = $user_data['phone'] ?? '';
                    $response['role']      = $user_data['role'];
                    $actual_user_id = $user_data['id']; // Use the ID fetched from DB for consistency

                    // --- Bookings Filtering ---
                    $booking_status_filter = isset($_GET['booking_status_filter']) ? trim($_GET['booking_status_filter']) : '';
                    $booking_conditions = "b.user_id = ?";
                    $booking_params = [$actual_user_id];
                    $booking_types = "i";

                    if (!empty($booking_status_filter)) {
                        $booking_conditions .= " AND b.booking_status = ?";
                        $booking_params[] = $booking_status_filter;
                        $booking_types .= "s";
                    }

                    // Now fetch bookings for this user using their actual user_id (integer)
                    $booking_sql = "SELECT b.booking_id, b.room_id, b.booking_in_date, b.booking_out_date, 
                                           b.total_price, b.payment_status, b.booking_status, r.room_type 
                         FROM bookings b
                         JOIN rooms r ON b.room_id = r.room_id
                         WHERE " . $booking_conditions . "
                         ORDER BY b.booking_in_date DESC";
                    
                    $booking_stmt = $conn->prepare($booking_sql);

                    if ($booking_stmt) {
                        $booking_stmt->bind_param($booking_types, ...$booking_params);
                        if ($booking_stmt->execute()) {
                            $booking_result = $booking_stmt->get_result();
                            while ($booking_row = $booking_result->fetch_assoc()) {
                                $response['bookings'][] = $booking_row;
                            }
                        } else {
                            error_log("Booking query execution error in get_user_info.php: " . $booking_stmt->error);
                        }
                        $booking_stmt->close();
                    } else {
                        error_log("Booking query prepare error in get_user_info.php: " . $conn->error);
                    }

                    // Now fetch reviews for this user using their email
                    if (!empty($user_data['email'])) {
                        $review_stmt = $conn->prepare("SELECT rating, review_text, created_at FROM reviews WHERE guest_name = ? ORDER BY created_at DESC");
                        if ($review_stmt) {
                            $review_stmt->bind_param("s", $user_data['email']);
                            if ($review_stmt->execute()) {
                                $review_result = $review_stmt->get_result();
                                while ($review_row = $review_result->fetch_assoc()) {
                                    $response['reviews'][] = $review_row;
                                }
                            } else {
                                error_log("Review query execution error in get_user_info.php: " . $review_stmt->error);
                            }
                            $review_stmt->close();
                        } else {
                             error_log("Review query prepare error in get_user_info.php: " . $conn->error);
                        }
                    }

                    // --- Contact Messages Filtering ---
                    $contact_reply_status_filter = isset($_GET['contact_reply_status_filter']) ? trim($_GET['contact_reply_status_filter']) : '';
                    $contact_conditions = "user_id = ?"; // cm. prefix will be added in final SQL
                    $contact_params = [$actual_user_id];
                    $contact_types = "i";

                    if (!empty($contact_reply_status_filter)) {
                        if ($contact_reply_status_filter === 'replied') {
                            $contact_conditions .= " AND admin_reply_message IS NOT NULL";
                        } elseif ($contact_reply_status_filter === 'unreplied') {
                            $contact_conditions .= " AND admin_reply_message IS NULL";
                        }
                    }

                    // Now fetch contact messages for this user using their user_id
                    $contact_sql = "SELECT subject, message, submitted_at, 
                                admin_reply_message, admin_replied_at, replied_by_admin_id
                         FROM contact_messages 
                         WHERE " . $contact_conditions . "
                         ORDER BY submitted_at DESC";

                    $contact_stmt = $conn->prepare($contact_sql);
                    if ($contact_stmt) {
                        $contact_stmt->bind_param($contact_types, ...$contact_params); // Only user_id is bound if no other param needed
                        if ($contact_stmt->execute()) {
                            $contact_result = $contact_stmt->get_result();
                            while ($contact_row = $contact_result->fetch_assoc()) {
                                $response['contact_messages'][] = $contact_row;
                            }
                        }
                        $contact_stmt->close();
                    }
                } else {
                    // User ID in session but not found in DB (e.g., deleted user)
                    $response['logged_in'] = false; // Invalidate session effectively
                    $response['message'] = "User session invalid. Please log in again.";
                    // Optionally, destroy session here:
                    // session_unset();
                    // session_destroy();
                }
            } else {
                 error_log("User data query execution error in get_user_info.php: " . $stmt->error);
                 $response['message'] = "Could not fetch user data.";
            }
            $stmt->close();
        } else {
            error_log("User data query prepare error in get_user_info.php: " . $conn->error);
            $response['message'] = "Database error preparing user data.";
        }
    }
} else {
    // Not logged in, $response['logged_in'] remains false
    $response['message'] = "User not authenticated.";
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
echo json_encode($response);
?>
