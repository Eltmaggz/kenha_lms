<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    die("Unauthorized access.");
}

include 'config.php';

$training_id = $_GET['training_id'] ?? null;
if (!$training_id) {
    die("Missing training ID.");
}

$filename = "enrollments_training_$training_id.csv";

// Set headers to force download
header("Content-Description: File Transfer");
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=$filename");

// Output buffer for writing
$output = fopen("php://output", "w");

// Column headers
fputcsv($output, ['Full Name', 'Email', 'Department', 'Region', 'Enrolled At']);

// Fetch enrolled users
$sql = "
  SELECT u.fullname, u.email, u.department, u.region, e.enrolled_at
  FROM enrollments e
  JOIN users u ON e.user_id = u.id
  WHERE e.training_id = ?
  ORDER BY e.enrolled_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['fullname'],
        $row['email'],
        $row['department'],
        $row['region'],
        $row['enrolled_at']
    ]);
}

fclose($output);
exit();
