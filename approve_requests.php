<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'HR') {
  header("Location: index.html");
  exit();
}

include 'config.php';

$email = $_SESSION['email'];
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$region = $_SESSION['region'];
$userId = $_SESSION['user_id'];

$profilePhoto = (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo']))
  ? 'uploads/' . $_SESSION['profile_photo']
  : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$feedbackMessage = '';

// Handle form submissions for approve/reject/defer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST['action'] ?? '';
  $requestId = $_POST['request_id'] ?? 0;

  if ($action && $requestId) {
    if ($action === 'approve') {
      $stmt = $conn->prepare("UPDATE training_requests SET status = 'approved', hr_response = ?, decision_date = NOW() WHERE id = ?");
      $response = $_POST['hr_response'] ?? 'Approved';
      $stmt->bind_param("si", $response, $requestId);
      $stmt->execute();
      $feedbackMessage = "✅ Request approved.";
    } elseif ($action === 'reject') {
      $stmt = $conn->prepare("UPDATE training_requests SET status = 'rejected', hr_response = ?, decision_date = NOW() WHERE id = ?");
      $response = $_POST['hr_response'] ?? 'Rejected';
      $stmt->bind_param("si", $response, $requestId);
      $stmt->execute();
      $feedbackMessage = "❌ Request rejected.";
    } elseif ($action === 'defer') {
      $deferDate = $_POST['defer_date'] ?? null;
      $response = $_POST['hr_response'] ?? 'Deferred';
      $stmt = $conn->prepare("UPDATE training_requests SET status = 'deferred', hr_response = ?, deferred_date = ?, decision_date = NOW() WHERE id = ?");
      $stmt->bind_param("ssi", $response, $deferDate, $requestId);
      $stmt->execute();
      $feedbackMessage = "⏳ Request deferred.";
    }
  }
}

// Fetch all pending training requests
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
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
    th { background: #003366; color: white; }
    .actions form { margin-bottom: 10px; }
    .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
    .approve { background: #00793a; color: white; }
    .reject { background: #b30000; color: white; }
    .defer { background: #e69500; color: white; }
    textarea, input[type="date"] { width: 100%; padding: 5px; margin-top: 6px; }
    .feedback { margin: 10px 0; font-weight: bold; color: green; }
  </style>
</head>
<body>
<div class="main-content">
  <h2>✅ Approve Training Requests</h2>

  <?php if (!empty($feedbackMessage)) echo "<p class='feedback'>" . htmlspecialchars($feedbackMessage) . "</p>"; ?>

  <?php if ($result && $result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Requested By</th>
          <th>Dept / Region</th>
          <th>Title</th>
          <th>Description</th>
          <th>Date</th>
          <th>Mode</th>
          <th>Assessment</th>
          <th>Suggested Trainer</th>
          <th>Actions</th>
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
            <td class="actions">
              <!-- Approve -->
              <form method="POST">
                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                <textarea name="hr_response" placeholder="Approval remarks (optional)"></textarea>
                <button type="submit" name="action" value="approve" class="btn approve">✅ Approve</button>
              </form>

              <!-- Reject -->
              <form method="POST">
                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                <textarea name="hr_response" placeholder="Reason for rejection" required></textarea>
                <button type="submit" name="action" value="reject" class="btn reject">❌ Reject</button>
              </form>

              <!-- Defer -->
              <form method="POST">
                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                <label>New Suggested Date</label>
                <input type="date" name="defer_date" required>
                <textarea name="hr_response" placeholder="Reason for deferral" required></textarea>
                <button type="submit" name="action" value="defer" class="btn defer">⏳ Defer</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No pending training requests at the moment.</p>
  <?php endif; ?>
</div>
</body>
</html>
