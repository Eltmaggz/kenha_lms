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

// Get training info
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

if ($role === 'TRAINER') {
    $trainerCheck = $conn->prepare("SELECT * FROM trainer_assignments WHERE trainer_id = ? AND training_id = ?");
    $trainerCheck->bind_param("ii", $userId, $training_id);
    $trainerCheck->execute();
    $trainerRes = $trainerCheck->get_result();
    $hasAccess = $trainerRes->num_rows > 0;
    $trainerCheck->close();
} elseif ($role !== 'TRAINER') {
    $enrollCheck = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND training_id = ?");
    $enrollCheck->bind_param("ii", $userId, $training_id);
    $enrollCheck->execute();
    $res = $enrollCheck->get_result();
    if ($res->num_rows > 0 && $today === $trainingDate) {
        $hasAccess = true;
    }
    $enrollCheck->close();
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
    body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 40px; }
    .container { max-width: 900px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h2, h3 { color: #003366; }
    .meta { font-size: 14px; margin-bottom: 10px; color: #444; }
    .content, .module { margin-top: 20px; background: #f9f9f9; padding: 15px; border-radius: 8px; }
    .module { border-left: 5px solid #00793a; margin-bottom: 15px; }
    .button {
      background: #00793a;
      color: white;
      padding: 10px 16px;
      text-decoration: none;
      display: inline-block;
      margin-top: 15px;
      border-radius: 5px;
    }
    .button:hover { background: #005f2f; }
    .discussion-link {
      display: inline-block;
      margin-top: 10px;
      font-size: 14px;
      color: #0066cc;
      text-decoration: none;
    }
    .discussion-link:hover {
      text-decoration: underline;
    }
    input, textarea, select {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      margin-bottom: 12px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    form label { font-weight: bold; }
  </style>
</head>
<body>

<div class="container">
  <h2>📘 <?= htmlspecialchars($training['title']) ?></h2>
  <p class="meta"><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($training['training_date'])) ?></p>
  <p class="meta"><strong>Mode:</strong> <?= htmlspecialchars($training['mode_of_delivery']) ?> |
     <strong>Assessment:</strong> <?= htmlspecialchars($training['assessment_type']) ?></p>

  <div class="content">
    <h3>📄 Description</h3>
    <p><?= nl2br(htmlspecialchars($training['description'])) ?></p>

    <?php if ($training['material_link']): ?>
      <p><strong>Material:</strong> <a href="<?= htmlspecialchars($training['material_link']) ?>" target="_blank" class="button">📁 Open Material</a></p>
    <?php else: ?>
      <p><strong>Material:</strong> No material uploaded by HR.</p>
    <?php endif; ?>
  </div>

  <?php if ($role === 'TRAINER'): ?>
    <div class="content">
      <h3>➕ Add Classroom Material</h3>
      <form method="POST" action="trainer/add_material.php" enctype="multipart/form-data">
        <input type="hidden" name="training_id" value="<?= $training_id ?>">
        <label>Module Title:</label>
        <input type="text" name="module_title" required>

        <label>Description:</label>
        <textarea name="module_description" required></textarea>

        <label>Upload File (PDF/Video):</label>
        <input type="file" name="material_file">

        <label>or Provide External Link:</label>
        <input type="url" name="material_link" placeholder="https://...">

        <button class="button" type="submit">Upload Material</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="content">
    <h3>📚 Classroom Content</h3>
    <?php
    $materialStmt = $conn->prepare("SELECT * FROM classroom_materials WHERE training_id = ?");
    $materialStmt->bind_param("i", $training_id);
    $materialStmt->execute();
    $materials = $materialStmt->get_result();

    if ($materials->num_rows > 0):
      while ($mat = $materials->fetch_assoc()):
        $modTitle = $mat['module_title'] ?? 'Untitled Module';
        $modDesc = $mat['module_description'] ?? 'No description available.';
    ?>
        <div class="module">
          <h4><?= htmlspecialchars($modTitle) ?></h4>
          <p><?= nl2br(htmlspecialchars($modDesc)) ?></p>
          <?php if (!empty($mat['material_link'])): ?>
            <p><a href="<?= htmlspecialchars($mat['material_link']) ?>" target="_blank" class="button">🔗 Open Link</a></p>
          <?php elseif (!empty($mat['material_file'])): ?>
            <p><a href="<?= htmlspecialchars($mat['material_file']) ?>" target="_blank" class="button">📁 Download File</a></p>
          <?php endif; ?>

          <a class="discussion-link" href="discussion.php?training_id=<?= $training_id ?>&module_id=<?= $mat['id'] ?>">💬 View Discussion</a>
        </div>
    <?php endwhile; else: ?>
      <p>No modules uploaded yet.</p>
    <?php endif;
    $materialStmt->close();
    ?>
  </div>

  <a class="button" href="dashboard.php">⬅ Back to Dashboard</a>
</div>

</body>
</html>
