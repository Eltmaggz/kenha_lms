<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_GET['training_id'] ?? null;

if ($training_id && is_numeric($training_id)) {
    // Check if already enrolled
    $check = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND training_id = ?");
    $check->bind_param("ii", $user_id, $training_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO enrollments (user_id, training_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $training_id);
        $insert->execute();
        $insert->close();
    }

    $check->close();
}

// Redirect back to view_trainings
header("Location: view_trainings.php");
exit();
?>
