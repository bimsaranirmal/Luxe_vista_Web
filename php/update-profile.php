<?php
session_start();
require 'config.php'; // Includes $conn
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in. Please login to update your profile.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get data from POST request
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? null); // Phone is optional, allow null

    // --- Validation ---
    if (empty($name)) {
        $response['message'] = 'Name cannot be empty.';
        echo json_encode($response);
        exit;
    }
    if (empty($email)) {
        $response['message'] = 'Email cannot be empty.';
        echo json_encode($response);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }
    // Optional: Add phone number validation if needed (e.g., regex for format)

    // --- Check if email is being changed and if the new email is already taken by another user ---
    $stmt_check_email = $conn->prepare("SELECT email FROM users WHERE id = ?");
    if (!$stmt_check_email) {
        error_log("MySQL Prepare Error (fetch current email) in update_profile.php: " . $conn->error);
        $response['message'] = 'Database error. Could not verify email.';
        echo json_encode($response);
        exit;
    }
    $stmt_check_email->bind_param("i", $user_id);
    $stmt_check_email->execute();
    $result_current_email = $stmt_check_email->get_result();
    $current_user_data = $result_current_email->fetch_assoc();
    $current_email = $current_user_data['email'] ?? null;
    $stmt_check_email->close();

    if ($current_email && strtolower($email) !== strtolower($current_email)) {
        // Email is being changed, check if the new email is already in use by ANOTHER user
        $stmt_email_exists = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if (!$stmt_email_exists) {
            error_log("MySQL Prepare Error (check new email) in update_profile.php: " . $conn->error);
            $response['message'] = 'Database error. Could not validate new email.';
            echo json_encode($response);
            exit;
        }
        $stmt_email_exists->bind_param("si", $email, $user_id);
        $stmt_email_exists->execute();
        if ($stmt_email_exists->get_result()->num_rows > 0) {
            $response['message'] = 'This email address is already registered by another user.';
            $stmt_email_exists->close();
            echo json_encode($response);
            exit;
        }
        $stmt_email_exists->close();
    }

    // --- Prepare UPDATE statement ---
    // Note: Password update should ideally be handled in a separate, dedicated function for better security.
    $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql);

    if (!$stmt_update) {
        error_log("MySQL Prepare Error (update user) in update_profile.php: " . $conn->error);
        $response['message'] = 'Could not prepare statement for profile update.';
    } else {
        $stmt_update->bind_param("sssi", $name, $email, $phone, $user_id);
        if ($stmt_update->execute()) {
            // Check if any row was actually changed
            if ($stmt_update->affected_rows > 0) {
                $response['status'] = 'success';
                $response['message'] = 'Profile updated successfully!';
                $_SESSION['user_name'] = $name; // Update session with new name
            } else {
                $response['status'] = 'info'; // Use 'info' or 'success'
                $response['message'] = 'No changes were made to your profile information.';
            }
        } else {
            error_log("MySQL Execute Error in update_profile.php: " . $stmt_update->error);
            $response['message'] = 'Failed to update profile. Please try again.';
        }
        $stmt_update->close();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

echo json_encode($response);
?>