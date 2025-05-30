<?php
// c:\my\htdocs\Luxe_vista\php\submit_review.php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

require 'config.php'; 

if (!isset($conn) || !$conn) {

    $response['message'] = 'Database connection object not available. Check config.php.';
    // Attempt to send JSON response even if headers were already sent by config.php's die()
    echo json_encode($response);
    exit;
}

if ($conn->connect_error) {
    // This means $conn was initialized, but there was a connection error.
    // If config.php dies on connect_error, this part might not be reached.
    $response['message'] = 'Database connection failed: ' . htmlspecialchars($conn->connect_error);
    // Attempt to send JSON response
    echo json_encode($response);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]);
    $review_text = trim($_POST['review_text'] ?? '');

    if (empty($guest_name)) {
        $response['message'] = 'Name is required.';
    } elseif ($rating === false || $rating === null) {
        $response['message'] = 'A valid rating (1-5 stars) is required.';
    } elseif (empty($review_text)) {
        $response['message'] = 'Review text cannot be empty.';
    } else {
        // Database operations
        $stmt = $conn->prepare("INSERT INTO reviews (guest_name, rating, review_text) VALUES (?, ?, ?)");

        if (!$stmt) { // More concise check for false
            // Log the actual MySQL error for debugging on the server.
            error_log("MySQL Prepare Error in submit-review.php: " . $conn->error); 
            $response['message'] = 'Could not prepare statement. DB Error: ' . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("sis", $guest_name, $rating, $review_text);

            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Thank you for your review! It has been submitted.';
            } else {
                error_log("MySQL Execute Error in submit-review.php: " . $stmt->error);
                $response['message'] = 'Failed to submit review. Please try again. DB Error: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// The connection $conn is established in config.php.
// It's good practice to close it here if config.php doesn't handle it.
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

echo json_encode($response);
?>
