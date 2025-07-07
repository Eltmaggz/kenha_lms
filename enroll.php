<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_GET['training_id'] ?? null;

if ($training_id && $user_id) {
    // Check if already enrolled
    $check = $conn->prepare("SELECT id FROM enrollments WHERE user = ? AND training_id = ?");
    $check->bind_param("ii", $user_id, $training_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        // Not enrolled yet, insert
        $stmt = $conn->prepare("INSERT INTO enrollments (user, training_id, status, enrolled_at) VALUES (?, ?, 'enrolled', NOW())");
        $stmt->bind_param("ii", $user_id, $training_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "✅ Successfully enrolled!";
    } else {
        $_SESSION['message'] = "⚠️ You have already enrolled in this training.";
    }

    $check->close();
}

$conn->close();
header("Location: view_trainings.php");
exit();
?>
