<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $room_type = $_POST['room_type'];
    $capacity = $_POST['capacity'];
    $description = $_POST['description'];
    $price_per_night = $_POST['price_per_night'];
    $availability = $_POST['availability'];

    // Update room details
    $stmt = $conn->prepare("UPDATE rooms SET room_type = ?, capacity = ?, description = ?, price_per_night = ?, availability = ? WHERE room_id = ?");
    $stmt->bind_param("sissss", $room_type, $capacity, $description, $price_per_night, $availability, $room_id);

    if ($stmt->execute()) {
        // Handle new image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = "../uploads/rooms/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Delete old images
            $deleteStmt = $conn->prepare("DELETE FROM room_images WHERE room_id = ?");
            $deleteStmt->bind_param("s", $room_id);
            $deleteStmt->execute();
            $deleteStmt->close();

            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                $fileName = basename($_FILES['images']['name'][$key]);
                $relativePath = "uploads/rooms/" . uniqid() . "_" . $fileName;
                $absolutePath = "../" . $relativePath;

                if (move_uploaded_file($tmpName, $absolutePath)) {
                    $imgStmt = $conn->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
                    $imgStmt->bind_param("ss", $room_id, $relativePath);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }

        echo "<script>
                alert('Room updated successfully!');
                window.location.href = '../view-rooms.php';
              </script>";
    } else {
        echo "<script>
                alert('Error updating room: " . $stmt->error . "');
                window.history.back();
              </script>";
    }

    $stmt->close();
}
?>