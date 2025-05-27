<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php';

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if (!isset($_SESSION['user_id'])) { // Add admin role check if needed: || $_SESSION['role'] !== 'admin'
    $response['message'] = 'Unauthorized. Please log in as an admin.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $original_message_id = isset($_POST['original_message_id']) ? (int)$_POST['original_message_id'] : 0;
    $admin_reply_message = isset($_POST['admin_reply_message']) ? trim($_POST['admin_reply_message']) : '';
    $admin_id = $_SESSION['user_id']; // Assuming admin's user_id is in session

    if (empty($original_message_id) || $original_message_id <= 0) {
        $response['message'] = 'Invalid original message ID.';
        echo json_encode($response);
        exit;
    }

    if (empty($admin_reply_message)) {
        $response['message'] = 'Reply message cannot be empty.';
        echo json_encode($response);
        exit;
    }

    $sanitized_reply = htmlspecialchars($admin_reply_message, ENT_QUOTES, 'UTF-8');

    try {
        $stmt = $conn->prepare("UPDATE contact_messages SET 
                                    admin_reply_message = ?, 
                                    admin_replied_at = NOW(), 
                                    replied_by_admin_id = ? 
                                WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('Database prepare statement failed: ' . $conn->error);
        }
        
        $stmt->bind_param("ssi", $sanitized_reply, $admin_id, $original_message_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['status'] = 'success';
                $response['message'] = 'Reply sent successfully.';
            } else {
                $response['message'] = 'Could not find the message to reply to, or no changes were made.';
            }
        } else {
            throw new Exception('Database execute statement failed: ' . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        error_log('Admin reply submission error: ' . $e->getMessage());
    }
    $conn->close();
} else {
    $response['message'] = 'Invalid request method.';
}

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output captured in submit_admin_reply.php: " . $accidentalOutput);
}
echo json_encode($response);
?>