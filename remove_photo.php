<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$email = $_SESSION['email'];

// Get current photo
$res = $conn->prepare("SELECT profile_photo FROM users WHERE email = ?");
$res->bind_param("s", $email);
$res->execute();
$res->bind_result($photo);
$res->fetch();
$res->close();

// Delete actual photo if it's not empty
if (!empty($photo) && $photo !== 'default-avatar.png') {
    $filePath = 'uploads/' . $photo;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Clear from DB and session
$stmt = $conn->prepare("UPDATE users SET profile_photo = NULL WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->close();

unset($_SESSION['profile_photo']);

// Redirect to the previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
