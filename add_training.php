<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
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

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $title = $_POST['title'];
  $description = $_POST['description'];
  $training_date = $_POST['training_date'];
  $time_of_delivery = $_POST['time_of_delivery'];
  $mode_of_delivery = $_POST['mode_of_delivery'];
  $assessment_type = $_POST['assessment_type'];
  $departments = $_POST['departments'] ?? [];

  $stmt = $conn->prepare("INSERT INTO trainings (title, description, training_date, time_of_delivery, mode_of_delivery, assessment_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssssi", $title, $description, $training_date, $time_of_delivery, $mode_of_delivery, $assessment_type, $userId);
  $stmt->execute();
  $training_id = $stmt->insert_id;
  $stmt->close();

  if (!empty($departments)) {
    foreach ($departments as $dept_id) {
      $assignStmt = $conn->prepare("INSERT INTO training_assignments (training_id, department_id) VALUES (?, ?)");
      $assignStmt->bind_param("ii", $training_id, $dept_id);
      $assignStmt->execute();
      $assignStmt->close();
    }
  }

  $message = "✅ Training added successfully.";
}

$deptResult = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$allDepartments = $deptResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Training</title>
  <link rel="stylesheet" href="style.css">
  <script>
    function toggleSelectAll(source) {
      const checkboxes = document.querySelectorAll('.department-checkbox');
      checkboxes.forEach(cb => cb.checked = source.checked);
    }
  </script>
</head>
<body>
<div class="sidebar">
  <img src="<?= $profilePhoto ?>" alt="Profile Photo">
  <h3><?= htmlspecialchars($fullname) ?></h3>
  <p><?= htmlspecialchars($email) ?></p>
  <p><?= ucwords($role) ?> | <?= ucwords($department) ?> / <?= ucwords($region) ?></p>

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
  <h2>➕ Add New Training</h2>
  <?php if (!empty($message)) echo "<p class='success-message'>$message</p>"; ?>
  <form method="POST">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <label>Training Date</label>
    <input type="datetime-local" name="training_date" required>

    <label>Time of Delivery</label>
    <input type="text" name="time_of_delivery" required>

    <label>Mode of Delivery</label>
    <input type="text" name="mode_of_delivery" required>

    <label>Assessment Type</label>
    <input type="text" name="assessment_type" required>

    <label>Assign to Departments</label>
    <div>
      <label><input type="checkbox" onclick="toggleSelectAll(this)"> Select All</label>
    </div>
    <div class="checkbox-group">
      <?php foreach ($allDepartments as $dept): ?>
        <label class="checkbox-label">
          <input type="checkbox" class="department-checkbox" name="departments[]" value="<?= $dept['id'] ?>">
          <?= htmlspecialchars(ucwords($dept['name'])) ?>
        </label>
      <?php endforeach; ?>
    </div>

    <button type="submit">Add Training</button>
  </form>
</div>
</body>
</html>
