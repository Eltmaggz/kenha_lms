<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'HR') {
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

// Handle approval action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve_id'])) {
  $id = $_POST['approve_id'];
  $stmt = $conn->prepare("UPDATE training_requests SET status = 'approved' WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Fetch all pending training requests with department, region, and requester info
$query = "
  SELECT tr.*, u.fullname AS requester_name, d.name AS department_name, r.name AS region_name
  FROM training_requests tr
  JOIN users u ON tr.requested_by = u.id
  JOIN departments d ON tr.department_id = d.id
  JOIN regions r ON tr.region_id = r.id
  WHERE tr.status = 'pending'
  ORDER BY tr.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approve Training Requests</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
    th { background: #1b1a1aff; }
    .approve-btn {
      background: #00793a;
      color: white;
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .approve-btn:hover {
      background: #005f2f;
    }
    .no-data {
      margin-top: 20px;
      font-style: italic;
      color: gray;
    }
  </style>
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


<div class="main-content">
  <h2>✅ Approve Training Requests</h2>

  <?php if ($result && $result->num_rows > 0): ?>
    <form method="POST">
      <table>
        <thead>
          <tr>
            <th>Requested By</th>
            <th>Department / Region</th>
            <th>Title</th>
            <th>Description</th>
            <th>Preferred Date</th>
            <th>Mode</th>
            <th>Assessment</th>
            <th>Suggested Trainer</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['requester_name']) ?></td>
              <td><?= htmlspecialchars($row['department_name'] . " / " . $row['region_name']) ?></td>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
              <td><?= htmlspecialchars($row['preferred_date']) ?></td>
              <td><?= htmlspecialchars($row['mode_of_delivery']) ?></td>
              <td><?= htmlspecialchars($row['assessment_type']) ?></td>
              <td><?= htmlspecialchars($row['suggested_trainer'] ?: 'N/A') ?></td>
              <td>
                <button type="submit" class="approve-btn" name="approve_id" value="<?= $row['id'] ?>">Approve</button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </form>
  <?php else: ?>
    <p class="no-data">No pending training requests.</p>
  <?php endif; ?>
</div>
</body>
</html>
