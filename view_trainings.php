<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}

include 'config.php';

$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

$userDept = $_SESSION['department'];
$userRegion = $_SESSION['region'];

$sql = "
  SELECT t.*
  FROM trainings t
  JOIN training_assignments ta ON t.id = ta.training_id
  JOIN departments d ON ta.department_id = d.id
  JOIN regions r ON ta.region_id = r.id
  WHERE (d.name = ? AND r.name = ?)
    AND (t.title LIKE ? OR t.training_date LIKE ?)
  ORDER BY t.training_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $userDept, $userRegion, $searchParam, $searchParam);
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

    .search-form input {
      padding: 10px;
      width: 300px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-bottom: 20px;
    }

    .card {
      background: white;
      padding: 20px;
      margin-bottom: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .card h3 { color: #0055aa; margin-bottom: 10px; }
    .card p { margin: 6px 0; font-size: 15px; color: #333; }
    .badge {
      background: #003366;
      color: white;
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 5px;
      display: inline-block;
      margin-top: 5px;
    }

    .enroll-btn {
      display: inline-block;
      margin-top: 10px;
      padding: 8px 16px;
      background: #00793a;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: 600;
    }

    .enroll-btn:hover { background: #00662f; }

    .no-data {
      font-style: italic;
      color: #777;
      padding: 20px;
      background: #fff;
      border-radius: 10px;
    }
  </style>
</head>
<body>

<h2>📚 Available Trainings for <?= htmlspecialchars($userDept) ?> / <?= htmlspecialchars($userRegion) ?></h2>
<a href="dashboard.php" style="
  display: inline-block;
  margin: 15px 0;
  padding: 10px 20px;
  background: #003366;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
">⬅ Back to Dashboard</a>


<form method="GET" class="search-form">
  <input type="text" name="search" placeholder="Search by title or date..." value="<?= htmlspecialchars($search) ?>">
</form>

<?php if ($result->num_rows > 0): ?>
  <?php while ($row = $result->fetch_assoc()) { ?>
    <div class="card">
      <h3><?= htmlspecialchars($row['title']) ?></h3>
      <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
      <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($row['training_date'])) ?></p>
      <p><strong>Time:</strong> <?= htmlspecialchars($row['time_of_delivery']) ?></p>
      <p><strong>Mode:</strong> <?= htmlspecialchars($row['mode_of_delivery']) ?></p>
      <p><strong>Assessment:</strong> <?= htmlspecialchars($row['assessment_type']) ?></p>
      <p><strong>Material:</strong> <a href="<?= htmlspecialchars($row['material_link']) ?>" target="_blank">View</a></p>
      <a class="enroll-btn" href="enroll.php?training_id=<?= $row['id'] ?>">Enroll</a>
    </div>
  <?php } ?>
<?php else: ?>
  <div class="no-data">No trainings found for your department or region.</div>
<?php endif; ?>

</body>
</html>
