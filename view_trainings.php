<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.html");
  exit();
}

include 'config.php';

$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

$selectedDepartments = $_GET['departments'] ?? [];
$selectedRegions = $_GET['regions'] ?? [];

$departmentFilter = !empty($selectedDepartments) ? $selectedDepartments : [$_SESSION['department']];
$regionFilter = !empty($selectedRegions) ? $selectedRegions : [$_SESSION['region']];

$inClauseDept = implode(',', array_fill(0, count($departmentFilter), '?'));
$inClauseRegion = implode(',', array_fill(0, count($regionFilter), '?'));

$params = array_merge($departmentFilter, $regionFilter, [$searchParam, $searchParam]);

$sql = "
  SELECT * FROM trainings 
  WHERE department IN ($inClauseDept)
  AND region IN ($inClauseRegion)
  AND (title LIKE ? OR training_date LIKE ?)
  ORDER BY training_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// For re-rendering the checkboxes
$allDepartments = [
  "planning and design", "construction and maintenance", "procurement", "finance and accounts",
  "human resource and administration", "legal services", "corporate communications", "ict",
  "environment and social safeguards", "internal audit", "quality assurance",
  "research and development", "road asset management"
];

$allRegions = [
  "nairobi (hq)", "upper eastern", "lower eastern", "coast", "south rift",
  "north rift", "central", "nyanza", "north eastern", "western"
];
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

    h2 { color: #003366; margin-bottom: 20px; }

    .filter-form {
      background: #fff;
      padding: 15px;
      margin-bottom: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .filter-group {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 10px;
    }

    .filter-group label {
      font-size: 14px;
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

    .submit-btn {
      background: #003366;
      color: white;
      padding: 8px 20px;
      border: none;
      border-radius: 6px;
      margin-top: 15px;
    }
  </style>
</head>
<body>

<h2>📚 Available Trainings</h2>
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


<form method="GET" class="filter-form">
  <label>🔎 Search:</label><br>
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title or date...">

  <div style="margin-top: 15px;">
    <strong>📂 Departments:</strong>
    <div class="filter-group">
      <?php foreach ($allDepartments as $dept): ?>
        <label>
          <input type="checkbox" name="departments[]" value="<?= $dept ?>"
            <?= in_array($dept, $departmentFilter) ? 'checked' : '' ?>>
          <?= ucwords($dept) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="margin-top: 15px;">
    <strong>📍 Regions:</strong>
    <div class="filter-group">
      <?php foreach ($allRegions as $reg): ?>
        <label>
          <input type="checkbox" name="regions[]" value="<?= $reg ?>"
            <?= in_array($reg, $regionFilter) ? 'checked' : '' ?>>
          <?= ucwords($reg) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <button type="submit" class="submit-btn">Apply Filters</button>
</form>

<?php if ($result->num_rows > 0): ?>
 <?php while ($row = $result->fetch_assoc()) { ?>
  <div class="card">
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    
    <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>

    <!-- ✅ Date with full timestamp -->
    <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($row['training_date'])) ?></p>

    <!-- ✅ Newly added fields -->
    <p><strong>Time:</strong> <?= htmlspecialchars($row['time_of_delivery']) ?></p>
    <p><strong>Mode:</strong> <?= htmlspecialchars($row['mode_of_delivery']) ?></p>
    <p><strong>Assessment:</strong> <?= htmlspecialchars($row['assessment_type']) ?></p>

    <!-- ✅ Training material -->
    <p><strong>Material:</strong> 
      <a href="<?= htmlspecialchars($row['material_link']) ?>" target="_blank">View</a>
    </p>

    <!-- ✅ Department badge -->
    <span class="badge"><?= htmlspecialchars($row['department']) ?> Department</span><br>

    <!-- ✅ Enroll button -->
    <a class="enroll-btn" href="enroll.php?training_id=<?= $row['id'] ?>">Enroll</a>
  </div>
<?php } ?>

<?php else: ?>
  <div class="no-data">No trainings found for your selection.</div>
<?php endif; ?>

</body>
</html>
