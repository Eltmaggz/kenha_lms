<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
  header("Location: index.html");
  exit();
}

include 'config.php';

$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$region = $_SESSION['region'];
$profilePhoto = !empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo']) 
  ? 'uploads/' . $_SESSION['profile_photo']
  : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// Fetch training progress per user
$query = "
  SELECT 
    t.title AS training_title,
    t.training_date,
    u.fullname AS learner_name,
    u.email AS learner_email,
    e.progress,
    e.completed
  FROM enrollments e
  JOIN trainings t ON e.training_id = t.id
  JOIN users u ON e.user_id = u.id
  ORDER BY t.training_date DESC, u.fullname
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📊 Training Progress Report</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table {
      width: 100%; border-collapse: collapse; margin-top: 20px;
    }
    th, td {
      padding: 10px; border: 1px solid #ccc; text-align: left;
    }
    th {
      background-color: #003366; color: white;
    }
    .completed { color: green; font-weight: bold; }
    .pending { color: red; font-weight: bold; }
    .ongoing { color: orange; font-weight: bold; }
    .sidebar .nav a.active {
      font-weight: bold; text-decoration: underline;
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
    <a href="view_trainings.php">📚 View Trainings</a>
    <a href="add_training.php">➕ Add Training</a>
    <a href="view_my_trainings.php">👤 My Trainings</a>
    <a href="approve_requests.php">✅ Approve Requests</a>
    <a class="active" href="reports.php">📊 Reports</a>
  </div>

  <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
  <h2>📊 Learner Progress Report</h2>

  <?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Training Title</th>
          <th>Date</th>
          <th>Learner Name</th>
          <th>Email</th>
          <th>Progress</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): 
          $status = $row['completed'] ? 'Completed' : ($row['progress'] > 0 ? 'Ongoing' : 'Pending');
        ?>
          <tr>
            <td><?= htmlspecialchars($row['training_title']) ?></td>
            <td><?= date('M d, Y', strtotime($row['training_date'])) ?></td>
            <td><?= htmlspecialchars($row['learner_name']) ?></td>
            <td><?= htmlspecialchars($row['learner_email']) ?></td>
            <td><?= intval($row['progress']) ?>%</td>
            <td class="<?= strtolower($status) ?>"><?= $status ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="no-data">No learner progress records found.</p>
  <?php endif; ?>
</div>

</body>
</html>
