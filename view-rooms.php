<?php
require 'php/config.php';

$sql = "SELECT * FROM rooms";
$result = $conn->query($sql);

$rooms = [];

while ($room = $result->fetch_assoc()) {
    // Get images for this room
    $room_id = $room['room_id'];
    $imgSql = "SELECT image_path FROM room_images WHERE room_id = '$room_id' LIMIT 1";
    $imgResult = $conn->query($imgSql);
    $image = $imgResult->fetch_assoc();
    $room['main_image'] = $image ? $image['image_path'] : 'default.jpg'; // fallback image
    $rooms[] = $room;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Rooms - LuxeVista</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    <div class="row">
      <?php if (empty($rooms)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>No rooms found. Click "Add New Room" to get started.
            </div>
        </div>
      <?php else: ?>
        <?php foreach ($rooms as $room): ?>
          <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp"> <!-- Added mb-4 for spacing between cards -->
            <div class="card room-card h-100"> <!-- Added h-100 for equal height cards if needed -->
              <img src="<?php echo htmlspecialchars($room['main_image']); ?>" class="room-img" alt="<?php echo htmlspecialchars($room['room_type']); ?>">
              <div class="card-body d-flex flex-column"> <!-- Flex column for button alignment -->
                <h5 class="card-title"><?php echo htmlspecialchars($room['room_type']); ?></h5>
                <p class="card-text"><strong>ID:</strong> <?php echo htmlspecialchars($room['room_id']); ?></p>
                <p class="card-text"><strong>Capacity:</strong> <?php echo htmlspecialchars($room['capacity']); ?> person(s)</p>
                <p class="card-text flex-grow-1"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars(substr($room['description'], 0, 100) . (strlen($room['description']) > 100 ? '...' : ''))); ?></p> <!-- Shorten desc, add nl2br -->
                <p class="card-text"><strong>Price:</strong> <span class="price">$<?php echo htmlspecialchars($room['price_per_night']); ?></span> / night</p>
                <p class="card-text">
                  <strong>Availability:</strong>
                  <span class="availability <?php echo strtolower(str_replace(' ', '-', $room['availability'])); ?>"> <!-- Ensure class is valid -->
                    <?php echo htmlspecialchars($room['availability']); ?>
                  </span>
                </p>
                <div class="mt-auto"> <!-- Push buttons to bottom -->
                  <a href="php/edit-room.php?room_id=<?php echo $room['room_id']; ?>" class="btn btn-sm btn-primary me-1">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <form action="php/delete-room.php" method="POST" style="display: inline;">
                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
</div>

<script>
  // Animation script can be kept if desired, or removed if animations are handled by classes only
  // document.addEventListener('DOMContentLoaded', function () {
  //   const roomCards = document.querySelectorAll('.room-card');
  //   roomCards.forEach((card, index) => {
  //     setTimeout(() => {
  //       // card.style.opacity = 1; // Opacity can be handled by animate.css
  //       // card.style.transform = 'translateY(0)'; // Transform handled by animate.css
  //     }, index * 100); // Staggered animation
  //   });
  // });
</script>

</body>
</html>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const roomCards = document.querySelectorAll('.room-card');
    roomCards.forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = 1;
        card.style.transform = 'translateY(0)';
      }, index * 100); // Staggered animation
    });
  });
</script>

</body>
</html>