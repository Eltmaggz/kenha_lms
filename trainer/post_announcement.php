<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'trainer') {
    header("Location: ../index.html");
    exit();
}

include '../config.php';

$trainer_id = $_SESSION['user_id'];
$message = '';
$posted = false;

// Fetch trainings assigned to the trainer
$trainings = [];
$stmt = $conn->prepare("
    SELECT t.id, t.title FROM trainings t
    JOIN trainer_assignments ta ON t.id = ta.training_id
    WHERE ta.trainer_id = ?
");
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $trainings[] = $row;
}

// Handle post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_id = $_POST['training_id'] ?? null;
    $announcement = trim($_POST['message']);

    if ($training_id && $announcement) {
        $insert = $conn->prepare("INSERT INTO announcements (training_id, trainer_id, message) VALUES (?, ?, ?)");
        $insert->bind_param("iis", $training_id, $trainer_id, $announcement);
        $insert->execute();
        $posted = true;
        $message = "✅ Announcement posted!";
    } else {
        $message = "❌ Please select a training and write a message.";
    }
}

// View past announcements
$past = $conn->prepare("
    SELECT a.message, a.created_at, t.title 
    FROM announcements a
    JOIN trainings t ON a.training_id = t.id
    WHERE a.trainer_id = ?
    ORDER BY a.created_at DESC
");
$past->bind_param("i", $trainer_id);
$past->execute();
$past_announcements = $past->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📢 Post Announcement</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 30px; }
    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    h2 { color: #003366; }
    label { font-weight: bold; margin-top: 15px; display: block; }
    select, textarea, button {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    button {
      background: #00793a;
      color: white;
      font-weight: bold;
      border: none;
      margin-top: 20px;
      cursor: pointer;
    }
    .message { margin-top: 10px; font-weight: bold; color: green; }
    .announcement {
      background: #f1f4f9;
      padding: 15px;
      margin-top: 20px;
      border-left: 5px solid #00793a;
    }
    .announcement small { color: #555; }
    .back-btn {
      display: inline-block;
      margin-bottom: 15px;
      background: #003366;
      color: white;
      padding: 10px 15px;
      text-decoration: none;
      border-radius: 5px;
    }
  </style>
</head>
<body>
<div class="container">
  <a href="../dashboard.php" class="back-btn">⬅ Back to Dashboard</a>

  <h2>📢 Post an Announcement</h2>

  <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

  <form method="POST">
    <label for="training_id">Select Training</label>
    <select name="training_id" required>
      <option value="">-- Select Training --</option>
      <?php foreach ($trainings as $t): ?>
        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="message">Announcement Message</label>
    <textarea name="message" rows="5" required></textarea>

    <button type="submit">➕ Post Announcement</button>
  </form>

  <?php if ($past_announcements->num_rows > 0): ?>
    <h3>📜 Past Announcements</h3>
    <?php while ($a = $past_announcements->fetch_assoc()): ?>
      <div class="announcement">
        <strong><?= htmlspecialchars($a['title']) ?></strong><br>
        <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>
        <small>Posted on <?= date("M d, Y H:i", strtotime($a['created_at'])) ?></small>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>
</body>
</html>
