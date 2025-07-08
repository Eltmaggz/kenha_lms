<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    header("Location: index.html");
    exit();
}

include 'config.php';

$training_id = $_GET['training_id'] ?? null;
if (!$training_id) {
    echo "❌ Invalid training selected.";
    exit();
}

$training_stmt = $conn->prepare("SELECT title FROM trainings WHERE id = ?");
$training_stmt->bind_param("i", $training_id);
$training_stmt->execute();
$training_result = $training_stmt->get_result();
$training = $training_result->fetch_assoc();
$training_title = $training['title'] ?? 'Unknown Training';
$training_stmt->close();

$sql = "
  SELECT u.fullname, u.email, u.department, u.region, e.enrolled_at
  FROM enrollments e
  JOIN users u ON e.user = u.id
  WHERE e.training_id = ?
  ORDER BY e.enrolled_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enrolled Users - <?= htmlspecialchars($training_title) ?></title>
  <link rel="stylesheet" href="main.css">
</head>
<body>
<?php include 'profile_sidebar.php'; ?>
<div class="main-content">
  <h2>👥 Enrolled Users - <?= htmlspecialchars($training_title) ?></h2>
  <a href="view_my_trainings.php" class="back-btn">⬅ Back</a>

  <?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Email</th>
          <th>Department</th>
          <th>Region</th>
          <th>Enrolled At</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['fullname']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['region']) ?></td>
            <td><?= date('M d, Y H:i', strtotime($row['enrolled_at'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="no-data">No users have enrolled in this training yet.</p>
  <?php endif; ?>
</div>
</body>
</html>
