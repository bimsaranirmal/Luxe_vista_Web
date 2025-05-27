<?php
require 'config.php';
// We are outputting HTML, so text/html is appropriate.
// header('Content-Type: application/json'); // Not needed if outputting HTML directly

if ($conn->connect_error) {
    // For simplicity in this example, we'll output an error message.
    echo '<div class="col-12 text-center"><p>Error connecting to the database.</p></div>';
    exit;
}
// --- End Database Connection ---

// Fetch approved reviews (or all if not using is_approved)
// Let's fetch a reasonable number for a slideshow, e.g., 5-10.
$sql = "SELECT guest_name, rating, review_text, created_at FROM reviews ORDER BY created_at DESC LIMIT 5"; 
$result = $conn->query($sql);

$reviews_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reviews_data[] = $row;
    }
}

if (empty($reviews_data)) {
    echo '<div class="col-12 text-center"><p>No reviews yet. Be the first to share your experience!</p></div>';
} else {
?>
<div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach ($reviews_data as $index => $review): ?>
        <button type="button" data-bs-target="#testimonialsCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach ($reviews_data as $index => $review):
            $guest_initial = strtoupper(substr($review['guest_name'], 0, 1));
            $rating_stars = str_repeat('<i class="fas fa-star"></i>', (int)$review['rating']) .
                            str_repeat('<i class="far fa-star"></i>', 5 - (int)$review['rating']);
            $review_date = date("M d, Y", strtotime($review['created_at']));
        ?>
        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
            <div class="container"> <!-- Optional: for consistent padding/centering -->
                <div class="row justify-content-center">
                    <div class="col-md-10 col-lg-8"> <!-- Adjust column size for review card width -->
                        <div class="review-card mx-auto" style="height: auto; min-height: 300px;"> <!-- Ensure card can grow, mx-auto for centering if col is wider -->
                            <div class="review-header">
                                <div class="review-avatar"><?php echo htmlspecialchars($guest_initial); ?></div>
                                <div class="review-author">
                                    <h5><?php echo htmlspecialchars($review['guest_name']); ?></h5>
                                    <div class="review-rating">
                                        <?php echo $rating_stars; ?>
                                    </div>
                                </div>
                            </div>
                            <p class="review-text">"<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"</p>
                            <div class="review-date"><?php echo $review_date; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>
<?php
}

$conn->close();
?>
