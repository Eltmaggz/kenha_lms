<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}

include 'config.php';

// Session variables
$email = $_SESSION['email'];
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$region = $_SESSION['region'];
$profilePhoto = !empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo']) 
  ? 'uploads/' . $_SESSION['profile_photo']
  : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// Get user_id
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$userId = $user['id'] ?? 0;
$stmt->close();

// Fetch training progress
$query = "
SELECT t.title, t.training_date, 
       CASE 
         WHEN e.completed = 1 THEN 'Completed'
         WHEN e.progress > 0 THEN 'Ongoing'
         ELSE 'Pending'
       END AS status
FROM trainings t
JOIN enrollments e ON t.id = e.training_id
WHERE e.user_id = ?
ORDER BY t.training_date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📈 My Training Progress</title>
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
  <h2>📈 My Training Progress</h2>

  <?php if ($result->num_rows > 0): ?>
    <table class="styled-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= date('M d, Y', strtotime($row['training_date'])) ?></td>
          <td class="status-<?= strtolower($row['status']) ?>">
            <?= htmlspecialchars($row['status']) ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty">You haven't enrolled in any training sessions yet.</p>
  <?php endif; ?>
</div>

</body>
</html>
