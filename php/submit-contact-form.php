<?php
session_start();
include 'config.php'; // Ensure this file correctly establishes a $conn database connection

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in. Please log in to send a message.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // It's good practice to use the email from the session if possible,
    // or verify the submitted email against the session email.
    // For this example, we'll use the submitted email but ensure it's validated.
    $user_email_from_form = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
    $subject = isset($_POST['contact_subject']) ? trim($_POST['contact_subject']) : '';
    $message_text = isset($_POST['contact_message']) ? trim($_POST['contact_message']) : '';
    $user_id = $_SESSION['user_id'];

    // Validate email from form
    if (!filter_var($user_email_from_form, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format provided.';
        echo json_encode($response);
        exit;
    }

    // You might want to fetch the user's actual email from the database based on user_id
    // to ensure the correct email is stored, rather than relying solely on the form input.
    // For now, we'll use the validated email from the form.
    $user_email_to_store = $user_email_from_form;

    if (empty($subject) || empty($message_text)) {
        $response['message'] = 'Subject and message fields are required.';
        echo json_encode($response);
        exit;
    }

    // Sanitize inputs before inserting into database
    $sanitized_subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $sanitized_message = htmlspecialchars($message_text, ENT_QUOTES, 'UTF-8');

    try {
        $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, user_email, subject, message, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt === false) {
            throw new Exception('Database prepare statement failed: ' . $conn->error);
        }
        
        $stmt->bind_param("isss", $user_id, $user_email_to_store, $sanitized_subject, $sanitized_message);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Your message has been sent successfully! We will get back to you soon.';
        } else {
            throw new Exception('Database execute statement failed: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        error_log('Contact form submission error: ' . $e->getMessage()); // Log detailed error
    }
    $conn->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>