<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    header("Location: index.html");
    exit();
}

include 'config.php';

$hr_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

$selectedDepartments = $_GET['departments'] ?? [];
$selectedRegions = $_GET['regions'] ?? [];

$departments = [
  "planning and design", "construction and maintenance", "procurement", "finance and accounts",
  "human resource and administration", "legal services", "corporate communications", "ict",
  "environment and social safeguards", "internal audit", "quality assurance",
  "research and development", "road asset management"
];

$regions = [
  "nairobi (hq)", "upper eastern", "lower eastern", "coast", "south rift",
  "north rift", "central", "nyanza", "north eastern", "western"
];

// Build filters
$deptFilterClause = '';
$regionFilterClause = '';
$params = [$hr_id];
$types = 's';

if (!empty($selectedDepartments)) {
    $placeholders = implode(',', array_fill(0, count($selectedDepartments), '?'));
    $deptFilterClause = "AND d.name IN ($placeholders)";
    $params = array_merge($params, $selectedDepartments);
    $types .= str_repeat('s', count($selectedDepartments));
}
if (!empty($selectedRegions)) {
    $placeholders = implode(',', array_fill(0, count($selectedRegions), '?'));
    $regionFilterClause = "AND r.name IN ($placeholders)";
    $params = array_merge($params, $selectedRegions);
    $types .= str_repeat('s', count($selectedRegions));
}

// Add search filters
$params[] = $searchParam;
$params[] = $searchParam;
$types .= 'ss';

// Final query
$sql = "
  SELECT t.*, 
         GROUP_CONCAT(DISTINCT d.name) AS departments,
         GROUP_CONCAT(DISTINCT r.name) AS regions,
         (SELECT COUNT(*) FROM enrollments e WHERE e.training_id = t.id) AS enrolled_count
  FROM trainings t
  LEFT JOIN training_assignments ta ON t.id = ta.training_id
  LEFT JOIN departments d ON ta.department_id = d.id
  LEFT JOIN regions r ON ta.region_id = r.id
  WHERE t.created_by = ?
    $deptFilterClause
    $regionFilterClause
    AND (t.title LIKE ? OR t.training_date LIKE ?)
  GROUP BY t.id
  ORDER BY t.training_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Trainings</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f7fc; padding: 30px; }
    h2 { color: #003366; margin-bottom: 20px; }
    .card {
      background: #fff; padding: 20px; margin-bottom: 20px;
      border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .card h3 { color: #00793a; margin-top: 0; }
    .badge { background: #003366; color: white; font-size: 13px;
      padding: 4px 10px; border-radius: 5px; display: inline-block; margin-top: 10px; }
    .view-users, .export-btn {
      margin-top: 10px; display: inline-block; text-decoration: none;
      color: white; padding: 8px 12px; border-radius: 5px;
    }
    .view-users { background: #00793a; }
    .export-btn { background: #0055aa; margin-left: 10px; }

    .back-btn {
      display: inline-block; margin-bottom: 20px;
      background: #003366; color: white; padding: 10px 20px;
      text-decoration: none; border-radius: 6px; font-weight: bold;
    }

    .filter-form {
      background: #fff; padding: 15px; margin-bottom: 30px;
      border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .filter-group { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; }
    .filter-group label { font-size: 14px; }
    .submit-btn {
      background: #003366; color: white; padding: 8px 20px;
      border: none; border-radius: 6px; margin-top: 15px;
    }
  </style>
</head>
<body>

<a class="back-btn" href="dashboard.php">⬅ Back to Dashboard</a>

<h2>📋 Trainings You Created</h2>

<form method="GET" class="filter-form">
  <label>🔎 Search by Title or Date</label><br>
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="e.g. Safety or 2025-08">

  <div style="margin-top: 15px;">
    <strong>📂 Departments:</strong>
    <div class="filter-group">
      <?php foreach ($departments as $dept): ?>
        <label>
          <input type="checkbox" name="departments[]" value="<?= $dept ?>"
            <?= in_array($dept, $selectedDepartments) ? 'checked' : '' ?>> <?= ucwords($dept) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="margin-top: 15px;">
    <strong>📍 Regions:</strong>
    <div class="filter-group">
      <?php foreach ($regions as $reg): ?>
        <label>
          <input type="checkbox" name="regions[]" value="<?= $reg ?>"
            <?= in_array($reg, $selectedRegions) ? 'checked' : '' ?>> <?= ucwords($reg) ?>
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
      <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
      <p><strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($row['training_date'])) ?></p>
      <p><strong>Time:</strong> <?= htmlspecialchars($row['time_of_delivery']) ?></p>
      <p><strong>Mode:</strong> <?= htmlspecialchars($row['mode_of_delivery']) ?> |
         <strong>Assessment:</strong> <?= htmlspecialchars($row['assessment_type']) ?></p>
      <p><strong>Departments:</strong> <?= htmlspecialchars($row['departments']) ?></p>
      <p><strong>Regions:</strong> <?= htmlspecialchars($row['regions']) ?></p>
      <p><strong>Enrolled:</strong> <?= $row['enrolled_count'] ?> people</p>
      <a class="view-users" href="view_enrollments.php?training_id=<?= $row['id'] ?>">👥 View Enrolled Users</a>
      <a href="export_enrollments.php?training_id=<?= $row['id'] ?>" class="export-btn">⬇ Export CSV</a>
    </div>
  <?php } ?>
<?php else: ?>
  <p>No trainings found.</p>
<?php endif; ?>

</body>
</html>
