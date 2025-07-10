<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_GET['training_id'] ?? null;

if (!$training_id) {
    echo "Invalid access: Training ID not specified.";
    exit();
}

// Check if user is enrolled
$check = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND training_id = ?");
$check->bind_param("ii", $user_id, $training_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo "❌ You are not enrolled in this training.";
    exit();
}

// Fetch training info
$training = $conn->prepare("SELECT * FROM trainings WHERE id = ?");
$training->bind_param("i", $training_id);
$training->execute();
$training_result = $training->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Classroom - <?= htmlspecialchars($training_result['title']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; background: #f0f4f8; }
    .container { background: white; padding: 25px; border-radius: 10px; max-width: 800px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    h2 { color: #003366; }
    .meta { margin: 10px 0; }
    .content { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; }
    a.button {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 18px;
      background: #00793a;
      color: white;
      text-decoration: none;
      border-radius: 6px;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>📘 <?= htmlspecialchars($training_result['title']) ?></h2>
  <p class="meta"><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($training_result['training_date'])) ?></p>
  <p class="meta"><strong>Mode:</strong> <?= htmlspecialchars($training_result['mode_of_delivery']) ?> | 
     <strong>Assessment:</strong> <?= htmlspecialchars($training_result['assessment_type']) ?></p>

  <div class="content">
    <h4>📄 Description:</h4>
    <p><?= nl2br(htmlspecialchars($training_result['description'])) ?></p>

    <?php if ($training_result['material_link']): ?>
      <h4>📂 Material:</h4>
      <a class="button" href="<?= htmlspecialchars($training_result['material_link']) ?>" target="_blank">Open Material</a>
    <?php else: ?>
      <p>No material provided yet.</p>
    <?php endif; ?>
  </div>

  <a class="button" href="view_trainings.php">⬅ Back to Trainings</a>
</div>

</body>
</html>
