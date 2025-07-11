<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'trainer') {
    header("Location: index.html");
    exit();
}
include 'config.php';

$trainer_email = $_SESSION['email'];
$trainer_id = $_SESSION['user_id'];

// Fetch trainings assigned to this trainer (you’ll need a relation table: trainer_assignments)
$sql = "
    SELECT t.id, t.title, t.description, t.training_date
    FROM trainings t
    JOIN trainer_assignments ta ON t.id = ta.training_id
    WHERE ta.trainer_id = ?
    ORDER BY t.training_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Trainer Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 40px; }
    h2 { color: #003366; }
    .card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .card h3 { margin: 0 0 10px; }
    .btn {
      display: inline-block;
      padding: 8px 15px;
      margin-top: 10px;
      background: #00793a;
      color: white;
      text-decoration: none;
      border-radius: 5px;
    }
    .btn:hover { background: #005f2f; }
  </style>
</head>
<body>

<h2>👨‍🏫 Trainer Dashboard</h2>

<?php if ($result->num_rows > 0): ?>
  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><?= htmlspecialchars($row['description']) ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($row['training_date']) ?></p>
      <a href="classroom.php?training_id=<?= $row['id'] ?>" class="btn">Go to Classroom</a>
    </div>
  <?php endwhile; ?>
<?php else: ?>
  <p>No trainings assigned yet.</p>
<?php endif; ?>

</body>
</html>
