<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config.php'; // Establishes $conn

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Optional: Admin role check
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access. Admin privileges required.';
    echo json_encode($response);
    exit;
}
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($conn) || $conn->connect_error) {
        $response['message'] = 'Database connection error.';
        error_log("DB connection error in add_service.php: " . ($conn ? $conn->connect_error : 'N/A'));
        echo json_encode($response);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;
    $category = !empty($_POST['category']) ? trim($_POST['category']) : null;
    $icon_class = !empty($_POST['icon_class']) ? trim($_POST['icon_class']) : null;
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;
    $image_path = null; // Initialize image path

    if (empty($name) || empty($description)) {
        $response['message'] = 'Service name and description are required.';
        echo json_encode($response);
        exit;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $upload_dir = '../uploads/services/'; // Directory relative to the PHP script
        $allowed_types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate file type
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            echo json_encode($response);
            exit;
        }

        // Validate file size
        if ($file['size'] > $max_size) {
            $response['message'] = 'File size exceeds the maximum limit (5MB).';
            echo json_encode($response);
            exit;
        }

        // Generate a unique filename
        $file_extension = array_search($file_type, $allowed_types);
        $new_file_name = uniqid('service_', true) . '.' . $file_extension;
        $destination = $upload_dir . $new_file_name;
        $image_path = 'uploads/services/' . $new_file_name; // Path to save in DB (relative to web root)

        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $response['message'] = 'Failed to move uploaded file.';
            error_log("Failed to move uploaded file from {$file['tmp_name']} to {$destination}");
            echo json_encode($response);
            exit;
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $response['message'] = 'File upload error: ' . $_FILES['image']['error'];
        error_log("File upload error in add_service.php: " . $_FILES['image']['error']);
        exit;
    }

    // Prepare the SQL statement including the image_path
    $sql = "INSERT INTO services (name, description, price, category, icon_class, image_path, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters: s = string, d = double, i = integer
        // Note the extra 's' for image_path and 'i' for is_active
        $stmt->bind_param("ssdsssi", $name, $description, $price, $category, $icon_class, $image_path, $is_active);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Service added successfully!';
        } else {
            $response['message'] = 'Failed to add service: ' . $stmt->error;
            error_log("SQL Execute Error in add_service.php: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['message'] = 'Failed to prepare statement: ' . $conn->error;
        error_log("SQL Prepare Error in add_service.php: " . $conn->error);
    }

    $conn->close();
} else {
    $response['message'] = 'Invalid request method.';
}

$accidentalOutput = ob_get_clean();
if (!empty($accidentalOutput)) {
    error_log("Accidental output in add_service.php: " . $accidentalOutput);
    // If headers not sent, we can still try to send JSON, but it might be corrupted
}

echo json_encode($response);
?>