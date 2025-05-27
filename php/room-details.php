<?php
session_start();
require 'php/config.php'; // Adjust path if config.php is elsewhere

$room_id = $_GET['id'] ?? null;
$room = null;
$room_images = [];

if ($room_id === null) {
    // Redirect or show error if no room ID is provided
    header("Location: all-rooms.html"); // Or index.html
    exit;
}

if ($conn && !$conn->connect_error) {
    // Fetch room details
    $stmt_room = $conn->prepare("SELECT room_id, room_type, capacity, description, price_per_night, availability FROM rooms WHERE room_id = ?");
    if ($stmt_room) {
        $stmt_room->bind_param("s", $room_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        $room = $result_room->fetch_assoc();
        $stmt_room->close();
    } else {
        error_log("Failed to prepare room details statement: " . $conn->error);
    }

    // Fetch room images
    if ($room) {
        $stmt_images = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY image_id ASC"); // Assuming an image_id for ordering
        if ($stmt_images) {
            $stmt_images->bind_param("s", $room_id);
            $stmt_images->execute();
            $result_images = $stmt_images->get_result();
            while ($img_row = $result_images->fetch_assoc()) {
                // Ensure image paths are correct relative to this page or use absolute paths
                // If image_path is stored as 'uploads/rooms/image.jpg' and this file is in root, it's fine.
                $room_images[] = $img_row['image_path'];
            }
            $stmt_images->close();
        } else {
            error_log("Failed to prepare room images statement: " . $conn->error);
        }
    }
    $conn->close();
} else {
    error_log("Database connection error in room-details.php: " . ($conn ? $conn->connect_error : "Connection object null"));
}

if ($room === null) {
    // Handle room not found
    // You can redirect or display a "Room not found" message
    // For now, let's just output a message.
    // In a real app, you'd have a proper error page.
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Room Not Found</h1><p>The requested room could not be found. Please <a href='all-rooms.html'>return to rooms list</a>.</p></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($room['room_type']); ?> Details - LuxeVista</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #1a3c40;
      --secondary: #2c666e;
      --accent: #f0b67f;
      --light: #f7f9f9;
      --gold: #d4af37;
    }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; background-color: var(--light); }
    .navbar { background-color: rgba(26, 60, 64, 0.95); backdrop-filter: blur(10px); padding: 15px 0; }
    .navbar-brand img { height: 50px; }
    .navbar-nav .nav-link { color: var(--light); font-weight: 500; margin: 0 10px; }
    .navbar-nav .nav-link:hover { color: var(--accent); }
    .navbar .btn-user-action { background-color: var(--accent); border: 2px solid var(--accent); color: var(--primary); font-weight: 600; }
    .navbar .btn-user-action:hover { background-color: transparent; color: var(--accent); }

    .room-details-header {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('<?php echo !empty($room_images) ? htmlspecialchars($room_images[0]) : "https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80"; ?>') no-repeat center center;
      background-size: cover;
      padding: 100px 0;
      color: white;
      text-align: center;
    }
    .room-details-header h1 { font-size: 3rem; font-weight: 700; }

    .carousel-item img {
      height: 500px; /* Adjust as needed */
      object-fit: cover;
      width: 100%;
    }
    .room-info-section { padding: 40px 0; }
    .room-info-section h2 { color: var(--primary); margin-bottom: 20px; font-weight: 700; }
    .room-info-section .price { font-size: 2rem; color: var(--accent); font-weight: bold; margin-bottom: 15px; }
    .room-info-section .price span { font-size: 1rem; color: #777; font-weight: normal; }
    .room-info-section .features span { margin-right: 20px; font-size: 1.1rem; }
    .room-info-section .features i { color: var(--secondary); margin-right: 8px; }
    .btn-book-now { background-color: var(--accent); color: var(--primary); font-weight: bold; padding: 12px 30px; font-size: 1.1rem; border: none; }
    .btn-book-now:hover { background-color: var(--gold); }

    .footer { background-color: var(--primary); color: var(--light); padding: 70px 0 0; }
    .footer-logo img { height: 70px; margin-bottom: 20px; }
    .footer-text { color: rgba(255,255,255,0.7); margin-bottom: 30px; line-height: 1.7; }
    .footer-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 25px; color: white; position: relative; }
    .footer-title::after { content: ''; position: absolute; bottom: -10px; left: 0; width: 40px; height: 2px; background: linear-gradient(to right, var(--accent), var(--gold)); }
    .footer-links { list-style: none; padding: 0; margin: 0; }
    .footer-links li { margin-bottom: 12px; }
    .footer-links a { color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s ease; }
    .footer-links a:hover { color: var(--accent); padding-left: 5px; }
    .footer-contact i { color: var(--accent); margin-right: 10px; width: 20px; }
    .footer-contact p { margin-bottom: 15px; color: rgba(255,255,255,0.7); }
    .social-links { margin-top: 30px; }
    .social-links a { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.1); color: white; margin-right: 10px; transition: all 0.3s ease; }
    .social-links a:hover { background-color: var(--accent); transform: translateY(-5px); }
    .copyright { text-align: center; padding: 20px 0; margin-top: 50px; border-top: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.5); font-size: 0.9rem; }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.html"><img src="images/logo.png" alt="LuxeVista Logo"></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="all-rooms.html">Rooms</a></li>
          <li class="nav-item"><a class="nav-link" href="index.html#about">About</a></li>
          <li class="nav-item"><a class="nav-link" href="index.html#contact">Contact</a></li>
          <li class="nav-item ms-lg-3" id="userAuthLinkContainer">
             <a class="btn btn-user-action" href="login.html">Login / Register</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Room Details Header -->
  <header class="room-details-header">
    <div class="container">
      <h1><?php echo htmlspecialchars($room['room_type']); ?></h1>
      <p class="lead">Room ID: <?php echo htmlspecialchars($room['room_id']); ?></p>
    </div>
  </header>

  <main class="container py-5">
    <div class="row">
      <div class="col-lg-8 mb-4">
        <?php if (!empty($room_images)): ?>
        <div id="roomImageCarousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <?php foreach ($room_images as $index => $image_path): ?>
            <button type="button" data-bs-target="#roomImageCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
          </div>
          <div class="carousel-inner rounded shadow">
            <?php foreach ($room_images as $index => $image_path): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
              <img src="<?php echo htmlspecialchars($image_path); ?>" class="d-block w-100" alt="Room Image <?php echo $index + 1; ?>">
            </div>
            <?php endforeach; ?>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#roomImageCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#roomImageCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
        <?php elseif (!empty($room['main_image_from_rooms_table_if_any'])): // Fallback if no images in room_images ?>
            <img src="<?php echo htmlspecialchars($room['main_image_from_rooms_table_if_any']); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
        <?php else: ?>
            <img src="images/default-room-large.jpg" class="img-fluid rounded shadow" alt="Default Room Image">
        <?php endif; ?>
      </div>

      <div class="col-lg-4 room-info-section">
        <h2>Room Details</h2>
        <p class="price">$<?php echo htmlspecialchars(number_format((float)$room['price_per_night'], 2)); ?> <span>per night</span></p>
        <div class="features mb-3">
          <span><i class="fas fa-users"></i> Capacity: <?php echo htmlspecialchars($room['capacity']); ?> Guests</span><br>
          <span><i class="fas fa-door-open"></i> Type: <?php echo htmlspecialchars($room['room_type']); ?></span>
        </div>
        <p class="availability mb-3">
          <strong>Availability:</strong>
          <span class="badge <?php echo $room['availability'] === 'Available' ? 'bg-success' : 'bg-danger'; ?>">
            <?php echo htmlspecialchars($room['availability']); ?>
          </span>
        </p>
        <h4>Description</h4>
        <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
        
        <?php if ($room['availability'] === 'Available'): ?>
        <button class="btn btn-book-now w-100 mt-3">Book Now</button>
        <?php else: ?>
        <button class="btn btn-secondary w-100 mt-3" disabled>Currently Unavailable</button>
        <?php endif; ?>
        <a href="all-rooms.html" class="btn btn-outline-secondary w-100 mt-2">Back to All Rooms</a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-3 col-md-6"><div class="footer-logo"><img src="images/logo.png" alt="LuxeVista Logo"></div><p class="footer-text">Experience unparalleled luxury and comfort at LuxeVista.</p><div class="social-links"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a></div></div>
        <div class="col-lg-3 col-md-6"><h4 class="footer-title">Quick Links</h4><ul class="footer-links"><li><a href="index.html">Home</a></li><li><a href="all-rooms.html">Rooms</a></li><li><a href="index.html#about">About Us</a></li><li><a href="index.html#testimonials">Testimonials</a></li><li><a href="index.html#contact">Contact</a></li></ul></div>
        <div class="col-lg-3 col-md-6"><h4 class="footer-title">Our Services</h4><ul class="footer-links"><li><a href="#">Restaurant & Bar</a></li><li><a href="#">Spa & Wellness</a></li><li><a href="#">Conference Room</a></li></ul></div>
        <div class="col-lg-3 col-md-6"><h4 class="footer-title">Contact Us</h4><div class="footer-contact"><p><i class="fas fa-map-marker-alt"></i> 123 Luxury Ave, Colombo, Sri Lanka</p><p><i class="fas fa-phone-alt"></i> +94 11 234 5678</p><p><i class="fas fa-envelope"></i> info@luxevista.com</p></div></div>
      </div>
    </div>
    <div class="copyright"><div class="container"><p>&copy; 2025 LuxeVista. All rights reserved.</p></div></div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script>
    // Check login status for navbar
    document.addEventListener('DOMContentLoaded', function() {
        fetch('php/get_user_info.php') // Adjust path if get_user_info.php is elsewhere
            .then(response => response.json())
            .then(data => {
                const userAuthLinkContainer = document.getElementById('userAuthLinkContainer');
                if (data.logged_in) {
                    userAuthLinkContainer.innerHTML = `
                        <span class="navbar-text text-white me-3">Hi, ${data.user_name.split(' ')[0]}</span>
                        <a class="btn btn-user-action" href="dashboard.html">Dashboard</a>
                        <a class="btn btn-user-action ms-2" href="php/logout.php">Logout</a>`;
                } else {
                    userAuthLinkContainer.innerHTML = '<a class="btn btn-user-action" href="login.html">Login / Register</a>';
                }
            })
            .catch(error => console.error('Error fetching user status:', error));
    });
  </script>
</body>
</html>