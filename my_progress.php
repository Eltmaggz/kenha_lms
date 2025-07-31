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
$profilePhoto = !empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])
  ? 'uploads/' . $_SESSION['profile_photo']
  : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$userResult = $stmt->get_result();
$userId = $userResult->fetch_assoc()['id'] ?? 0;
$stmt->close();

$query = "
  SELECT t.title, t.training_date, e.progress, e.completed
  FROM trainings t
  JOIN enrollments e ON t.id = e.training_id
  WHERE e.user_id = ?
  ORDER BY t.training_date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$chartData = [];
while ($row = $result->fetch_assoc()) {
  $chartData[] = [$row['title'], intval($row['progress'])];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>📈 My Training Progress</title>
  <link rel="stylesheet" href="style.css">
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script>
    google.charts.load("current", { packages: ["corechart", "bar"] });
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
      var data = google.visualization.arrayToDataTable([
        ['Training', 'Progress (%)'],
        <?php foreach ($chartData as $d): ?>
          ['<?= addslashes($d[0]) ?>', <?= $d[1] ?>],
        <?php endforeach; ?>
      ]);

      var options = {
        title: 'Training Completion Progress',
        hAxis: { title: 'Progress (%)', minValue: 0, maxValue: 100 },
        vAxis: { title: 'Training' },
        chartArea: { width: '70%', height: '70%' },
        colors: ['#00793a']
      };

      var chart = new google.visualization.BarChart(document.getElementById('progress_chart'));
      chart.draw(data, options);
    }
  </script>
  <style>
    #progress_chart { margin-top: 40px; }
    .status-completed { color: green; }
    .status-ongoing { color: orange; }
    .status-pending { color: red; }
    .styled-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .styled-table th, .styled-table td { padding: 10px; border: 1px solid #ccc; }
    .styled-table th { background-color: #f0f0f0; }
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
    <label for="profilePic" class="upload-label">📸 Upload Photo</label>
    <input type="file" id="profilePic" name="profile_photo" onchange="this.form.submit()">
  </form>
  <form method="POST" action="remove_photo.php">
    <button type="submit" class="remove-btn">❌ Remove Photo</button>
  </form>

  <div class="nav">
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="view_trainings.php">📚 View Trainings</a>
    <?php if ($role === 'HR'): ?>
      <a href="add_training.php">➕ Add Training</a>
      <a href="view_my_trainings.php">👤 My Trainings</a>
      <a href="approve_requests.php">✅ Approve Requests</a>
      <a href="reports.php">📊 Reports</a>
    <?php endif; ?>
    <?php if ($role === 'DEPT-HEAD'): ?>
      <a href="schedule_training.php">🗓️ Schedule Training</a>
    <?php endif; ?>
    <?php if ($role !== 'TRAINER'): ?>
      <a href="my_progress.php">📈 My Progress</a>
    <?php endif; ?>
  </div>
  <a href="logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Main -->
<div class="main-content">
  <h2>📈 My Training Progress</h2>

  <?php if (!empty($chartData)): ?>
    <div id="progress_chart" style="width: 100%; height: 400px;"></div>

    <table class="styled-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Date</th>
          <th>Progress</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()):
          $status = $row['completed'] ? 'Completed' : ($row['progress'] > 0 ? 'Ongoing' : 'Pending');
        ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= date('M d, Y', strtotime($row['training_date'])) ?></td>
            <td><?= intval($row['progress']) ?>%</td>
            <td class="status-<?= strtolower($status) ?>"><?= $status ?></td>
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
