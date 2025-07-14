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

// Trainer access check
if ($role === 'trainer') {
    $trainerCheck = $conn->prepare("SELECT * FROM trainer_assignments WHERE trainer_id = ? AND training_id = ?");
    $trainerCheck->bind_param("ii", $userId, $training_id);
    $trainerCheck->execute();
    $trainerRes = $trainerCheck->get_result();
    $hasAccess = $trainerRes->num_rows > 0;
    $trainerCheck->close();
}
// Employee access check
elseif ($role !== 'trainer') {
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
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 40px; }
    .container { max-width: 900px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h2, h3 { color: #003366; }
    .meta { font-size: 14px; margin-bottom: 10px; color: #444; }
    .content, .module { margin-top: 20px; background: #f9f9f9; padding: 15px; border-radius: 8px; }
    .module { border-left: 5px solid #00793a; margin-bottom: 15px; }
    .button {
      background: #00793a; color: white;
      padding: 10px 16px; text-decoration: none;
      display: inline-block; margin-top: 10px; border-radius: 5px;
    }
    .button:hover { background: #005f2f; }
    input, textarea {
      width: 100%; padding: 10px;
      margin-top: 6px; margin-bottom: 12px;
      border: 1px solid #ccc; border-radius: 5px;
    }
    .comment-box { margin-top: 10px; background: #eee; padding: 10px; border-radius: 5px; }
    .comment-box small { display: block; color: #666; }
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

  <?php if ($role === 'trainer'): ?>
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

        <label>or Provide Link:</label>
        <input type="url" name="material_link" placeholder="https://...">

        <button class="button" type="submit">Upload Material</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="content">
    <h3>📚 Classroom Content & Discussions</h3>
    <?php
    $materialsStmt = $conn->prepare("SELECT * FROM classroom_materials WHERE training_id = ?");
    $materialsStmt->bind_param("i", $training_id);
    $materialsStmt->execute();
    $materials = $materialsStmt->get_result();

    while ($mod = $materials->fetch_assoc()):
      $module_id = $mod['id'];
    ?>
      <div class="module">
        <h4><?= htmlspecialchars($mod['module_title']) ?></h4>
        <p><?= nl2br(htmlspecialchars($mod['module_description'])) ?></p>
        <?php if (!empty($mod['material_link'])): ?>
          <p><a href="<?= htmlspecialchars($mod['material_link']) ?>" target="_blank" class="button">🔗 Open Link</a></p>
        <?php elseif (!empty($mod['material_file'])): ?>
          <p><a href="<?= htmlspecialchars($mod['material_file']) ?>" target="_blank" class="button">📁 Download File</a></p>
        <?php endif; ?>

        <!-- Discussion Section -->
        <div style="margin-top: 10px;">
          <a class="button" href="discussion.php?module_id=<?= $module_id ?>&training_id=<?= $training_id ?>">💬 View Full Discussion</a>
        </div>

        <!-- Show recent comments -->
        <?php
        $disStmt = $conn->prepare("SELECT d.comment, u.fullname, d.created_at FROM module_discussions d JOIN users u ON d.user_id = u.id WHERE d.module_id = ? ORDER BY d.created_at DESC LIMIT 3");
        $disStmt->bind_param("i", $module_id);
        $disStmt->execute();
        $discussions = $disStmt->get_result();

        while ($d = $discussions->fetch_assoc()):
        ?>
          <div class="comment-box">
            <strong><?= htmlspecialchars($d['fullname']) ?>:</strong><br>
            <?= nl2br(htmlspecialchars($d['comment'])) ?>
            <small><?= $d['created_at'] ?></small>
          </div>
        <?php endwhile; $disStmt->close(); ?>

        <!-- Post Comment Form -->
        <form method="POST" action="trainer/post_comment.php">
          <input type="hidden" name="training_id" value="<?= $training_id ?>">
          <input type="hidden" name="module_id" value="<?= $module_id ?>">
          <label>Add Comment:</label>
          <textarea name="comment" rows="2" required></textarea>
          <button class="button" type="submit">💬 Post Comment</button>
        </form>
      </div>
    <?php endwhile; $materialsStmt->close(); ?>
  </div>

  <a class="button" href="dashboard.php">⬅ Back to Dashboard</a>
</div>
</body>
</html>