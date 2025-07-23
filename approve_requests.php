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

// Fetch all pending training requests with department, region, and requester info
$query = "
  SELECT tr.*, u.fullname AS requester_name, d.name AS department_name, r.name AS region_name
  FROM training_requests tr
  JOIN users u ON tr.requested_by = u.id
  JOIN departments d ON tr.department_id = d.id
  JOIN regions r ON tr.region_id = r.id
  WHERE tr.status = 'pending'
  ORDER BY tr.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approve Training Requests</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
    th { background: #f4f4f4; }
    .approve-btn {
      background: #00793a;
      color: white;
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .approve-btn:hover {
      background: #005f2f;
    }
    .no-data {
      margin-top: 20px;
      font-style: italic;
      color: gray;
    }
  </style>
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
            <th>Department / Region</th>
            <th>Title</th>
            <th>Description</th>
            <th>Preferred Date</th>
            <th>Mode</th>
            <th>Assessment</th>
            <th>Suggested Trainer</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['requester_name']) ?></td>
              <td><?= htmlspecialchars($row['department_name'] . " / " . $row['region_name']) ?></td>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
              <td><?= htmlspecialchars($row['preferred_date']) ?></td>
              <td><?= htmlspecialchars($row['mode_of_delivery']) ?></td>
              <td><?= htmlspecialchars($row['assessment_type']) ?></td>
              <td><?= htmlspecialchars($row['suggested_trainer'] ?: 'N/A') ?></td>
              <td>
                <button type="submit" class="approve-btn" name="approve_id" value="<?= $row['id'] ?>">Approve</button>
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
