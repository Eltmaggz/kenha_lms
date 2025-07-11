<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}

include 'config.php';

$email = $_SESSION['email'];
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$region = $_SESSION['region'];
$userId = $_SESSION['user_id'];
$profilePhoto = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

if (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])) {
  $profilePhoto = 'uploads/' . $_SESSION['profile_photo'];
}

// Dynamic statistics
$pending = $scheduled = $reports = 0;

if (in_array($role, ['hr', 'dept-head', 'employee'])) {
  $pendingQuery = $conn->query("SELECT COUNT(*) FROM training_requests WHERE status = 'pending'");
  $pending = $pendingQuery ? ($pendingQuery->fetch_row()[0] ?? 0) : 0;

  $scheduledQuery = $conn->query("SELECT COUNT(*) FROM trainings WHERE WEEK(training_date) = WEEK(CURDATE())");
  $scheduled = $scheduledQuery ? ($scheduledQuery->fetch_row()[0] ?? 0) : 0;

  $reportsQuery = $conn->query("SELECT COUNT(*) FROM reports");
  $reports = $reportsQuery ? ($reportsQuery->fetch_row()[0] ?? 0) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KeNHA LMS Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard"><!-- WRAPPER FOR SIDEBAR + MAIN -->

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

    <!-- NAVIGATION -->
    <div class="nav">
      <a href="dashboard.php">🏠 Dashboard</a>
      <a href="view_trainings.php">📚 View Trainings</a>

      <?php if ($role === 'hr'): ?>
        <a href="add_training.php">➕ Add Training</a>
        <a href="view_my_trainings.php">👤 My Trainings</a>
        <a href="approve_requests.php">✅ Approve Requests</a>
      <?php endif; ?>

      <?php if ($role === 'dept-head'): ?>
        <a href="schedule_training.php">🗓️ Schedule Training</a>
      <?php endif; ?>

      <?php if ($role === 'employee'): ?>
        <a href="my_progress.php">📈 My Progress</a>
      <?php endif; ?>

      <a href="reports.php">📊 Reports</a>
    </div>

    <a href="logout.php" class="logout">🚪 Logout</a>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="top-bar">
      <h2>Welcome back, <?= htmlspecialchars($fullname) ?>!</h2>
      <div class="badges">
        <span class="badge">Department: <?= htmlspecialchars($department) ?></span>
        <span class="badge">Region: <?= htmlspecialchars($region) ?></span>
        <span class="badge">Role: <?= htmlspecialchars($role) ?></span>
      </div>
    </div>

    <div class="cards">
      <a class="card" href="view_trainings.php">
        <h3>📚 View Trainings</h3>
        <p><?= $scheduled ?> trainings scheduled this week</p>
      </a>
      <a class="card" href="my_progress.php">
        <h3>📈 My Progress</h3>
        <p>Track your enrolled trainings</p>
      </a>

      <?php if ($role === 'hr'): ?>
        <a class="card" href="add_training.php">
          <h3>➕ Add Training</h3>
          <p>Create a new training session</p>
        </a>
        <a class="card" href="view_my_trainings.php">
          <h3>👤 My Trainings</h3>
          <p>Manage your created trainings</p>
        </a>
        <a class="card" href="approve_requests.php">
          <h3>✅ Approve Requests</h3>
          <p><?= $pending ?> pending training requests</p>
        </a>
      <?php endif; ?>

      <?php if ($role === 'dept-head'): ?>
        <a class="card" href="schedule_training.php">
          <h3>🗓️ Schedule Training</h3>
          <p>Request training for your department</p>
        </a>
      <?php endif; ?>

      <a class="card" href="reports.php">
        <h3>📊 Reports</h3>
        <p><?= $reports ?> reports submitted</p>
      </a>
    </div>
  </div><!-- End Main Content -->

</div><!-- End Dashboard Wrapper -->
</body>
</html>
