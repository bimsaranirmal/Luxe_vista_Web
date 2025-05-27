<?php
require_once 'config.php';

$sql = "SELECT r.*, ri.main_image
        FROM rooms r
        LEFT JOIN (
            SELECT r1.room_id, r1.image_path AS main_image
            FROM room_images r1
            INNER JOIN (
                SELECT room_id, MIN(id) AS min_id
                FROM room_images
                GROUP BY room_id
            ) r2 ON r1.room_id = r2.room_id AND r1.id = r2.min_id
        ) ri ON r.room_id = ri.room_id
        WHERE r.availability = 'Available'
        ORDER BY r.price_per_night ASC";

$result = $conn->query($sql);

if (!$result) {
    echo "Database query failed: " . $conn->error;
    exit();
}


$rooms_data = [];
while ($room = $result->fetch_assoc()) {
    // Set a default image if main_image is not available or empty
    if (empty($room['main_image'])) {
        // Assuming 'images' folder is at the root, accessible from index.html
        $room['main_image'] = 'images/default-room.jpg'; 
    }
    $rooms_data[] = $room;
}

if (empty($rooms_data)) {
    echo '<div class="col-12 text-center"><p>No rooms available at the moment. Please check back later.</p></div>';
} else {
?><div id="roomsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000"> <!-- Interval set to 7000ms (7 seconds) -->
    <div class="carousel-indicators">
        <?php
        $num_slides = ceil(count($rooms_data) / 3);
        for ($i = 0; $i < $num_slides; $i++): ?>
        <button type="button" data-bs-target="#roomsCarousel" data-bs-slide-to="<?php echo $i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>" aria-current="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $i + 1; ?>"></button>
        <?php endfor; ?>
    </div>
    <div class="carousel-inner">
        <?php
        $room_chunks = array_chunk($rooms_data, 3);
        foreach ($room_chunks as $slide_index => $chunk):
        ?>
        <div class="carousel-item <?php echo $slide_index === 0 ? 'active' : ''; ?>">
            <div class="container">
                <div class="row">
                    <?php foreach ($chunk as $room): ?>
                    <div class="col-md-4 mb-4 d-flex align-items-stretch"> <!-- col-md-4 for 3 cards per row, mb-4 for spacing, d-flex for equal height cards -->
                        <div class="room-card w-100">  <!-- w-100 to make card take full width of col -->
                            <div class="room-image">
                                <img src="<?php echo htmlspecialchars($room['main_image']); ?>" class="d-block w-100" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
                                <div class="room-type-badge"><?php echo htmlspecialchars($room['room_type']); ?></div>
                            </div>
                            <div class="room-content d-flex flex-column"> <!-- flex-column for button at bottom -->
                                <h3 class="room-title"><?php echo htmlspecialchars($room['room_type']); ?></h3> <!-- Changed to room_type for a more descriptive title -->
                                <p class="room-id-display" style="font-size: 0.9em; color: #6c757d;">ID: <?php echo htmlspecialchars($room['room_id']); ?></p>
                                <div class="room-price">$<?php echo htmlspecialchars(number_format((float)$room['price_per_night'], 2, '.', '')); ?> <span>per night</span></div>
                                <div class="room-features">
                                    <span><i class="fas fa-user-friends"></i> <?php echo htmlspecialchars($room['capacity']); ?> Guests</span>
                                    <!-- Availability is already filtered by SQL, so always 'Available' here -->
                                    <!-- <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($room['availability']); ?></span> -->
                                </div>
                                <p class="room-description"><?php
                                    $description = htmlspecialchars($room['description']);
                                    echo strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
                                ?></p>
                                <a href="room-details.php?id=<?php echo htmlspecialchars($room['room_id']); ?>" class="btn btn-view-room mt-auto">View Details</a> <!-- mt-auto to push button to bottom -->
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($num_slides > 1): // Only show controls if there's more than one slide ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#roomsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#roomsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
    <?php endif; ?>
</div>
<?php
}
$conn->close();
?>