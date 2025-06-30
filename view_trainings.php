<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}
include 'config.php';

$department = $_SESSION['department'];
$region = $_SESSION['region'];

$stmt = $conn->prepare("SELECT * FROM trainings WHERE department = ? OR region = ?");
$stmt->bind_param("ss", $department, $region);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Trainings</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f4f7fc;
      padding: 40px;
    }

    h2 {
      color: #003366;
      margin-bottom: 20px;
    }

    .card {
      background: white;
      padding: 20px;
      margin-bottom: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .card h3 {
      color: #0055aa;
      margin: 0 0 10px;
    }

    .card p {
      margin: 6px 0;
      font-size: 15px;
      color: #333;
    }

    .card a {
      color: #00793a;
      font-weight: 600;
      text-decoration: none;
    }

    .no-data {
      font-style: italic;
      color: #777;
      padding: 20px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body>

  <h2>Available Trainings for <?= htmlspecialchars($department) ?> / <?= htmlspecialchars($region) ?></h2>

  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()) { ?>
      <div class="card">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($row['training_date']) ?></p>
        <p><strong>Material:</strong>
          <a href="<?= htmlspecialchars($row['material_link']) ?>" target="_blank">View</a>
        </p>
      </div>
    <?php } ?>
  <?php else: ?>
    <div class="no-data">No trainings found for your department or region.</div>
  <?php endif; ?>

</body>
</html>
