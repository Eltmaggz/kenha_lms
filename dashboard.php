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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KeNHA LMS - Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #e6f0ff, #f2f9ff);
      padding: 40px;
    }

    .dashboard {
      max-width: 1200px;
      margin: auto;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: 32px;
      color: #003366;
    }

    .user-info {
      text-align: right;
      font-size: 14px;
      color: #333;
      margin-bottom: 20px;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }

    .card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      padding: 25px;
      transition: transform 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card h3 {
      color: #0055aa;
      margin-bottom: 10px;
      font-size: 20px;
    }

    .card p {
      font-size: 14px;
      color: #555;
    }

    .logout {
      display: inline-block;
      margin-top: 30px;
      padding: 10px 20px;
      background: #003366;
      color: white;
      border: none;
      border-radius: 6px;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <div class="header">
      <h1>Welcome to KeNHA LMS</h1>
    </div>

    <div class="user-info">
      Logged in as: <strong><?php echo $_SESSION['email']; ?></strong><br>
      Role: <strong><?php echo $_SESSION['role']; ?></strong>
    </div>

    <div class="cards">
      <!-- Common Features -->
      <div class="card">
        <h3>View Trainings</h3>
        <p>Explore available trainings for your department and region.</p>
      </div>

      <div class="card">
        <h3>My Progress</h3>
        <p>Track your completed, ongoing, and upcoming training sessions.</p>
      </div>

      <!-- HR Features -->
      <?php if ($_SESSION['role'] === 'hr') { ?>
      <div class="card">
        <h3>Add Training</h3>
        <p>Create new training programs for different departments and categories.</p>
      </div>

      <div class="card">
        <h3>Approve Requests</h3>
        <p>Review and respond to special or external training requests.</p>
      </div>

      <div class="card">
        <h3>Reports</h3>
        <p>Generate training and enrollment reports across the organization.</p>
      </div>
      <?php } ?>

      <!-- HOD Features -->
      <?php if ($_SESSION['role'] === 'dept-head') { ?>
      <div class="card">
        <h3>Schedule Training</h3>
        <p>Manage quarterly training sessions for your department.</p>
      </div>

      <div class="card">
        <h3>Department Requests</h3>
        <p>View training needs raised by team members and manage approvals.</p>
      </div>
      <?php } ?>
    </div>

    <a class="logout" href="logout.php">Logout</a>
  </div>
</body>
</html>