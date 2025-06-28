<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$email = $_SESSION['email'];

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_', true) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
        // Update DB
        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE email = ?");
        $stmt->bind_param("ss", $filename, $email);
        $stmt->execute();
        $stmt->close();

        // Update session
        $_SESSION['profile_photo'] = $filename;
    }
}

header("Location: dashboard.php");
exit();
?>
