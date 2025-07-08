<?php
session_start();
include 'config.php';

if (isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $filename = time() . '_' . basename($file['name']);
    $targetPath = "uploads/" . $filename;

    // Optional validation
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (in_array($file['type'], $allowedTypes) && move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Update DB
        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE email = ?");
        $stmt->bind_param("ss", $filename, $_SESSION['email']);
        $stmt->execute();
        $_SESSION['profile_photo'] = $filename;
    }
}
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
