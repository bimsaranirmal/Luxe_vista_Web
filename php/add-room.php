<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $room_type = $_POST['room_type'];
    $capacity = $_POST['capacity'];
    $description = $_POST['description'];
    $price_per_night = $_POST['price_per_night'];
    $availability = $_POST['availability'];

    // Check number of images
    if (count($_FILES['images']['name']) > 5) {
        echo json_encode(['status' => 'error', 'message' => 'You can upload a maximum of 5 images.']);
        exit();
    }

    // Insert room details
    $stmt = $conn->prepare("INSERT INTO rooms (room_id, room_type, capacity, description, price_per_night, availability) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $room_id, $room_type, $capacity, $description, $price_per_night, $availability);

    if ($stmt->execute()) {
        $uploadDir = "../uploads/rooms/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['images']['name'][$key]);
            $relativePath = "uploads/rooms/" . uniqid() . "_" . $fileName;
            $absolutePath = "../" . $relativePath;

            if (move_uploaded_file($tmpName, $absolutePath)) {
                $imgStmt = $conn->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
                $imgStmt->bind_param("ss", $room_id, $relativePath); // SAVE RELATIVE PATH ONLY
                $imgStmt->execute();
                $imgStmt->close();
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Room added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
}
?>