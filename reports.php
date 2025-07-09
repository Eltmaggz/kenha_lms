<?php
session_start();
if (!isset($_SESSION['email']) || ($_SESSION['role'] !== 'hr' && $_SESSION['role'] !== 'dept-head')) {
  header("Location: index.html");
  exit();
}
include 'config.php';

$result = $conn->query("
  SELECT r.*, t.title 
  FROM reports r 
  JOIN trainings t ON r.training_id = t.id 
  ORDER BY r.submitted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Training Reports</title>
  <link rel="stylesheet" href="main.css">
</head>
<body>
<?php include 'profile_sidebar.php'; ?>
<div class="main-content">
  <h2>📊 Training Reports</h2>
  <?php if ($result && $result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Training</th>
          <th>Submitted By</th>
          <th>Date</th>
          <th>Summary</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['submitted_by']) ?></td>
            <td><?= date('Y-m-d', strtotime($row['submitted_at'])) ?></td>
            <td><?= nl2br(htmlspecialchars($row['summary'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="no-data">No reports submitted yet.</p>
  <?php endif; ?>
</div>
</body>
</html>
