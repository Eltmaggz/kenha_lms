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
<?php
$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'employee';
$email = $_SESSION['email'] ?? '';
$department = $_SESSION['department'] ?? 'N/A';
$region = $_SESSION['region'] ?? 'N/A';

$profilePhoto = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
if (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])) {
    $profilePhoto = 'uploads/' . $_SESSION['profile_photo'];
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
    <!-- SIDEBAR -->
  <div class="sidebar">
    <img src="<?= $profilePhoto ?>" alt="Profile Photo">
    <h3><?= htmlspecialchars($fullname) ?></h3>
    <p><?= htmlspecialchars($email) ?></p>
    <p><?= ucwords($role) ?> | <?= ucwords($department) ?> / <?= ucwords($region) ?></p>

    <form class="profile-upload" method="POST" action="upload_profile.php" enctype="multipart/form-data">
      <label for="profilePic" class="upload-label">📸 Upload Photo</label>
      <input type="file" id="profilePic" name="profile_photo" onchange="this.form.submit()">
    </form>
    <form method="POST" action="remove_photo.php">
      <button type="submit" class="remove-btn">❌ Remove Photo</button>
    </form>

    <div class="nav">
      <a href="dashboard.php">🏠 Dashboard</a>
      <?php if ($role !== 'trainer'): ?>
        <a href="view_trainings.php">📚 View Trainings</a>
      <?php endif; ?>

      <?php if ($role === 'hr'): ?>
        <a href="add_training.php">➕ Add Training</a>
        <a href="view_my_trainings.php">👤 My Trainings</a>
        <a href="approve_requests.php">✅ Approve Requests</a>
        <?php if ($role === 'hr'): ?>
  <a href="reports.php">📊 Reports</a>
<?php endif; ?>

      <?php endif; ?>

      <?php if ($role === 'dept-head'): ?>
        <a href="schedule_training.php">🗓️ Schedule Training</a>
      <?php endif; ?>

      <?php if ($role !== 'trainer'): ?>
        <a href="my_progress.php">📈 My Progress</a>
      <?php endif; ?>

      <?php if ($role === 'trainer'): ?>
        <a href="trainer/add_material.php?training_id=<?= $t['id'] ?>">➕ Add Material</a>
      <?php endif; ?>

     
    </div>

    <a href="logout.php" class="logout">🚪 Logout</a>
  </div>

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
