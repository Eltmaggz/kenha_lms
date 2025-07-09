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
$profilePhoto = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

if (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])) {
    $profilePhoto = 'uploads/' . $_SESSION['profile_photo'];
}

$sql = "
  SELECT t.*
  FROM trainings t
  JOIN training_assignments ta ON t.id = ta.training_id
  JOIN departments d ON ta.department_id = d.id
  JOIN regions r ON ta.region_id = r.id
  WHERE (d.name = ? AND r.name = ?)
    AND (t.title LIKE ? OR t.training_date LIKE ?)
  ORDER BY t.training_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $userDept, $userRegion, $searchParam, $searchParam);
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
<div class="sidebar">
  <img src="<?= $profilePhoto ?>" alt="Profile Photo">
  <h3><?= htmlspecialchars($fullname) ?></h3>
  <p><?= htmlspecialchars($email) ?></p>
  <p><?= ucwords($role) ?> | <?= ucwords($userDept) ?> / <?= ucwords($userRegion) ?></p>

  <form class="profile-upload" method="POST" action="upload_profile.php" enctype="multipart/form-data">
    <label for="profilePic" class="upload-label">📸 Upload Photo</label>
    <input type="file" id="profilePic" name="profile_photo" onchange="this.form.submit()">
  </form>

  <div class="nav">
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="view_trainings.php">📚 View Trainings</a>
    <a href="add_training.php">➕ Add Training</a>
    <a href="my_progress.php">📈 My Progress</a>
    <a href="reports.php">📊 Reports</a>
    <a href="schedule_training.php">🗓️ Schedule Training</a>
    <a href="view_my_trainings.php">👤 My Trainings</a>
    <a href="approve_requests.php">✅ Approve Requests</a>
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
    <?php while ($row = $result->fetch_assoc()) { ?>
      <div class="card">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
        <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($row['training_date'])) ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars($row['time_of_delivery']) ?></p>
        <p><strong>Mode:</strong> <?= htmlspecialchars($row['mode_of_delivery']) ?></p>
        <p><strong>Assessment:</strong> <?= htmlspecialchars($row['assessment_type']) ?></p>
        <p><strong>Material:</strong> <a href="<?= htmlspecialchars($row['material_link']) ?>" target="_blank">View</a></p>
        <?php
        $user_id = $_SESSION['user_id'];
        $tid = $row['id'];
        $enrolledCheck = $conn->prepare("SELECT id FROM enrollments WHERE user = ? AND training_id = ?");
        $enrolledCheck->bind_param("ii", $user_id, $tid);
        $enrolledCheck->execute();
        $enrolledCheck->store_result();

        if ($enrolledCheck->num_rows > 0) {
            echo '<span class="badge" style="background: green;">✅ Enrolled</span>';
        } else {
            echo '<a class="enroll-btn" href="enroll.php?training_id=' . $tid . '">Enroll</a>';
        }
        $enrolledCheck->close();
        ?>
      </div>
    <?php } ?>
  <?php else: ?>
    <div class="no-data">No trainings found for your department or region.</div>
  <?php endif; ?>
</div>
</body>
</html>
