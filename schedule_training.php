<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'dept-head') {
  header("Location: index.html");
  exit();
}
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $title = $_POST['title'];
  $date = $_POST['date'];
  $description = $_POST['description'];
  $department = $_SESSION['department'];
  $region = $_SESSION['region'];

  $stmt = $conn->prepare("INSERT INTO scheduled_trainings (title, date, description, department, region) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssss", $title, $date, $description, $department, $region);
  $stmt->execute();
  $stmt->close();
  $conn->close();
  $message = "✅ Training scheduled successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Schedule Training</title>
  <style>
    body { font-family: Arial; background: #eef3f7; padding: 40px; }
    .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: auto; }
    h2 { color: #003366; }
    label { display: block; margin-top: 10px; }
    input, textarea { width: 100%; padding: 10px; margin-top: 5px; }
    button { margin-top: 20px; background: #00793a; color: white; border: none; padding: 10px 20px; border-radius: 6px; }
    .msg { margin-top: 20px; color: green; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Schedule Department Training</h2>
    <?php if (!empty($message)) echo "<p class='msg'>$message</p>"; ?>
    <form method="POST">
      <label>Training Title</label>
      <input type="text" name="title" required>
      <label>Date</label>
      <input type="date" name="date" required>
      <label>Description</label>
      <textarea name="description" required></textarea>
      <button type="submit">Schedule</button>
    </form>
  </div>
</body>
</html>
