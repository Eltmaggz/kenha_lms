<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'HR') {
  header("Location: index.html");
  exit();
}
include 'config.php';

// Handle approval action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve_id'])) {
  $id = $_POST['approve_id'];
  $stmt = $conn->prepare("UPDATE training_requests SET status = 'approved' WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Fetch all pending requests
$result = $conn->query("SELECT * FROM training_requests WHERE status = 'pending' ORDER BY requested_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approve Training Requests</title>
  <link rel="stylesheet" href="main.css">
</head>
<body>
<?php include 'profile_sidebar.php'; ?>
<div class="main-content">
  <h2>✅ Approve Training Requests</h2>

  <?php if ($result && $result->num_rows > 0): ?>
    <form method="POST">
      <table>
        <thead>
          <tr>
            <th>Requested By</th>
            <th>Training Title</th>
            <th>Reason</th>
            <th>Requested Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['requested_by']) ?></td>
              <td><?= htmlspecialchars($row['training_title']) ?></td>
              <td><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
              <td><?= date('Y-m-d', strtotime($row['requested_at'])) ?></td>
              <td>
                <button type="submit" name="approve_id" value="<?= $row['id'] ?>">Approve</button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </form>
  <?php else: ?>
    <p class="no-data">No pending training requests.</p>
  <?php endif; ?>
</div>
</body>
</html>
