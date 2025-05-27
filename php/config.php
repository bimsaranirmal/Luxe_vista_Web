<?php
$conn = new mysqli("localhost", "root", "bimsara123", "luxe_vista");

    // For AJAX requests, we prefer to handle errors in the calling script with JSON.
    // For direct script access, die is okay. This is a simple check.
    // A more robust way might involve a constant defined by AJAX handlers.
    if ($conn->connect_error && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) {
        die("Connection failed: " . htmlspecialchars($conn->connect_error));
}
?>
