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

<div class="sidebar">
  <div class="sidebar-profile">
    <div class="profile-pic-container">
      <img src="<?php echo $profilePhoto; ?>" alt="Profile Photo">

      <!-- Upload form -->
      <form action="upload_photo.php" method="POST" enctype="multipart/form-data">
        <label for="uploadInput">📷</label>
        <input type="file" id="uploadInput" name="profile_photo" accept="image/*" onchange="this.form.submit()" style="display:none;">
      </form>
    </div>

    <!-- Profile Info -->
    <h3 style="text-align:center;"><?php echo htmlspecialchars($fullname); ?></h3>
    <p><?php echo htmlspecialchars($email); ?></p>
    <p>Dept: <?php echo htmlspecialchars($department); ?></p>
    <p>Region: <?php echo htmlspecialchars($region); ?></p>
    <p>Role: <?php echo htmlspecialchars($role); ?></p>

    <!-- Remove Photo Button -->
    <form action="remove_photo.php" method="POST" style="text-align: center; margin-top: 10px;">
      <button type="submit" class="remove-btn">Remove Photo</button>
    </form>
  </div>

  <!-- Navigation based on role -->
  <div class="nav">
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="view_trainings.php">📚 View Trainings</a>

    <?php if ($role === 'HR'): ?>
      <a href="add_training.php">➕ Add Training</a>
      <a href="view_my_trainings.php">👤 My Trainings</a>
      <a href="reports.php">📊 Reports</a>
      <a href="approve_requests.php">✅ Approve Requests</a>

    <?php elseif ($role === 'DEPT-HEAD'): ?>
     <a href="schedule_training.php">📬 Request Training</a>
      <a href="reports.php">📊 Reports</a>

    <?php elseif ($role !== 'TRAINER'): ?>
      <a href="my_progress.php">📈 My Progress</a>
    <?php endif; ?>
  </div>

  <a href="logout.php" class="logout">🚪 Logout</a>
</div>
