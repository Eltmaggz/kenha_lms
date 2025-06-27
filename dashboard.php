<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'employee';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KeNHA LMS Dashboard</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      height: 100vh;
    }
    .sidebar {
      width: 250px;
      background-color: #002147;
      color: white;
      padding: 20px;
    }
    .sidebar h2 {
      font-size: 22px;
      margin-bottom: 30px;
    }
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 10px 0;
      font-size: 16px;
    }
    .sidebar a:hover {
      background-color: #004080;
      padding-left: 10px;
    }
    .main-content {
      flex: 1;
      background-color: #f4f4f4;
      padding: 30px;
      overflow-y: auto;
    }
    .main-content h1 {
      font-size: 26px;
      color: #333;
    }
    .card {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin-bottom: 10px;
    }
    .card p {
      color: #666;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h2>KeNHA LMS</h2>
  <p><strong><?php echo htmlspecialchars($fullname); ?></strong></p>
  <p>(<?php echo htmlspecialchars(ucfirst($role)); ?>)</p>
  <hr style="margin: 15px 0;">
  <a href="#">Dashboard</a>
  <a href="#">Profile</a>

  <?php if ($role === 'employee'): ?>
    <a href="#">My Trainings</a>
    <a href="#">Certificates</a>

  <?php elseif ($role === 'hr'): ?>
    <a href="#">All Employees</a>
    <a href="#">Training Reports</a>
    <a href="#">Schedule Training</a>

  <?php elseif ($role === 'hr-department'): ?>
    <a href="#">Department Training Reports</a>
    <a href="#">Employees in My Department</a>

  <?php elseif ($role === 'dept-head'): ?>
    <a href="#">Approve Requests</a>
    <a href="#">Team Progress</a>

  <?php elseif ($role === 'trainer'): ?>
    <a href="#">My Sessions</a>
    <a href="#">Submit Feedback</a>
  <?php endif; ?>

  <a href="logout.php">Logout</a>
</div>

<div class="main-content">
  <h1>Welcome, <?php echo htmlspecialchars($fullname); ?>!</h1>

  <?php if ($role === 'employee'): ?>
    <div class="card">
      <h3>Your Training Progress</h3>
      <p>You are enrolled in 3 courses. Next training: ERP Refresher - July 20.</p>
    </div>

  <?php elseif ($role === 'hr'): ?>
    <div class="card">
      <h3>Company-Wide Overview</h3>
      <p>Track, manage, and schedule training across departments.</p>
    </div>

  <?php elseif ($role === 'hr-department'): ?>
    <div class="card">
      <h3>Department Reports</h3>
      <p>View training stats for your department only.</p>
    </div>

  <?php elseif ($role === 'dept-head'): ?>
    <div class="card">
      <h3>Team Monitoring</h3>
      <p>Approve training requests and monitor staff progress in your department.</p>
    </div>

  <?php elseif ($role === 'trainer'): ?>
    <div class="card">
      <h3>Training Sessions</h3>
      <p>You have 2 upcoming sessions. Upload training material and record attendance.</p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
