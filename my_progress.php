<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}
include 'config.php';

$email = $_SESSION['email'];

// Get user based on email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$user = $user['id'] ?? 0;
$stmt->close();

// Now fetch progress using user
$query = "
SELECT t.title, t.training_date, 
       CASE 
         WHEN e.completed = 1 THEN 'Completed'
         WHEN e.progress > 0 THEN 'Ongoing'
         ELSE 'Pending'
       END AS status
FROM trainings t
JOIN enrollments e ON t.id = e.training_id
WHERE e.user = ?
ORDER BY t.training_date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>My Training Progress</title>
  <style>
    body { font-family: Arial; background: #f4f7fc; padding: 40px; }
    h2 { color: #003366; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
    th { background-color: #003366; color: white; }
    tr:last-child td { border-bottom: none; }
    .status-completed { color: green; font-weight: bold; }
    .status-ongoing { color: #ff9800; font-weight: bold; }
    .status-pending { color: #bb0000; font-weight: bold; }
    .empty { margin-top: 20px; font-style: italic; color: #888; }
  </style>
</head>
<body>
  <h2>📈 My Training Progress</h2>

  <?php if ($result->num_rows > 0) { ?>
    <table>
      <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Status</th>
      </tr>
      <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
          <td><?php echo htmlspecialchars($row['title']); ?></td>
          <td><?php echo htmlspecialchars($row['training_date']); ?></td>
          <td class="status-<?php echo strtolower($row['status']); ?>">
            <?php echo htmlspecialchars($row['status']); ?>
          </td>
        </tr>
      <?php } ?>
    </table>
  <?php } else { ?>
    <p class="empty">You haven't enrolled in any training sessions yet.</p>
  <?php } ?>
</body>
</html>
