<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

// Variables from session
$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'employee';
$email = $_SESSION['email'] ?? '';
$department = $_SESSION['department'] ?? 'N/A';
$region = $_SESSION['region'] ?? 'N/A';
$profilePhoto = $_SESSION['profile_photo'] ?? 'default-avatar.png';

// Get stats for HR
$pending = $scheduled = $reports = 0;

if ($role === 'hr') {
    $pending = $conn->query("SELECT COUNT(*) FROM training_requests WHERE status = 'pending'")->fetch_row()[0] ?? 0;
    $scheduled = $conn->query("SELECT COUNT(*) FROM trainings WHERE WEEK(training_date) = WEEK(CURDATE())")->fetch_row()[0] ?? 0;
    $reports = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row()[0] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
      margin: 0;
      padding: 40px;
    }

    .dashboard {
      max-width: 1200px;
      margin: auto;
    }

    .top-bar {
      background: #003366;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      border-radius: 10px;
    }

    .badge {
      background: #00793a;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 13px;
      margin-left: 10px;
    }

    .content-area {
      display: flex;
      gap: 30px;
      align-items: stretch;
    }

    .sidebar-profile {
      width: 270px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 20px;
      flex-shrink: 0;
      min-height: 400px;
    }

    .sidebar-profile img {
      width: 100%;
      height: 250px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .sidebar-profile p {
      font-size: 14px;
      margin: 5px 0;
      color: #444;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      flex: 1;
      min-height: 400px;
    }

    .card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      padding: 25px;
      transition: transform 0.3s;
      text-decoration: none;
      color: inherit;
    }

    .card:hover {
      transform: translateY(-5px);
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

    .upload-controls {
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="dashboard">

    <?php if ($role === 'hr') { ?>
      <div class="top-bar">
        <h2>HR Dashboard</h2>
        <div>
          <span class="badge"><?php echo $pending; ?> Pending Requests</span>
          <span class="badge"><?php echo $scheduled; ?> Trainings This Week</span>
          <span class="badge"><?php echo $reports; ?> Reports</span>
        </div>
      </div>
    <?php } ?>

    <div class="content-area">
      <div class="sidebar-profile">
        <h3>👤 Profile</h3>
        <img src="uploads/<?php echo $profilePhoto; ?>" alt="Profile Photo" onerror="this.src='default-avatar.png'">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($department); ?></p>
        <p><strong>Region:</strong> <?php echo htmlspecialchars($region); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?></p>

        <form class="upload-controls" action="upload_photo.php" method="POST" enctype="multipart/form-data">
          <input type="file" name="profile_photo" accept="image/*" required>
          <button type="submit">Upload</button>
        </form>

        <form class="upload-controls" action="remove_photo.php" method="POST">
          <button type="submit">Remove Photo</button>
        </form>
      </div>

      <div class="cards">
        <a href="view_trainings.php" class="card">
          <h3>View Trainings</h3>
          <p>Explore available trainings for your department and region.</p>
        </a>

        <a href="my_progress.php" class="card">
          <h3>My Progress</h3>
          <p>Track your completed, ongoing, and upcoming training sessions.</p>
        </a>

        <?php if ($role === 'hr') { ?>
          <a href="add_training.php" class="card">
            <h3>Add Training</h3>
            <p>Create new training programs for different departments.</p>
          </a>

          <a href="approve_requests.php" class="card">
            <h3>Approve Requests</h3>
            <p>Handle special or external training approvals.</p>
          </a>

          <a href="reports.php" class="card">
            <h3>Reports</h3>
            <p>Generate and view training statistics.</p>
          </a>
        <?php } ?>

        <?php if ($role === 'dept-head') { ?>
          <a href="schedule_training.php" class="card">
            <h3>Schedule Training</h3>
            <p>Manage department training plans.</p>
          </a>

          <a href="department_requests.php" class="card">
            <h3>Department Requests</h3>
            <p>Approve and track team training needs.</p>
          </a>
        <?php } ?>
      </div>
    </div>

    <a class="logout" href="logout.php">Logout</a>
  </div>
</body>
</html>
