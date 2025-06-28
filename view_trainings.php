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
<html>
<head>
  <meta charset="UTF-8">
  <title>Available Trainings</title>
  <style>
    body { font-family: Arial; background: #f4f7fc; padding: 40px; }
    .card { background: white; padding: 20px; margin-bottom: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h2 { color: #003366; }
    a { color: #00793a; text-decoration: none; }
  </style>
</head>
<body>
  <h2>Available Trainings</h2>
  <?php while ($row = $result->fetch_assoc()) { ?>
    <div class="card">
      <h3><?php echo htmlspecialchars($row['title']); ?></h3>
      <p><?php echo htmlspecialchars($row['description']); ?></p>
      <p><strong>Link:</strong> <a href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank">View</a></p>
    </div>
  <?php } ?>
</body>
</html>
