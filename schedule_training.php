<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'DEPT-HEAD') {
  header("Location: index.html");
  exit();
}
include 'config.php';

// Session data
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];
$department = $_SESSION['department'];
$region = $_SESSION['region'];
$userId = $_SESSION['user_id'];

$profilePhoto = (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo']))
  ? 'uploads/' . $_SESSION['profile_photo']
  : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$message = "";
$isError = false;

// Handle submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $preferred_date = $_POST['preferred_date'];
  $mode = $_POST['mode_of_delivery'];
  $assessment = $_POST['assessment_type'];
  $suggested_trainer = trim($_POST['suggested_trainer']);
  $justification = trim($_POST['justification']);

  // Get department and region IDs
  $depQuery = $conn->prepare("SELECT id FROM departments WHERE name = ?");
  $depQuery->bind_param("s", $department);
  $depQuery->execute();
  $depQuery->bind_result($department_id);
  $depQuery->fetch();
  $depQuery->close();

  $regQuery = $conn->prepare("SELECT id FROM regions WHERE name = ?");
  $regQuery->bind_param("s", $region);
  $regQuery->execute();
  $regQuery->bind_result($region_id);
  $regQuery->fetch();
  $regQuery->close();

  // Insert request
  $stmt = $conn->prepare("INSERT INTO training_requests (
      title, description, preferred_date, mode_of_delivery, assessment_type,
      department_id, region_id, requested_by, suggested_trainer, justification
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param(
    "ssssssiiss",
    $title,
    $description,
    $preferred_date,
    $mode,
    $assessment,
    $department_id,
    $region_id,
    $userId,
    $suggested_trainer,
    $justification
  );

  if ($stmt->execute()) {
    $message = "✅ Training request sent successfully.";
  } else {
    $message = "❌ Failed to submit request: " . $stmt->error;
    $isError = true;
  }

  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Training Request</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .form-box label { display: block; margin-top: 10px; font-weight: bold; }
    .form-box input, .form-box select, .form-box textarea {
      width: 100%; padding: 10px; margin-top: 4px; margin-bottom: 12px;
      border: 1px solid #ccc; border-radius: 5px;
    }
    .success-msg { color: green; font-weight: bold; margin: 10px 0; }
    .error-msg { color: red; font-weight: bold; margin: 10px 0; }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <img src="<?= $profilePhoto ?>" alt="Profile Photo">
  <h3><?= htmlspecialchars($fullname) ?></h3>
  <p><?= htmlspecialchars($email) ?></p>
  <p><?= ucwords($role) ?> | <?= ucwords($department) ?> / <?= ucwords($region) ?></p>

  <form class="profile-upload" method="POST" action="upload_profile.php" enctype="multipart/form-data">
    <label class="upload-label" for="profilePic">📸 Upload Photo</label>
    <input type="file" name="profile_photo" id="profilePic" onchange="this.form.submit()">
  </form>
  <form method="POST" action="remove_photo.php">
    <button class="remove-btn" type="submit">❌ Remove Photo</button>
  </form>

  <div class="nav">
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="view_trainings.php">📚 View Trainings</a>
    <a href="schedule_training.php">📬 Request Training</a>
    <a href="my_progress.php">📈 My Progress</a>
  </div>
  <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
  <h2>📬 Create Training Request</h2>

  <?php if (!empty($message)): ?>
    <p class="<?= $isError ? 'error-msg' : 'success-msg' ?>"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="POST" class="form-box">
    <label>Training Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <label>Preferred Date</label>
    <input type="date" name="preferred_date" required>

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

    <label>Suggested Trainer (Optional)</label>
    <input type="text" name="suggested_trainer" placeholder="Trainer full name or email">

    <label>Justification / Reason for Request</label>
    <textarea name="justification" placeholder="Explain why this training is needed" required></textarea>

    <button type="submit">📤 Submit Request</button>
  </form>
</div>
</body>
</html>
