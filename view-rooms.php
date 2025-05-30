<?php
// This page will now use JavaScript to fetch and display rooms.
// The PHP logic for initially fetching all rooms is removed.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Rooms - LuxeVista</title>
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
      --dark-text: #333;
      --sidebar-width: 240px;
    }
    
    body {
      background: linear-gradient(135deg, rgba(26, 60, 64, 0.05), rgba(44, 102, 110, 0.05));
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--dark-text);
      margin: 0;
      min-height: 100vh;
    }
    
    /* Logo styling */
    .logo-container {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 2rem;
      padding: 1rem 0.5rem;
    }
    
    .logo {
      max-width: 80%;
      height: auto;
    }
    
    /* Sidebar styling */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--primary), var(--secondary));
      padding: 20px 0;
      color: white;
      box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      transition: all 0.3s ease;
    }
    
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .sidebar-item {
      margin: 8px 20px;
    }
    
    .sidebar-link {
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 12px 15px;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 500;
    }
    
    .sidebar-link:hover,
    .sidebar-link.active {
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateX(5px);
    }
    
    .sidebar-link.active {
      background: linear-gradient(90deg, var(--accent), var(--gold));
      box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
    }
    
    .sidebar-icon {
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }
    .main-content {
      margin-left: var(--sidebar-width);
      padding: 30px;
      transition: all 0.3s ease;
    }

  .page-header {
    color: var(--primary);
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--accent);
  }
  .page-header h1 { font-size: 2.5rem; font-weight: 700; }
  .page-header .lead { /* For subtitle, if added */
    font-size: 1.25rem;
    font-weight: 300;
    color: var(--secondary);
  }


    .room-card {
      max-width: 350px;
      max-height: 500px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
      margin-bottom: 20px;
      background: #fff;
    color: var(--dark-text); /* Changed from var(--primary) for card text */
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .room-card:hover {
      transform: translateY(-10px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .filter-form .form-control, .filter-form .form-select {
        background-color: #fff;
        border: 1px solid var(--secondary);
        color: var(--dark-text);
    }
    .filter-form .form-control:focus, .filter-form .form-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.2rem rgba(240, 182, 127, 0.25);
    }
    .filter-form label {
        color: var(--primary);
        font-weight: 500;
    }

    .room-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .card-body {
      padding: 15px;
    }

    .price {
    font-weight: bold;
    color: var(--gold);
  }

  .availability {
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
  }

  .availability.available {
    background-color: var(--success);
    color: #fff;
  }

  .availability.not-available {
    background-color: var(--error);
    color: #fff;
  }

  .btn-primary {
    background-color: var(--primary);
    border: none;
    transition: all 0.3s ease;
  }

  .btn-primary:hover {
    background-color: var(--secondary);
    color: #fff;
  }

  .btn-danger {
    background-color: var(--error);
    border: none;
    transition: all 0.3s ease;
  }

  .btn-danger:hover {
    background-color: #c0392b;
    color: #fff;
  }
  .loading-spinner {
      display: none; /* Hidden by default */
      text-align: center;
      padding: 20px;
  }
  .loading-spinner .spinner-border {
      width: 3rem;
      height: 3rem;
      color: var(--accent);
  }
  .no-rooms-message {
    color: var(--secondary);
  }
  </style>
</head>
<body>
<!-- Using container-fluid for full width within the iframe/content area -->
 <div class="main-content">
    <div class="page-header">
      <h1 class="animate__animated animate__fadeInDown">Manage Rooms</h1> <!-- Updated Title -->
      <p class="lead animate__animated animate__fadeInUp animate__delay-0_5s">View, add, edit, or delete hotel rooms.</p> <!-- Added Subtitle -->
    </div>

    <div class="mb-4"> <!-- Margin bottom for spacing -->
        <a href="add-room.html" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Room
        </a>
    </div>

    <!-- Filters Form -->
    <div class="card shadow-sm mb-4 filter-form">
      <div class="card-body">
        <h5 class="card-title" style="color: var(--primary);"><i class="fas fa-filter me-2"></i>Filter Rooms</h5>
        <form id="filterForm">
          <div class="row g-3">
            <div class="col-md-3">
              <label for="room_type_filter" class="form-label">Room Type</label>
              <select id="room_type_filter" name="room_type" class="form-select">
                <option value="all">All Types</option>
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Suite">Suite</option>
                <option value="Deluxe">Deluxe</option>
                <!-- Add other room types as needed -->
              </select>
            </div>
            <div class="col-md-2">
              <label for="min_capacity_filter" class="form-label">Min. Capacity</label>
              <input type="number" id="min_capacity_filter" name="min_capacity" class="form-control" min="1" placeholder="e.g., 2">
            </div>
            <div class="col-md-2">
              <label for="max_price_filter" class="form-label">Max Price</label>
              <input type="number" id="max_price_filter" name="max_price" class="form-control" min="0" step="10" placeholder="e.g., 300">
            </div>
            <div class="col-md-2">
              <label for="check_in_date_filter" class="form-label">Check-in</label>
              <input type="date" id="check_in_date_filter" name="check_in_date" class="form-control">
            </div>
            <div class="col-md-2">
              <label for="check_out_date_filter" class="form-label">Check-out</label>
              <input type="date" id="check_out_date_filter" name="check_out_date" class="form-control">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="show_all_availability_filter" name="show_all_availability">
                <label class="form-check-label" for="show_all_availability_filter">
                  Show All
                </label>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-12 text-end">
              <button type="button" id="applyFiltersBtn" class="btn btn-primary"><i class="fas fa-search me-1"></i> Apply Filters</button>
              <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary"><i class="fas fa-undo me-1"></i> Reset</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Loading rooms...</p>
    </div>

    <div class="row" id="roomsRow">
      <!-- Rooms will be loaded here by JavaScript -->
    </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const roomsRow = document.getElementById('roomsRow');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const filterForm = document.getElementById('filterForm');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');

    function fetchRooms(params = {}) {
      loadingSpinner.style.display = 'block';
      roomsRow.innerHTML = ''; // Clear previous results

      const queryParams = new URLSearchParams(params).toString();
      fetch(`php/view-rooms.php?${queryParams}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          loadingSpinner.style.display = 'none';
          if (data.error) {
            roomsRow.innerHTML = `<div class="col-12"><div class="alert alert-danger text-center">${data.error}</div></div>`;
            return;
          }
          if (data && data.length > 0) {
            displayRooms(data);
          } else {
            roomsRow.innerHTML = `<div class="col-12"><div class="alert alert-info text-center no-rooms-message" role="alert"><i class="fas fa-info-circle me-2"></i>No rooms found matching your criteria.</div></div>`;
          }
        })
        .catch(error => {
          loadingSpinner.style.display = 'none';
          roomsRow.innerHTML = `<div class="col-12"><div class="alert alert-danger text-center">Error loading rooms: ${error.message}. Please try again.</div></div>`;
          console.error('Error fetching rooms:', error);
        });
    }

    function displayRooms(rooms) {
      rooms.forEach((room, index) => {
        const description = room.description || '';
        const shortDescription = description.length > 100 ? description.substring(0, 97) + '...' : description;
        const availabilityClass = room.availability ? room.availability.toLowerCase().replace(' ', '-') : 'not-available';
        
        const roomCardHTML = `
          <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay: ${index * 0.1}s;">
            <div class="card room-card h-100">
              <img src="${room.main_image ? escapeHTML(room.main_image) : 'images/default-room.jpg'}" class="room-img" alt="${escapeHTML(room.room_type)}">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">${escapeHTML(room.room_type)}</h5>
                <p class="card-text"><strong>ID:</strong> ${escapeHTML(room.room_id)}</p>
                <p class="card-text"><strong>Capacity:</strong> ${escapeHTML(room.capacity)} person(s)</p>
                <p class="card-text flex-grow-1"><strong>Description:</strong> ${nl2br(escapeHTML(shortDescription))}</p>
                <p class="card-text"><strong>Price:</strong> <span class="price">$${parseFloat(room.price_per_night).toFixed(2)}</span> / night</p>
                <p class="card-text">
                  <strong>Availability:</strong>
                  <span style="color:black;" class="availability ${escapeHTML(availabilityClass)}">
                    ${escapeHTML(room.availability)}
                  </span>
                </p>
                <div class="mt-auto">
                  <a href="php/edit-room.php?room_id=${encodeURIComponent(room.room_id)}" class="btn btn-sm btn-primary me-1">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <form action="php/delete-room.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
                    <input type="hidden" name="room_id" value="${escapeHTML(room.room_id)}">
                    <button type="submit" style="background-color: red;" class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>`;
        roomsRow.insertAdjacentHTML('beforeend', roomCardHTML);
      });
    }

    applyFiltersBtn.addEventListener('click', () => {
      const formData = new FormData(filterForm);
      const params = {};
      for (let [key, value] of formData.entries()) {
        if (value) { // Only add if value is not empty
            if (key === 'show_all_availability') {
                 params[key] = document.getElementById('show_all_availability_filter').checked ? '1' : ''; // Send '1' if checked
            } else {
                params[key] = value;
            }
        }
      }
      // If show_all_availability is not checked and not explicitly sent, the backend defaults to 'Available' only
      if (!document.getElementById('show_all_availability_filter').checked && !params.hasOwnProperty('show_all_availability')) {
        // params.show_all_availability = '0'; // Or simply don't send it, backend handles empty as 'false'
      }
      fetchRooms(params);
    });

    resetFiltersBtn.addEventListener('click', () => {
      filterForm.reset();
      document.getElementById('show_all_availability_filter').checked = false; // Explicitly uncheck
      fetchRooms(); // Fetch all (or default)
    });

    // Initial load of rooms
    fetchRooms();

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    function nl2br(str) {
        return escapeHTML(str).replace(/(?:\r\n|\r|\n)/g, '<br>');
    }
  });
</script>

</body>
</html>