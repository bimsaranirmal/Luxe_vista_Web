<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $phone = $_POST["phone"];

    // Check if the user exists with the provided email and phone
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND phone = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate a unique token for password reset
        $token = bin2hex(random_bytes(16));
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // Send the reset link to the user's email
        $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
        $subject = "Password Reset Request";
        $message = "Click the link below to reset your password:\n\n$resetLink\n\nThis link will expire in 1 hour.";
        $headers = "From: no-reply@luxevista.com";

        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send the reset email.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No account found with the provided email and phone number.']);
    }

    $stmt->close();
}
?>