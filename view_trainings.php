<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}

include 'config.php';

$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

$userDept = $_SESSION['department'];
$userRegion = $_SESSION['region'];
$fullname = $_SESSION['fullname'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'employee';
$user_id = $_SESSION['user_id'];

$profilePhoto = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
if (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])) {
    $profilePhoto = 'uploads/' . $_SESSION['profile_photo'];
}

$sql = "
  SELECT DISTINCT t.*
  FROM trainings t
  JOIN training_assignments ta ON t.id = ta.training_id
  JOIN departments d ON ta.department_id = d.id
  WHERE d.name = ?
    AND (t.title LIKE ? OR t.training_date LIKE ?)
  ORDER BY t.training_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $userDept, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KeNHA LMS - View Trainings</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard">
  <div class="sidebar">
    <img src="<?= $profilePhoto ?>" alt="Profile Photo">
    <h3><?= htmlspecialchars($fullname) ?></h3>
    <p><?= htmlspecialchars($email) ?></p>
    <p><?= ucwords($role) ?> | <?= ucwords($userDept) ?> / <?= ucwords($userRegion) ?></p>
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
        <a href="schedule_training.php">🗓️ Schedule Training</a>
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
  <div class="main-content">
    <div class="top-bar">
      <h2>Welcome back, <?= htmlspecialchars($fullname) ?>!</h2>
      <div class="badges">
        <span class="badge">Department: <?= htmlspecialchars($userDept) ?></span>
        <span class="badge">Region: <?= htmlspecialchars($userRegion) ?></span>
        <span class="badge">Role: <?= htmlspecialchars($role) ?></span>
      </div>
    </div>

    <h2>📚 Available Trainings for <?= htmlspecialchars($userDept) ?> / <?= htmlspecialchars($userRegion) ?></h2>

    <form method="GET" class="search-form">
      <input type="text" name="search" placeholder="Search by title or date..." value="<?= htmlspecialchars($search) ?>">
    </form>

    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()) {
        $tid = $row['id'];
        $trainingDate = date('Y-m-d', strtotime($row['training_date']));
        $today = date('Y-m-d');

        $check = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND training_id = ?");
        $check->bind_param("ii", $user_id, $tid);
        $check->execute();
        $check->store_result();
        $enrolled = $check->num_rows > 0;
        $check->close();
      ?>
        <div class="card">
          <h3><?= htmlspecialchars($row['title']) ?></h3>
          <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
          <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($row['training_date'])) ?></p>
          <p><strong>Time:</strong> <?= htmlspecialchars($row['time_of_delivery']) ?></p>
          <p><strong>Mode:</strong> <?= htmlspecialchars($row['mode_of_delivery']) ?></p>
          <p><strong>Assessment:</strong> <?= htmlspecialchars($row['assessment_type']) ?></p>
    

          <?php if ($enrolled): ?>
            <span class="badge" style="background: green;">✅ Enrolled</span><br>
            <?php if ($today === $trainingDate): ?>
              <a class="enroll-btn" style="margin-top:6px;background:#003366;" href="classroom.php?training_id=<?= $tid ?>">📘 To Classroom</a>
            <?php else: ?>
              <p style="color: gray; font-size: 13px;">Classroom opens on <?= date('M d, Y', strtotime($trainingDate)) ?></p>
            <?php endif; ?>
          <?php else: ?>
            <a class="enroll-btn" href="enroll.php?training_id=<?= $tid ?>">Enroll</a>
          <?php endif; ?>
        </div>
      <?php } ?>
    <?php else: ?>
      <div class="no-data">No trainings found for your department or region.</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>