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

// Fetch training title
$training_sql = "SELECT title FROM trainings WHERE id = ?";
$training_stmt = $conn->prepare($training_sql);
$training_stmt->bind_param("i", $training_id);
$training_stmt->execute();
$training_result = $training_stmt->get_result();
$training = $training_result->fetch_assoc();
$training_title = $training['title'] ?? 'Unknown Training';
$training_stmt->close();

// Get enrolled users
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
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7faff;
      padding: 40px;
    }

    .container {
      background: white;
      border-radius: 10px;
      padding: 30px;
      max-width: 900px;
      margin: auto;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    h2 {
      color: #003366;
      margin-bottom: 20px;
    }

    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      background: #003366;
      color: white;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 6px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }

    th {
      background: #00793a;
      color: white;
    }

    tr:nth-child(even) {
      background: #f2f2f2;
    }

    .no-data {
      font-style: italic;
      color: #666;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="view_my_trainings.php" class="back-btn">⬅ Back to My Trainings</a>
    <h2>👥 Enrolled Users - <?= htmlspecialchars($training_title) ?></h2>

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
