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
$profilePhoto = !empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])
  ? 'uploads/' . $_SESSION['profile_photo']
  : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $training_date = $_POST['training_date'];
  $time_of_delivery = trim($_POST['time_of_delivery']);
  $mode_of_delivery = trim($_POST['mode_of_delivery']);
  $assessment_type = trim($_POST['assessment_type']);
  $departments = $_POST['departments'] ?? [];

  // Insert into trainings
  $stmt = $conn->prepare("
    INSERT INTO trainings (title, description, training_date, time_of_delivery, mode_of_delivery, assessment_type, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("ssssssi", $title, $description, $training_date, $time_of_delivery, $mode_of_delivery, $assessment_type, $userId);
  $stmt->execute();
  $training_id = $stmt->insert_id;
  $stmt->close();

  // Assign to selected departments + assign trainers automatically
  if (!empty($departments)) {
    foreach ($departments as $dept_id) {
      // Assign to department
      $assign = $conn->prepare("INSERT INTO training_assignments (training_id, department_id) VALUES (?, ?)");
      $assign->bind_param("ii", $training_id, $dept_id);
      $assign->execute();
      $assign->close();

      // Automatically assign trainers for each department
      $trainerQuery = $conn->prepare("SELECT id FROM users WHERE role = 'trainer' AND department = (SELECT name FROM departments WHERE id = ?)");
      $trainerQuery->bind_param("i", $dept_id);
      $trainerQuery->execute();
      $trainerResult = $trainerQuery->get_result();

      while ($trainer = $trainerResult->fetch_assoc()) {
        $trainer_id = $trainer['id'];

        // Check if already assigned
        $checkStmt = $conn->prepare("SELECT id FROM trainer_assignments WHERE trainer_id = ? AND training_id = ?");
        $checkStmt->bind_param("ii", $trainer_id, $training_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
          $assignTrainer = $conn->prepare("INSERT INTO trainer_assignments (trainer_id, training_id) VALUES (?, ?)");
          $assignTrainer->bind_param("ii", $trainer_id, $training_id);
          $assignTrainer->execute();
          $assignTrainer->close();
        }

        $checkStmt->close();
      }

      $trainerQuery->close();
    }
  }

  $message = "✅ Training added successfully and assigned to selected department(s).";
}

// Fetch departments
$departmentsRes = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$allDepartments = $departmentsRes->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Training - KeNHA LMS</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .checkbox-group { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; }
    .checkbox-label { display: flex; align-items: center; gap: 6px; }
    .success-message { color: green; font-weight: bold; margin: 15px 0; }
    button { background: #00793a; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-top: 20px; cursor: pointer; }
    label { display: block; margin-top: 15px; font-weight: bold; }
  </style>
  <script>
    function toggleSelectAll(master) {
      document.querySelectorAll('.department-checkbox').forEach(cb => cb.checked = master.checked);
    }
  </script>
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
  <h2>➕ Add New Training</h2>

  <?php if (!empty($message)): ?>
    <p class="success-message"><?= $message ?></p>
  <?php endif; ?>

  <form method="POST">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <label>Training Date</label>
    <input type="datetime-local" name="training_date" required>

    <label>Time of Delivery</label>
    <input type="text" name="time_of_delivery" required placeholder="e.g. 10:00 AM - 12:00 PM">

    <label>Mode of Delivery</label>
    <select name="mode_of_delivery" required>
      <option value="">--Select--</option>
      <option value="Instructor-Led Training (ILT)">Instructor-Led Training (ILT)</option>
      <option value="Virtual Instructor-Led Training (VILT)">Virtual Instructor-Led Training (VILT)</option>
      <option value="E-Learning (Self-Paced)">E-Learning (Self-Paced)</option>
      <option value="Blended Learning">Blended Learning</option>
      <option value="On-the-Job Training (OJT)">On-the-Job Training (OJT)</option>
    </select>

    <label>Assessment Type</label>
    <select name="assessment_type" required>
      <option value="">--Select--</option>
      <option value="Quizzes/MCQs">Quizzes/MCQs</option>
      <option value="Assignments/Projects">Assignments/Projects</option>
      <option value="Surveys/Feedback">Surveys/Feedback</option>
      <option value="Self-Assessments">Self-Assessments</option>
      <option value="Peer Reviews">Peer Reviews</option>
    </select>

    <label>Assign to Department(s)</label>
    <div>
      <label><input type="checkbox" onclick="toggleSelectAll(this)"> Select All</label>
    </div>
    <div class="checkbox-group">
      <?php foreach ($allDepartments as $dept): ?>
        <label class="checkbox-label">
          <input type="checkbox" class="department-checkbox" name="departments[]" value="<?= $dept['id'] ?>">
          <?= ucwords(htmlspecialchars($dept['name'])) ?>
        </label>
      <?php endforeach; ?>
    </div>

    <button type="submit">➕ Submit Training</button>
  </form>
</div>
</body>
</html>
