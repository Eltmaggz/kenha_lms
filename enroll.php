<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$user_email = $_SESSION['email'];
$training_id = isset($_GET['training_id']) ? intval($_GET['training_id']) : 0;

// Validate training ID
if ($training_id <= 0) {
    die("Invalid training ID.");
}

// Get user ID
$user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$user_check->bind_param("s", $user_email);
$user_check->execute();
$user_result = $user_check->get_result();

if ($user_result->num_rows === 0) {
    die("User not found.");
}

$user = $user_result->fetch_assoc();
$user_id = $user['id'];

// Check if already enrolled
$check_enroll = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND training_id = ?");
$check_enroll->bind_param("ii", $user_id, $training_id);
$check_enroll->execute();
$check_enroll->store_result();

if ($check_enroll->num_rows > 0) {
    header("Location: view_trainings.php?message=already-enrolled");
    exit();
}

// Enroll user
$insert = $conn->prepare("INSERT INTO enrollments (user_id, training_id, enrollment_date) VALUES (?, ?, NOW())");
$insert->bind_param("ii", $user_id, $training_id);

if ($insert->execute()) {
    header("Location: view_trainings.php?message=enrolled-success");
    exit();
} else {
    echo "Enrollment failed.";
}
?>
