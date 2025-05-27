
<?php
session_start();
// If the user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
require 'php/config.php'; // For any potential future use, though not strictly needed for display if all data comes via JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings - LuxeVista</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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
    .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color: var(--accent); }
    .navbar .btn-user-action { background-color: var(--accent); border: 2px solid var(--accent); color: var(--primary); font-weight: 600; }
    .navbar .btn-user-action:hover { background-color: transparent; color: var(--accent); }

    .page-header {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('images/banners/banner-my-bookings.jpg') no-repeat center center; /* Create a suitable banner image */
      background-size: cover;
      padding: 80px 0;
      color: white;
      text-align: center;
      margin-bottom: 40px;
    }
    .page-header h1 { font-size: 3rem; font-weight: 700; }

    .booking-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
        margin-bottom: 25px;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .booking-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .booking-card-header {
        background-color: var(--secondary);
        color: white;
        padding: 15px 20px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        font-size: 1.2rem;
        font-weight: 600;
    }
    .booking-card-body {
        padding: 20px;
    }
    .booking-card-body p { margin-bottom: 0.75rem; }
    .booking-card-body strong { color: var(--primary); }
    .booking-status {
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .booking-status-confirmed { background-color: #d1e7dd; color: #0f5132; } /* Bootstrap success light */
    .booking-status-pending { background-color: #fff3cd; color: #664d03; } /* Bootstrap warning light */
    .booking-status-cancelled { background-color: #f8d7da; color: #842029; } /* Bootstrap danger light */
    .booking-status-completed { background-color: #cfe2ff; color: #084298; } /* Bootstrap info light */
    .booking-status-upcoming { background-color: #e2e3e5; color: #41464b; } /* Bootstrap secondary light */

    .no-bookings {
        text-align: center;
        padding: 50px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
    }
    .no-bookings i { font-size: 3rem; color: var(--accent); margin-bottom: 20px; }

   .footer {
      background-color: var(--primary);
      color: var(--light);
      padding: 70px 0 0;
    }
    
    .footer-logo img {
      height: 70px;
      margin-bottom: 20px;
    }
    
    .footer-text {
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 30px;
      line-height: 1.7;
    }
    
    .footer-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 25px;
      color: white;
      position: relative;
    }
    
    .footer-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 40px;
      height: 2px;
      background: linear-gradient(to right, var(--accent), var(--gold));
    }
    
    .footer-links {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .footer-links li {
      margin-bottom: 12px;
    }
    
    .footer-links a {
      color: rgba(255, 255, 255, 0.7);
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .footer-links a:hover {
      color: var(--accent);
      padding-left: 5px;
    }
    
    .footer-contact i {
      color: var(--accent);
      margin-right: 10px;
      width: 20px;
    }
    
    .footer-contact p {
      margin-bottom: 15px;
      color: rgba(255, 255, 255, 0.7);
    }
    
    .social-links {
      margin-top: 30px;
    }
    
    .social-links a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: rgba(255, 255, 255, 0.1);
      color: white;
      margin-right: 10px;
      transition: all 0.3s ease;
    }
    
    .social-links a:hover {
      background-color: var(--accent);
      transform: translateY(-5px);
    }
    
    .copyright {
      text-align: center;
      padding: 20px 0;
      margin-top: 50px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: rgba(255, 255, 255, 0.5);
      font-size: 0.9rem;
    }
    
    /* Loader */
    .loader-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 200px;
    }
    
    .loader {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid var(--accent);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
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
          <li class="nav-item"><a class="nav-link" href="dashboard.html">Dashboard</a></li>
          <li class="nav-item ms-lg-3" id="userAuthLinkContainer">
             <!-- Content will be filled by JS -->
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="page-header">
    <div class="container">
      <h1 class="animate__animated animate__fadeInDown">My Bookings</h1>
      <p class="lead animate__animated animate__fadeInUp animate__delay-0_5s">Review your reservation history and upcoming stays.</p>
    </div>
  </div>

  <main class="container py-4">
    <div id="bookingsContainer" class="row">
      <!-- Bookings will be loaded here by JavaScript -->
      <div class="col-12 text-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Loading bookings...</span>
        </div>
        <p class="mt-2">Loading your bookings...</p>
      </div>
    </div>
  </main>

  <!-- Footer -->
 <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-3 col-md-6"><div class="footer-logo"><img src="images/logo.png" alt="LuxeVista Logo"></div><p class="footer-text">Experience unparalleled luxury and comfort at LuxeVista.</p><div class="social-links"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a></div></div>
        <div class="col-lg-3 col-md-6"><h4 class="footer-title">Quick Links</h4><ul class="footer-links"><li><a href="index.html">Home</a></li><li><a href="index.html#about">About Us</a></li><li><a href="index.html#rooms">Rooms</a></li><li><a href="index.html#testimonials">Testimonials</a></li><li><a href="#">Amenities</a></li><li><a href="#">Gallery</a></li><li><a href="index.html#contact">Contact</a></li></ul></div>
        <div class="col-lg-3 col-md-6"><h4 class="footer-title">Our Services</h4><ul class="footer-links"><li><a href="#">Restaurant & Bar</a></li><li><a href="#">Spa & Wellness</a></li><li><a href="#">Conference Room</a></li><li><a href="#">Swimming Pool</a></li><li><a href="#">Fitness Center</a></li><li><a href="#">Events & Celebrations</a></li></ul></div>
        <div class="col-lg-3 col-md-6"><h4 class="footer-title">Contact Us</h4><div class="footer-contact"><p><i class="fas fa-map-marker-alt"></i> 123 Luxury Ave, Colombo, Sri Lanka</p><p><i class="fas fa-phone-alt"></i> +94 11 234 5678</p><p><i class="fas fa-envelope"></i> info@luxevista.com</p><p><i class="fas fa-clock"></i> 24/7 Reception Service</p></div></div>
      </div>
    </div>
    <div class="copyright"><div class="container"><p>&copy; 2025 LuxeVista. All rights reserved.</p></div></div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookingsContainer = document.getElementById('bookingsContainer');
        const userAuthLinkContainer = document.getElementById('userAuthLinkContainer');

        fetch('php/get_user_info.php')
            .then(response => response.json())
            .then(data => {
                if (data.logged_in) {
                    userAuthLinkContainer.innerHTML = `
                        <span class="navbar-text text-white me-3">Hi, ${data.user_name.split(' ')[0]}</span>
                        <a class="btn btn-user-action ms-2" href="php/logout.php">Logout</a>`;
                    
                    if (data.bookings && data.bookings.length > 0) {
                        renderBookings(data.bookings);
                    } else {
                        bookingsContainer.innerHTML = `
                            <div class="col-12">
                                <div class="no-bookings animate__animated animate__fadeIn">
                                    <i class="fas fa-folder-open"></i>
                                    <h2>No Bookings Found</h2>
                                    <p>You haven't made any reservations yet. Why not explore our rooms?</p>
                                    <a href="all-rooms.html" class="btn btn-primary">Explore Rooms</a>
                                </div>
                            </div>`;
                    }
                } else {
                    // Should be redirected by PHP, but as a fallback:
                    window.location.href = 'login.html?redirect=my-bookings.php';
                }
            })
            .catch(error => {
                console.error('Error fetching user bookings:', error);
                bookingsContainer.innerHTML = '<div class="alert alert-danger col-12">Could not load your bookings. Please try again later.</div>';
            });

        function renderBookings(bookings) {
            bookingsContainer.innerHTML = ''; // Clear loading spinner
            bookings.forEach(booking => {
                const checkIn = new Date(booking.booking_in_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                const checkOut = new Date(booking.booking_out_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                
                const bookingStatusLower = (booking.booking_status || '').toLowerCase();
                let statusClass = 'booking-status-pending';
                let statusText = booking.booking_status || 'Pending';
                if (bookingStatusLower === 'confirmed') { statusClass = 'booking-status-confirmed'; }
                else if (bookingStatusLower === 'cancelled') { statusClass = 'booking-status-cancelled'; }
                else if (bookingStatusLower === 'completed') { statusClass = 'booking-status-completed'; }
                else if (bookingStatusLower === 'upcoming') { statusClass = 'booking-status-upcoming'; } // Assuming 'upcoming' is similar to 'confirmed' for cancellation
                else if (bookingStatusLower === 'cancellation requested') { statusClass = 'booking-status-pending'; statusText = 'Cancellation Requested'; }


                // Cancellation button logic
                let cancellationButtonHtml = '';
                const checkInDate = new Date(booking.booking_in_date);
                const today = new Date();
                today.setHours(0,0,0,0); // Normalize today to start of day
                const sevenDaysFromNow = new Date(today);
                sevenDaysFromNow.setDate(today.getDate() + 7);

                if ((bookingStatusLower === 'confirmed' || bookingStatusLower === 'upcoming') && checkInDate > sevenDaysFromNow) {
                    cancellationButtonHtml = `<button class="btn btn-sm btn-danger mt-2 direct-cancel-btn" data-booking-id="${booking.booking_id}">Cancel Booking</button>`;
                }


                const cardHtml = `
                    <div class="col-md-6 col-lg-4 animate__animated animate__fadeInUp">
                        <div class="booking-card">
                            <div class="booking-card-header">
                                ${booking.room_type || 'Room Booking'}
                            </div>
                            <div class="booking-card-body">
                                <p><strong>Booking ID:</strong> #${booking.booking_id}</p>
                                <p><strong>Room ID:</strong> ${booking.room_id}</p>
                                <p><strong>Check-in:</strong> ${checkIn}</p>
                                <p><strong>Check-out:</strong> ${checkOut}</p>
                                <p><strong>Total Price:</strong> $${parseFloat(booking.total_price).toFixed(2)}</p>
                                <p><strong>Payment Status:</strong> ${booking.payment_status || 'N/A'}</p>
                                <p><strong>Booking Status:</strong> <span class="booking-status ${statusClass}">${statusText}</span></p>
                                <div id="action-booking-${booking.booking_id}">
                                   ${cancellationButtonHtml}
                                </div>
                            </div>
                        </div>
                    </div>`;
                bookingsContainer.insertAdjacentHTML('beforeend', cardHtml);
            });
        }

        bookingsContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('direct-cancel-btn')) {
                const bookingId = event.target.dataset.bookingId;
                if (!confirm('Are you sure you want to cancel booking #' + bookingId + '? This action cannot be undone.')) {
                    return;
                }

                event.target.disabled = true;
                event.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

                const formData = new FormData();
                formData.append('action', 'user_direct_cancel'); // Action for the new script
                formData.append('booking_id', bookingId);
                fetch('php/user_cancel_booking.php', { // Target the new PHP script
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        // Update UI
                        const statusElement = document.getElementById(`status-booking-${bookingId}`);
                        if (statusElement) {
                            statusElement.textContent = 'Cancelled';
                            statusElement.className = 'booking-status booking-status-cancelled';
                        }
                        event.target.remove(); // Remove the button
                    } else {
                        alert('Error: ' + data.message);
                        event.target.disabled = false;
                        event.target.innerHTML = 'Cancel Booking';
                    }
                })
                .catch(error => {
                    console.error('Cancellation error:', error);
                    alert('An error occurred while cancelling the booking. Please try again.');
                    event.target.disabled = false;
                    event.target.innerHTML = 'Cancel Booking';
                });
            }
        });
    });
  </script>
</body>
</html>
