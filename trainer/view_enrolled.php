<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'TRAINER') {
    header("Location: ../index.html");
    exit();
}

include '../config.php';

$trainer_id = $_SESSION['user_id'];
$training_id = $_GET['training_id'] ?? null;

if (!$training_id) {
    echo "❌ No training selected.";
    exit();
}

// Validate: Ensure this training is assigned to the trainer
$check = $conn->prepare("
    SELECT t.title FROM trainings t 
    JOIN trainer_assignments ta ON t.id = ta.training_id
    WHERE ta.trainer_id = ? AND t.id = ?
");
$check->bind_param("ii", $trainer_id, $training_id);
$check->execute();
$result = $check->get_result();
$training = $result->fetch_assoc();

if (!$training) {
    echo "❌ You are not assigned to this training.";
    exit();
}

// Fetch enrolled users
$enrollments = $conn->prepare("
    SELECT u.fullname, u.email, u.department, u.region 
    FROM enrollments e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.training_id = ?
");
$enrollments->bind_param("i", $training_id);
$enrollments->execute();
$enrolled = $enrollments->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>👥 Enrolled Users - <?= htmlspecialchars($training['title']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 30px; }
    h2 { color: #003366; }
    table {
      border-collapse: collapse;
      width: 100%;
      background: white;
      margin-top: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    th { background: #00793a; color: white; }
    tr:hover { background: #f1f1f1; }
    .back-btn {
      display: inline-block;
      margin-bottom: 15px;
      background: #003366;
      color: white;
      padding: 10px 15px;
      text-decoration: none;
      border-radius: 5px;
    }
  </style>
</head>
<body>

<a class="back-btn" href="../dashboard.php">⬅ Back to Dashboard</a>
<h2>👥 Enrolled Users for Training: <?= htmlspecialchars($training['title']) ?></h2>

<?php if ($enrolled->num_rows > 0): ?>
  <table>
    <tr>
      <th>#</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Department</th>
      <th>Region</th>
    </tr>
    <?php $i = 1; while ($row = $enrolled->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($row['fullname']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= ucwords($row['department']) ?></td>
        <td><?= ucwords($row['region']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
<?php else: ?>
  <p>No users have enrolled yet.</p>
<?php endif; ?>

</body>
</html>
