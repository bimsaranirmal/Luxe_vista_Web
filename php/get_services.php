<?php
require_once 'config.php'; // Establishes $conn

// Error reporting (useful for development)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 for production
ini_set('log_errors', 1);

header('Content-Type: text/html');
ob_start(); // Start output buffering

if (!isset($conn) || $conn->connect_error) {
    echo '<div class="col-12 text-center alert alert-danger">Database connection error. Please try again later.</div>';
    error_log("DB connection error in get-services.php: " . ($conn ? $conn->connect_error : 'N/A'));
    ob_end_flush();
    exit;
}

$sql = "SELECT service_id, name, description, price, category, icon_class, image_path FROM services WHERE is_active = 1 ORDER BY name ASC";
$result = $conn->query($sql);

$output = '';
$is_first_item = true; // Flag for the first active carousel item

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $serviceName = htmlspecialchars($row['name']);
            $description = htmlspecialchars($row['description']);
            $price = $row['price'] && $row['price'] > 0 ? '$' . number_format($row['price'], 2) : ''; // Display price or leave empty, ensure price is > 0
            $category = $row['category'] ? htmlspecialchars($row['category']) : '';
            $iconClass = $row['icon_class'] ? htmlspecialchars($row['icon_class']) : 'fas fa-concierge-bell'; // Default icon
            // Assuming image_path is relative to the project root. Adjust if necessary.
            $imagePath = $row['image_path'] ? htmlspecialchars($row['image_path']) : 'images/service-placeholder.jpg'; // Default placeholder

            // Truncate description for the card
            $shortDescription = strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;

            $output .= '
            <div class="carousel-item' . ($is_first_item ? ' active' : '') . '">
              <div class="d-flex justify-content-center p-md-5 p-3"> <!-- Add padding and center the card -->
                <div class="col-12 col-md-10 col-lg-8"> <!-- Control card width within the slide -->
                  <div class="room-card w-100"> <!-- Re-using room-card style -->
                    <div class="room-image" style="height: 300px;"> <!-- Adjusted height for carousel slide -->
                      <img src="' . $imagePath . '" alt="' . $serviceName . '" style="object-fit: cover; width: 100%; height: 100%;">
                      ' . ($category ? '<div class="room-type-badge">' . $category . '</div>' : '') . '
                    </div>
                    <div class="room-content d-flex flex-column">
                      <h3 class="room-title"><i class="' . $iconClass . ' me-2"></i>' . $serviceName . '</h3>
                      ' . ($price ? '<div class="room-price">' . $price . '</div>' : '') . '
                      <p class="room-description flex-grow-1">' . $shortDescription . '</p>
                      <!-- <a href="service-details.php?id=' . $row['service_id'] . '" class="btn btn-view-room mt-auto">Learn More</a> -->
                    </div>
                  </div>
                </div>
              </div>
            </div>';
            $is_first_item = false; // Set to false after the first item
        }
    } else {
        // If no services, output a single non-active carousel item with the message, or carousel won't initialize properly with empty inner
        $output = '<div class="carousel-item active"><div class="d-flex justify-content-center align-items-center" style="min-height: 300px;"><p>No services currently available. Please check back later.</p></div></div>';
    }
} else {
    $output = '<div class="col-12 text-center alert alert-danger">Failed to fetch services: ' . htmlspecialchars($conn->error) . '</div>';
    error_log("SQL Error in get-services.php: " . $conn->error);
}

$conn->close();
echo $output;
ob_end_flush(); // Send buffered output
?>