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
        $stmt_images = $conn->prepare("SELECT image_path FROM room_images WHERE room_id = ? ORDER BY id ASC"); // Order by the primary key 'id'
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
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

    /* Styles for the inline calendar */
    #roomAvailabilityCalendarContainer { margin-top: 30px; margin-bottom: 20px; }
    .flatpickr-calendar { box-shadow: 0 0 15px rgba(0,0,0,0.1); }


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

        <!-- Calendar Placeholder -->
        <div id="roomAvailabilityCalendarContainer">
          <h4>Room Availability Calendar</h4>
          <div id="roomAvailabilityCalendar"></div>
        </div>

        <h4>Description</h4>
        <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
        
        <?php if ($room['availability'] === 'Available'): ?>
        <button class="btn btn-book-now w-100 mt-3" data-bs-toggle="modal" data-bs-target="#bookingModal">Book Now</button>
        <?php else: ?>
        <button class="btn btn-secondary w-100 mt-3" disabled>Unavailable</button>
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

  <!-- Booking Modal -->
  <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bookingModalLabel">Book Room: <?php echo htmlspecialchars($room['room_type']); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="bookingForm">
            <!-- Hidden inputs for room details -->
            <input type="hidden" name="room_id" id="bookingRoomId" value="<?php echo htmlspecialchars($room['room_id']); ?>">
            <input type="hidden" name="price_per_night" id="bookingPricePerNight" value="<?php echo htmlspecialchars($room['price_per_night']); ?>">
            
            <!-- User Information (Pre-filled) -->
            <h5 class="mb-3"><i class="fas fa-user-circle me-2 text-primary"></i>Your Details</h5>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="userEmail" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                <input type="email" class="form-control" id="userEmail" name="user_email" readonly required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="userPhone" class="form-label"><i class="fas fa-phone me-2"></i>Phone (Optional)</label>
                <input type="tel" class="form-control" id="userPhone" name="user_phone" readonly>
              </div>
            </div>
            
            <hr class="my-4">

            <!-- Booking Dates -->
            <h5 class="mb-3"><i class="fas fa-calendar-check me-2 text-primary"></i>Booking Dates</h5>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="bookingInDate" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Check-in Date</label>
                <input type="date" class="form-control" id="bookingInDate" name="booking_in_date" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="bookingOutDate" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Check-out Date</label>
                <input type="date" class="form-control" id="bookingOutDate" name="booking_out_date" required>
              </div>
            </div>

            <!-- Price Summary -->
            <div class="card bg-light p-3 mb-4 shadow-sm">
                <h6 class="card-title mb-2"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Price Summary</h6>
                <p class="mb-1"><strong>Price Per Night:</strong> $<?php echo htmlspecialchars(number_format((float)$room['price_per_night'], 2)); ?></p>
                <p class="mb-1"><strong>Total Nights:</strong> <span id="totalNightsDisplay">0</span></p>
                <h5 class="mt-2 mb-1"><strong>Total Price:</strong> $<span id="totalPriceDisplay">0.00</span></h5>
                <h6 class="text-success mb-0"><strong>Advance Payment (50%):</strong> $<span id="advancePaymentDisplay">0.00</span></h6>
            </div>
            <h5 class="mb-3"><i class="fas fa-credit-card me-2 text-primary"></i>Payment Details (Simulated)</h5>
            <p class="text-danger small"><strong>Warning:</strong> For demonstration only. Do not enter real card details. This system does not securely process payments.</p>
            <div class="mb-3">
              <label for="cardNumber" class="form-label">Card Number</label>
              <input type="text" class="form-control" id="cardNumber" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required maxlength="19" inputmode="numeric">
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="cardExpiryMonth" class="form-label">Expiry Month</label>
                <input type="text" class="form-control" id="cardExpiryMonth" name="card_expiry_month" placeholder="MM" required maxlength="2" inputmode="numeric">
              </div>
              <div class="col-md-4 mb-3">
                <label for="cardExpiryYear" class="form-label">Expiry Year</label>
                <input type="text" class="form-control" id="cardExpiryYear" name="card_expiry_year" placeholder="YYYY" required maxlength="4" inputmode="numeric">
              </div>
              <div class="col-md-4 mb-3">
                <label for="cardCvv" class="form-label">CVV</label>
                <input type="text" class="form-control" id="cardCvv" name="card_cvv" placeholder="XXX" required maxlength="4" inputmode="numeric">
              </div>

            <div id="bookingMessage" class="mt-3"></div>
            <button type="submit" class="btn btn-primary w-100 btn-book-now">Confirm Booking & Pay Advance</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Flatpickr JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    // Check login status for navbar
    document.addEventListener('DOMContentLoaded', function() {
        const bookingModalElement = document.getElementById('bookingModal');
        const bookingForm = document.getElementById('bookingForm');
        const userEmailInput = document.getElementById('userEmail');
        const userPhoneInput = document.getElementById('userPhone');
        const bookingInDateInput = document.getElementById('bookingInDate');
        const bookingOutDateInput = document.getElementById('bookingOutDate');
        const totalNightsDisplay = document.getElementById('totalNightsDisplay');
        const totalPriceDisplay = document.getElementById('totalPriceDisplay');
        const advancePaymentDisplay = document.getElementById('advancePaymentDisplay');
        const pricePerNight = parseFloat(document.getElementById('bookingPricePerNight').value);
        let roomBookedDates = []; // To store fetched booked dates for the current room
        const currentRoomId = document.getElementById('bookingRoomId').value;

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
                    userAuthLinkContainer.innerHTML = '<a class="btn btn-user-action" href="login.html?redirect=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>">Login / Register</a>';
                    // Disable book now button if not logged in, or handle redirect
                    const bookNowButton = document.querySelector('.btn-book-now[data-bs-target="#bookingModal"]');
                    if(bookNowButton) {
                        // bookNowButton.disabled = true;
                        // bookNowButton.textContent = "Login to Book";
                    }
                }
                // Pre-fill user details in booking form if logged in
                if (data.logged_in && userEmailInput && userPhoneInput) {
                    userEmailInput.value = data.email || '';
                    userPhoneInput.value = data.phone || '';
                }
            })
            .catch(error => console.error('Error fetching user status:', error));

        // Fetch booked dates for the calendar on page load
        fetch(`php/get_room_booked_dates.php?room_id=${currentRoomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    roomBookedDates = data.booked_dates;
                    initializeRoomCalendar(roomBookedDates);
                } else {
                    console.error('Failed to fetch booked dates for calendar:', data.message);
                    initializeRoomCalendar([]); // Initialize with empty if fetch fails
                }
            })
            .catch(error => {
                console.error('Error fetching booked dates for calendar:', error);
                initializeRoomCalendar([]); // Initialize with empty on error
            });

        function initializeRoomCalendar(bookedDates) {
            flatpickr("#roomAvailabilityCalendar", {
                inline: true, // Display the calendar inline
                minDate: "today",
                dateFormat: "Y-m-d",
                disable: bookedDates, // Disable dates that are already booked
            });
        }

        // Fetch booked dates when modal is about to be shown
        if (bookingModalElement) {
            bookingModalElement.addEventListener('show.bs.modal', function () {
                fetch(`php/get_room_booked_dates.php?room_id=${currentRoomId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            roomBookedDates = data.booked_dates;
                            // console.log('Booked dates for this room:', roomBookedDates);
                            // Here you could integrate with a JS calendar to disable dates
                            // For now, we'll use this array for validation on submit.
                        } else {
                            console.error('Failed to fetch booked dates:', data.message);
                            roomBookedDates = [];
                        }
                    })
                    .catch(error => console.error('Error fetching booked dates:', error));
            });
        }
        function calculatePrice() {
            if (!bookingInDateInput.value || !bookingOutDateInput.value || !pricePerNight) {
                totalNightsDisplay.textContent = '0';
                totalPriceDisplay.textContent = '0.00';
                advancePaymentDisplay.textContent = '0.00';
                return;
            }

            const checkIn = new Date(bookingInDateInput.value);
            const checkOut = new Date(bookingOutDateInput.value);

            if (checkOut <= checkIn) {
                totalNightsDisplay.textContent = '0';
                totalPriceDisplay.textContent = '0.00';
                advancePaymentDisplay.textContent = '0.00';
                // Optionally show an error message for invalid date range
                return;
            }

            const timeDiff = checkOut.getTime() - checkIn.getTime();
            const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));

            if (nights > 0) {
                const totalPrice = nights * pricePerNight;
                const advancePayment = totalPrice / 2;
                totalNightsDisplay.textContent = nights;
                totalPriceDisplay.textContent = totalPrice.toFixed(2);
                advancePaymentDisplay.textContent = advancePayment.toFixed(2);
            } else {
                totalNightsDisplay.textContent = '0';
                totalPriceDisplay.textContent = '0.00';
                advancePaymentDisplay.textContent = '0.00';
            }
        }

        if (bookingInDateInput && bookingOutDateInput) {
            bookingInDateInput.addEventListener('change', calculatePrice);
            bookingOutDateInput.addEventListener('change', calculatePrice);
            // Set min date for check-in to today
            const today = new Date().toISOString().split('T')[0];
            bookingInDateInput.setAttribute('min', today);
            bookingOutDateInput.setAttribute('min', today); // Also for checkout, though logic will enforce it's after checkin
        }

        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(bookingForm);
                const bookingMessage = document.getElementById('bookingMessage');
                const submitButton = bookingForm.querySelector('button[type="submit"]');

                // Client-side validation against fetched booked dates
                const selectedCheckIn = bookingInDateInput.value;
                const selectedCheckOut = bookingOutDateInput.value;
                if (selectedCheckIn && selectedCheckOut && roomBookedDates.length > 0) {
                    let currentDate = new Date(selectedCheckIn);
                    const endDate = new Date(selectedCheckOut);
                    let conflictFound = false;
                    while(currentDate < endDate) {
                        const formattedCurrentDate = currentDate.toISOString().split('T')[0];
                        if (roomBookedDates.includes(formattedCurrentDate)) {
                            conflictFound = true;
                            break;
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    if (conflictFound) {
                        bookingMessage.innerHTML = `<div class="alert alert-danger">The selected date range includes dates that are already booked for this room. Please choose different dates.</div>`;
                        // No need to disable button here, just prevent submission
                        return; // Stop form submission
                    }
                }



                bookingMessage.innerHTML = '';
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                fetch('php/process_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        bookingMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        bookingForm.reset();
                        calculatePrice(); // Reset price display
                        setTimeout(() => {
                            const modalInstance = bootstrap.Modal.getInstance(bookingModalElement);
                            if (modalInstance) modalInstance.hide();
                            // Optionally redirect to a booking confirmation page or dashboard
                            // window.location.href = 'dashboard.html?booking_id=' + data.booking_id;
                        }, 3000);
                    } else {
                        bookingMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    bookingMessage.innerHTML = `<div class="alert alert-danger">An error occurred. Please try again.</div>`;
                    console.error('Booking Error:', error);
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Confirm Booking & Pay Advance';
                });
            });
        }
    });
  </script>
</body>
</html>