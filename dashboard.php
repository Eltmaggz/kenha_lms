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

if (in_array($role, ['HR', 'DEPT-HEAD', 'STAFF', 'TRAINER'])) {
  $pending = $conn->query("SELECT COUNT(*) FROM training_requests WHERE status = 'pending'")->fetch_row()[0] ?? 0;

  // Filter scheduled trainings this week for the user's department
  $stmt = $conn->prepare("
    SELECT COUNT(DISTINCT t.id)
    FROM trainings t
    JOIN training_assignments ta ON t.id = ta.training_id
    JOIN departments d ON ta.department_id = d.id
    WHERE WEEK(t.training_date) = WEEK(CURDATE()) AND d.name = ?
  ");
  $stmt->bind_param("s", $department);
  $stmt->execute();
  $stmt->bind_result($scheduled);
  $stmt->fetch();
  $stmt->close();

  $reports = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row()[0] ?? 0;
}

// Trainer trainings
$trainerTrainings = [];
if ($role === 'trainer') {
  $stmt = $conn->prepare("
    SELECT t.id, t.title, t.description, t.training_date
    FROM trainings t
    JOIN trainer_assignments ta ON t.id = ta.training_id
    WHERE ta.trainer_id = ?
    ORDER BY t.training_date DESC
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $trainerTrainings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
<div class="dashboard">

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
      <?php if ($role !== 'TRAINER'): ?>
        <a href="view_trainings.php">📚 View Trainings</a>
      <?php endif; ?>

      <?php if ($role === 'HR'): ?>
        <a href="add_training.php">➕ Add Training</a>
        <a href="view_my_trainings.php">👤 My Trainings</a>
        <a href="approve_requests.php">✅ Approve Requests</a>
        <?php if ($role === 'HR'): ?>
  <a href="reports.php">📊 Reports</a>
<?php endif; ?>
      <?php endif; ?>

      <?php if ($role === 'DEPT-HEAD'): ?>
        <a href="schedule_training.php">📬 Request Training</a>
      <?php endif; ?>

      <?php if ($role !== 'TRAINER'): ?>
        <a href="my_progress.php">📈 My Progress</a>
      <?php endif; ?>

      <?php if ($role === 'TRAINER'): ?>
        <a href="trainer/add_material.php?training_id=<?= $t['id'] ?>">➕ Add Material</a>
      <?php endif; ?>

     
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
      <?php if ($role !== 'TRAINER'): ?>
        <a class="card" href="view_trainings.php">
          <h3>📚 View Trainings</h3>
          <p><?= $scheduled ?> trainings scheduled this week</p>
        </a>
      <?php endif; ?>

      <?php if ($role !== 'TRAINER'): ?>
        <a class="card" href="my_progress.php">
          <h3>📈 My Progress</h3>
          <p>Track your enrolled trainings</p>
        </a>
      <?php endif; ?>

      <?php if ($role === 'HR'): ?>
        <a class="card" href="add_training.php">
          <h3>➕ Add Training</h3>
          <p>Create new training sessions</p>
        </a>
        <a class="card" href="view_my_trainings.php">
          <h3>👤 My Trainings</h3>
          <p>Manage created trainings</p>
        </a>
        <a class="card" href="approve_requests.php">
          <h3>✅ Approve Requests</h3>
          <p><?= $pending ?> pending requests</p>
        </a>
      <?php endif; ?>

      <?php if ($role === 'DEPT-HEAD'): ?>
        <a class="card" href="schedule_training.php">
          <h3>📬 Request Training</h3>
          <p>Request new trainings</p>
        </a>
      <?php endif; ?>

      <?php if ($role === 'TRAINER'): ?>
<div class="card" style="padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; width: 100%; box-sizing: border-box;">
  <h3>🎓 Assigned Trainings</h3>
  <?php if (!empty($trainerTrainings)): ?>
    <?php foreach ($trainerTrainings as $t): ?>
      <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ccc;">
        <strong><?= htmlspecialchars($t['title']) ?></strong><br>
        <small><?= date('M d, Y H:i', strtotime($t['training_date'])) ?></small><br>
        <a href="classroom.php?training_id=<?= $t['id'] ?>" style="display: inline-block; margin-top: 6px; padding: 8px 14px; background: #003366; color: white; border-radius: 4px; text-decoration: none;">👨‍🏫 Go to Classroom</a>
        <a href="trainer/add_material.php?training_id=<?= $t['id'] ?>" style="display: inline-block; margin-top: 6px; padding: 8px 14px; background: #00793a; color: white; border-radius: 4px; text-decoration: none;">➕ Add Material</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No trainings assigned.</p>
  <?php endif; ?>
</div>

<?php endif; ?>

      <?php if ($role === 'HR'): ?>
  <a class="card" href="reports.php">
    <h3>📊 Reports</h3>
    <p><?= $reports ?> reports submitted</p>
  </a>
<?php endif; ?>

    </div>
    <?php if ($role !== 'TRAINER'): ?>
  <div class="progress-section" style="margin-top: 40px;">
    <h3 style="color: #003366;">📈 My Training Progress</h3>
    <?php
      $stmt = $conn->prepare("
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
      ");
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $progressResult = $stmt->get_result();

      if ($progressResult->num_rows > 0):
    ?>
      <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead>
          <tr style="background-color: #f0f0f0;">
            <th style="text-align: left; padding: 8px;">Title</th>
            <th style="text-align: left; padding: 8px;">Date</th>
            <th style="text-align: left; padding: 8px;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $progressResult->fetch_assoc()): ?>
            <tr>
              <td style="padding: 8px;"><?= htmlspecialchars($row['title']) ?></td>
              <td style="padding: 8px;"><?= date('M d, Y', strtotime($row['training_date'])) ?></td>
              <td style="padding: 8px; color: 
                <?= $row['status'] === 'Completed' ? 'green' : ($row['status'] === 'Ongoing' ? '#e69500' : 'red') ?>">
                <?= htmlspecialchars($row['status']) ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="margin-top: 10px;">No training enrolled yet.</p>
    <?php endif;
      $stmt->close();
    ?>
  </div>
<?php endif; ?>

  </div>
</div>
</body>
</html>
