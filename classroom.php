<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$training_id = $_GET['training_id'] ?? null;

if (!$training_id) {
    echo "Invalid access: Training ID not specified.";
    exit();
}

// Fetch training info
$stmt = $conn->prepare("SELECT * FROM trainings WHERE id = ?");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$training) {
    echo "Training not found.";
    exit();
}

// Access control
$today = date('Y-m-d');
$trainingDate = date('Y-m-d', strtotime($training['training_date']));
$hasAccess = false;

if ($role === 'trainer' && $training['department'] === $department) {
    $hasAccess = true;
} elseif ($role !== 'trainer') {
    $check = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND training_id = ?");
    $check->bind_param("ii", $userId, $training_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0 && $today === $trainingDate) {
        $hasAccess = true;
    }
    $check->close();
}

if (!$hasAccess) {
    echo "<p style='color:red;'>⛔ Access denied. You can only access this classroom on the training date or if you're the assigned trainer.</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Classroom - <?= htmlspecialchars($training['title']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; background: #f0f4f8; }
    .container { background: white; padding: 25px; border-radius: 10px; max-width: 800px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    h2 { color: #003366; }
    .meta { margin: 10px 0; }
    .content, .module { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; }
    .button {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 18px;
      background: #00793a;
      color: white;
      text-decoration: none;
      border-radius: 6px;
    }
    input, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>📘 <?= htmlspecialchars($training['title']) ?></h2>
  <p class="meta"><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($training['training_date'])) ?></p>
  <p class="meta"><strong>Mode:</strong> <?= htmlspecialchars($training['mode_of_delivery']) ?> |
     <strong>Assessment:</strong> <?= htmlspecialchars($training['assessment_type']) ?></p>

  <div class="content">
    <h4>📄 Description:</h4>
    <p><?= nl2br(htmlspecialchars($training['description'])) ?></p>

    <?php if ($training['material_link']): ?>
      <h4>📂 Material:</h4>
      <a class="button" href="<?= htmlspecialchars($training['material_link']) ?>" target="_blank">Open Material</a>
    <?php else: ?>
      <p>No material provided yet.</p>
    <?php endif; ?>
  </div>

  <?php if ($role === 'trainer'): ?>
    <div class="content">
      <h3>📝 Add Module/Material</h3>
      <form method="POST" action="add_material.php" enctype="multipart/form-data">
        <input type="hidden" name="training_id" value="<?= $training_id ?>">
        <label>Module Title:</label>
        <input type="text" name="module_title" required>

        <label>Description:</label>
        <textarea name="module_description" required></textarea>

        <label>Upload File (PDF/Video):</label>
        <input type="file" name="material_file">

        <label>or Provide Link:</label>
        <input type="url" name="material_link" placeholder="https://...">

        <button class="button" type="submit">➕ Add Content</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="content">
    <h3>📘 Classroom Content</h3>
    <?php
    $m = $conn->prepare("SELECT * FROM classroom_materials WHERE training_id = ?");
    $m->bind_param("i", $training_id);
    $m->execute();
    $materials = $m->get_result();

    if ($materials->num_rows > 0) {
      while ($mod = $materials->fetch_assoc()) {
        echo "<div class='module'>";
        echo "<h4>" . htmlspecialchars($mod['module_title']) . "</h4>";
        echo "<p>" . nl2br(htmlspecialchars($mod['module_description'])) . "</p>";
        if (!empty($mod['material_link'])) {
          echo "<p><a href='" . htmlspecialchars($mod['material_link']) . "' target='_blank'>🔗 Open Link</a></p>";
        } elseif (!empty($mod['material_file'])) {
          echo "<p><a href='" . htmlspecialchars($mod['material_file']) . "' target='_blank'>📁 Download File</a></p>";
        }
        echo "</div>";
      }
    } else {
      echo "<p>No classroom content uploaded yet.</p>";
    }
    $m->close();
    ?>
  </div>

  <a class="button" href="view_trainings.php">⬅ Back to Trainings</a>
</div>

</body>
</html>
