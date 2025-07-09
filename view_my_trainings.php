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

$departments = [/* same departments */];
$regions = [/* same regions */];

// Filters
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

$params[] = $searchParam;
$params[] = $searchParam;
$types .= 'ss';

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
  <title>My Trainings (Table Format)</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .filter-form {
      background: #fff; padding: 15px; margin-bottom: 30px;
      border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .filter-group { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; }
    .filter-group label { font-size: 14px; }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      margin-top: 20px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: #003366;
      color: white;
    }

    .action-links a {
      margin-right: 10px;
      padding: 6px 12px;
      border-radius: 5px;
      color: white;
      font-size: 13px;
      text-decoration: none;
    }

    .btn-success { background: #00793a; }
    .btn-blue { background: #0055aa; }
  </style>
</head>
<body>
<?php include 'profile_sidebar.php'; ?>
<div class="main-content">
  <h2>📋 Trainings You Created (Table Format)</h2>

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

    <button type="submit" class="btn-primary">Apply Filters</button>
  </form>

  <?php if ($result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Date & Time</th>
          <th>Mode / Assessment</th>
          <th>Departments</th>
          <th>Regions</th>
          <th>Enrolled</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($row['training_date'])) ?><br><small><?= htmlspecialchars($row['time_of_delivery']) ?></small></td>
            <td><?= htmlspecialchars($row['mode_of_delivery']) ?> / <?= htmlspecialchars($row['assessment_type']) ?></td>
            <td><?= htmlspecialchars($row['departments']) ?></td>
            <td><?= htmlspecialchars($row['regions']) ?></td>
            <td><?= $row['enrolled_count'] ?></td>
            <td class="action-links">
              <a class="btn-success" href="view_enrollments.php?training_id=<?= $row['id'] ?>">👥 View</a>
              <a class="btn-blue" href="export_enrollments.php?training_id=<?= $row['id'] ?>">⬇ CSV</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="no-data">No trainings found.</p>
  <?php endif; ?>
</div>
</body>
</html>
