<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];

    // Delete associated images
    $imgStmt = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
    $imgStmt->bind_param("s", $room_id);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();

    while ($row = $imgResult->fetch_assoc()) {
        $imagePath = "../" . $row['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Delete the image file
        }
    }
    $imgStmt->close();

    // Delete images from the database
    $deleteImgStmt = $conn->prepare("DELETE FROM room_images WHERE room_id = ?");
    $deleteImgStmt->bind_param("s", $room_id);
    $deleteImgStmt->execute();
    $deleteImgStmt->close();

    // Delete the room
    $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
    $stmt->bind_param("s", $room_id);

    if ($stmt->execute()) {
        header('Location: ../view-rooms.php?success=Room deleted successfully!');
        exit();
    } else {
        header('Location: ../view-rooms.php?error=Failed to delete room.');
        exit();
    }

    $stmt->close();
}
?>