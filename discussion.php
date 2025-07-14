<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$userId = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];

$module_id = $_GET['module_id'] ?? null;
$training_id = $_GET['training_id'] ?? null;

if (!$module_id || !$training_id) {
    die("Invalid access.");
}

// Handle new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $stmt = $conn->prepare("INSERT INTO comments (training_id, module_id, user_id, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $training_id, $module_id, $userId, $comment);
    $stmt->execute();
    $stmt->close();
    header("Location: discussion.php?training_id=$training_id&module_id=$module_id");
    exit();
}

// Fetch module info
$modStmt = $conn->prepare("SELECT module_title FROM classroom_materials WHERE id = ?");
$modStmt->bind_param("i", $module_id);
$modStmt->execute();
$modTitle = $modStmt->get_result()->fetch_assoc()['module_title'] ?? 'Module';
$modStmt->close();

// Fetch comments
$cstmt = $conn->prepare("
  SELECT c.*, u.fullname FROM comments c
  JOIN users u ON c.user_id = u.id
  WHERE c.module_id = ?
  ORDER BY c.created_at ASC
");
$cstmt->bind_param("i", $module_id);
$cstmt->execute();
$comments = $cstmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Discussion - <?= htmlspecialchars($modTitle) ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 40px; }
    .container { background: white; max-width: 800px; margin: auto; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #003366; margin-bottom: 20px; }
    .comment-box { border-bottom: 1px solid #ddd; padding: 10px 0; }
    .comment-box strong { color: #0055aa; }
    .meta { font-size: 12px; color: gray; }
    form textarea { width: 100%; height: 100px; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    button { background: #00793a; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-top: 10px; cursor: pointer; }
    a.back-btn { display: inline-block; margin-top: 20px; color: #003366; text-decoration: none; }
  </style>
</head>
<body>

<div class="container">
  <h2>💬 Discussion: <?= htmlspecialchars($modTitle) ?></h2>

  <form method="POST">
    <label for="comment">Post a comment:</label>
    <textarea name="comment" required placeholder="Write your thoughts or questions..."></textarea>
    <button type="submit">Send</button>
  </form>

  <hr>

  <?php if ($comments->num_rows > 0): ?>
    <?php while ($c = $comments->fetch_assoc()): ?>
      <div class="comment-box">
        <strong><?= htmlspecialchars($c['fullname']) ?></strong>
        <div class="meta"><?= date('M d, Y H:i', strtotime($c['created_at'])) ?></div>
        <p><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No comments yet. Start the discussion!</p>
  <?php endif; ?>

  <a class="back-btn" href="classroom.php?training_id=<?= $training_id ?>">⬅ Back to Classroom</a>
</div>

</body>
</html>