<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'employee';
$email = $_SESSION['email'] ?? '';
$department = $_SESSION['department'] ?? 'N/A';
$region = $_SESSION['region'] ?? 'N/A';

$profilePhoto = 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
if (!empty($_SESSION['profile_photo']) && file_exists('uploads/' . $_SESSION['profile_photo'])) {
    $profilePhoto = 'uploads/' . $_SESSION['profile_photo'];
}

$pending = $scheduled = $reports = 0;
if ($role === 'hr' || $role === 'dept-head' || $role === 'employee') {
    $pending = $conn->query("SELECT COUNT(*) FROM training_requests WHERE status = 'pending'")->fetch_row()[0] ?? 0;
    $scheduled = $conn->query("SELECT COUNT(*) FROM trainings WHERE WEEK(training_date) = WEEK(CURDATE())")->fetch_row()[0] ?? 0;
    $reports = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row()[0] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>KeNHA LMS - Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #e6f0ff, #f2f9ff);
    }

    .dashboard {
      height: 100%;
      display: flex;
      flex-direction: column;
      padding: 20px;
      box-sizing: border-box;
    }

    .top-bar {
      background: #003366;
      color: white;
      padding: 15px 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      border-radius: 10px;
      flex-wrap: wrap;
    }

    .badge {
      background: #ff8000;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      margin-left: 10px;
      margin-top: 5px;
    }

    .content-area {
      display: flex;
      flex: 1;
      flex-wrap: wrap;
      gap: 30px;
    }

    .sidebar-profile {
      width: 100%;
      max-width: 270px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      padding: 20px;
      flex-shrink: 0;
    }

    .profile-pic-container {
      position: relative;
      width: 160px;
      height: 160px;
      margin: auto;
    }

    .profile-pic-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid #003366;
    }

    .profile-pic-container label {
      position: absolute;
      bottom: 0;
      right: 0;
      background: #ff8000;
      border-radius: 50%;
      padding: 10px;
      cursor: pointer;
      font-size: 16px;
      color: #fff;
    }

    .sidebar-profile p {
      font-size: 14px;
      margin: 6px 0;
      color: #333;
      text-align: center;
    }

    .upload-controls {
      margin-top: 15px;
      text-align: center;
    }

    .remove-btn {
      padding: 8px 14px;
      font-size: 14px;
      background: #bb0000;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .remove-btn:hover {
      background: #990000;
    }

    .cards {
      flex: 1;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
    }

    .card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      padding: 25px;
      text-decoration: none;
      color: inherit;
      transition: transform 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .logout {
      display: inline-block;
      padding: 6px 14px;
      background: #003366;
      color: white;
      font-size: 14px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      cursor: pointer;
      transition: background 0.3s;
      width: auto;
      max-width: 150px;
      text-align: center;
      margin-top: 30px;
    }

    .logout:hover {
      background: #001f40;
    }

    @media (max-width: 768px) {
      .content-area {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <?php if (in_array($role, ['hr', 'dept-head', 'employee'])) { ?>
      <div class="top-bar">
        <h2>
          <?php
            if ($role === 'hr') echo 'HR Dashboard';
            elseif ($role === 'dept-head') echo 'Department Head Dashboard';
            else echo 'Staff Dashboard';
          ?>
        </h2>
        <div>
          <span class="badge"><?php echo $pending; ?> Pending Requests</span>
          <span class="badge"><?php echo $scheduled; ?> Trainings This Week</span>
          <span class="badge"><?php echo $reports; ?> Reports</span>
        </div>
      </div>
    <?php } ?>

    <div class="content-area">
      <div class="sidebar-profile">
        <h3 style="text-align:center;">👤 Profile</h3>
        <div class="profile-pic-container">
          <img src="<?php echo $profilePhoto; ?>" alt="Profile Photo">
          <form action="upload_photo.php" method="POST" enctype="multipart/form-data">
            <label for="uploadInput">📷</label>
            <input type="file" id="uploadInput" name="profile_photo" accept="image/*" onchange="this.form.submit()" style="display:none;">
          </form>
        </div>
        <p><strong><?php echo htmlspecialchars($fullname); ?></strong></p>
        <p><?php echo htmlspecialchars($email); ?></p>
        <p>Dept: <?php echo htmlspecialchars($department); ?></p>
        <p>Region: <?php echo htmlspecialchars($region); ?></p>
        <p>Role: <?php echo htmlspecialchars($role); ?></p>

        <form class="upload-controls" action="remove_photo.php" method="POST">
          <button type="submit" class="remove-btn">Remove Photo</button>
        </form>
      </div>

      <div class="cards">
        <a href="view_trainings.php" class="card">
          <h3>📋 View Trainings</h3>
          <p>Explore available trainings for your department and region.</p>
        </a>
        <a href="my_progress.php" class="card">
          <h3>📈 My Progress</h3>
          <p>Track your completed, ongoing, and upcoming training sessions.</p>
        </a>
        <?php if ($role === 'hr') { ?>
          <a href="add_training.php" class="card">
            <h3>➕ Add Training</h3>
            <p>Create training programs for your team.</p>
          </a>
          <a href="approve_requests.php" class="card">
            <h3>✅ Approve Requests</h3>
            <p>Review and approve training suggestions.</p>
          </a>
          <a href="reports.php" class="card">
            <h3>📊 Reports</h3>
            <p>Monitor statistics and analytics.</p>
          </a>
          <a href="view_my_trainings.php" class="card">
            <h3>📋 View My Trainings</h3>
            <p>View and manage trainings you have created.</p>
          </a>
        <?php } ?>
        <?php if ($role === 'dept-head') { ?>
          <a href="schedule_training.php" class="card">
            <h3>📅 Schedule Training</h3>
            <p>Manage departmental sessions.</p>
          </a>
          <a href="department_requests.php" class="card">
            <h3>📨 Department Requests</h3>
            <p>View and approve requests from your team.</p>
          </a>
        <?php } ?>
      </div>
    </div>
    <a class="logout" href="logout.php">Logout</a>
  </div>
</body>
</html>
