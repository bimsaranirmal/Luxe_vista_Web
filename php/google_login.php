<?php
session_start();
require 'config.php';

$data = json_decode(file_get_contents("php://input"));
$token = $data->id_token;

$clientId = "956907905937-cjr0ptmvvokkubipklkrg5d0nrgtsin3.apps.googleusercontent.com";
$client = new Google_Client(['client_id' => $clientId]);
$payload = $client->verifyIdToken($token);

if ($payload) {
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, google_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $google_id);
        $stmt->execute();
        $_SESSION['user_id'] = $stmt->insert_id;
    } else {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $_SESSION['user_id'] = $user_id;
    }

    echo "success";
} else {
    echo "invalid_token";
}
?>
