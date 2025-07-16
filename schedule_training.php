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
  $message = "✅ Training scheduled successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Schedule Training</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'profile_sidebar.php'; ?>
<div class="main-content">
  <h2>📅 Schedule Department Training</h2>
  <?php if (!empty($message)) echo "<p class='success-msg'>$message</p>"; ?>
  <form method="POST" class="form-box">
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
