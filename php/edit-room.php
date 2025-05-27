<!-- filepath: c:\xampp\htdocs\Luxe_vista\php\edit-room.php -->
<?php
require 'config.php'; // Include the database connection

if (!isset($_GET['room_id'])) {
    die('Room ID is required.');
}

$room_id = $_GET['room_id'];

// Fetch current images for the room
$imageStmt = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
$imageStmt->bind_param("s", $room_id);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();
$images = [];
while ($row = $imageResult->fetch_assoc()) {
    $images[] = $row['image_path'];
}
$imageStmt->close();

// Fetch room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE room_id = ?");
$stmt->bind_param("s", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Room not found.');
}

$room = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Room - LuxeVista</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background: linear-gradient(135deg, #1a3c40, #2c666e);
      color: #fff;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .container {
      background: #fff;
      color: #1a3c40;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      padding: 30px;
      max-width: 600px;
      width: 100%;
    }

    .btn-primary {
      background-color: #f0b67f;
      border: none;
    }

    .btn-primary:hover {
      background-color: #d4af37;
    }

    .image-preview {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 15px;
    }

    .image-preview img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
  </style>
</head>
<body>
<div class="container">
  <h2 class="text-center mb-4"><i class="fas fa-edit"></i> Edit Room</h2>
  <form action="update-room.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['room_id']); ?>">
    
    <div class="form-group">
      <label for="room_type">Room Type</label>
      <select name="room_type" class="form-control" required>
        <option value="Single" <?php echo $room['room_type'] === 'Single' ? 'selected' : ''; ?>>Single</option>
        <option value="Double" <?php echo $room['room_type'] === 'Double' ? 'selected' : ''; ?>>Double</option>
        <option value="Suite" <?php echo $room['room_type'] === 'Suite' ? 'selected' : ''; ?>>Suite</option>
        <option value="Deluxe" <?php echo $room['room_type'] === 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
      </select>
    </div>

    <div class="form-group">
      <label for="price_per_night">Price Per Night</label>
      <input type="number" name="price_per_night" class="form-control" value="<?php echo htmlspecialchars($room['price_per_night']); ?>" step="0.01" required>
    </div>

    <div class="form-group">
      <label for="availability">Availability</label>
      <select name="availability" class="form-control" required>
        <option value="Available" <?php echo $room['availability'] === 'Available' ? 'selected' : ''; ?>>Available</option>
        <option value="Not Available" <?php echo $room['availability'] === 'Not Available' ? 'selected' : ''; ?>>Not Available</option>
      </select>
    </div>

    <div class="form-group">
      <label for="capacity">Capacity</label>
      <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($room['capacity']); ?>" required>
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($room['description']); ?></textarea>
    </div>

    <div class="form-group">
      <label>Current Images</label>
      <div class="image-preview">
        <?php foreach ($images as $image): ?>
          <img src="../<?php echo htmlspecialchars($image); ?>" alt="Room Image">
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-group">
      <label for="images">Upload New Images</label>
      <input type="file" name="images[]" class="form-control-file" multiple accept="image/*">
      <small class="text-muted">You can upload up to 5 images. Existing images will remain unless replaced.</small>
    </div>

    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> Update Room</button>
  </form>
</div>
</body>
</html>