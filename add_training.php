<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    header("Location: index.html");
    exit();
}

include 'config.php';
$message = '';

// Load departments and regions from DB
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$regions = $conn->query("SELECT id, name FROM regions ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $created_by = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $time = $_POST['time_of_delivery'];
    $mode = $_POST['mode_of_delivery'];
    $assessment = $_POST['assessment_type'];
    $date = !empty($_POST['training_date']) ? date('Y-m-d H:i:s', strtotime($_POST['training_date'])) : null;

    $use_link = isset($_POST['use_link']);
    $material_link = '';
    $selectedDepts = $_POST['departments'] ?? [];
    $selectedRegions = $_POST['regions'] ?? [];

    if (empty($selectedDepts) || empty($selectedRegions)) {
        $message = "❌ Please select at least one department and one region.";
    } else {
        // Handle file or link
        if ($use_link) {
            $material_link = trim($_POST['link']);
        } else {
            if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('training_', true) . '.' . $ext;
                $uploadDir = 'uploads/trainings';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $uploadPath = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadPath)) {
                    $material_link = $uploadPath;
                } else {
                    $message = "❌ Failed to upload the file.";
                }
            }
        }

        if (empty($message)) {
            // Insert into trainings table
            $stmt = $conn->prepare("INSERT INTO trainings (title, description, material_link, time_of_delivery, mode_of_delivery, assessment_type, training_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $title, $description, $material_link, $time, $mode, $assessment, $date, $created_by);
            if ($stmt->execute()) {
                $training_id = $stmt->insert_id;

                // Assign departments and regions
                $assign = $conn->prepare("INSERT INTO training_assignments (training_id, department_id, region_id) VALUES (?, ?, ?)");
                $assignments = 0;
                foreach ($selectedDepts as $dept_id) {
                    foreach ($selectedRegions as $region_id) {
                        $assign->bind_param("iii", $training_id, $dept_id, $region_id);
                        $assign->execute();
                        $assignments++;
                    }
                }
                $assign->close();
                $message = "✅ Training added and assigned to $assignments department-region combination(s).";
            } else {
                $message = "❌ Failed to insert training.";
            }
            $stmt->close();
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Training</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f7fc; padding: 40px; }
    .container {
      background: #fff; padding: 30px; border-radius: 10px;
      max-width: 850px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    h2 { color: #00793a; margin-bottom: 20px; }
    label { display: block; margin-top: 12px; font-weight: bold; }
    input, textarea, select {
      width: 100%; padding: 10px; margin-top: 5px;
      border: 1px solid #ccc; border-radius: 6px;
    }
    .checkbox-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 8px;
      margin-top: 10px;
    }
    .message { margin-top: 20px; font-weight: bold; color: green; }
    button {
      background: #00793a; color: white; padding: 10px 20px;
      border: none; border-radius: 6px; margin-top: 20px; cursor: pointer;
    }
    .back-btn {
      background: #003366; color: white; text-decoration: none;
      padding: 10px 20px; border-radius: 6px; display: inline-block; margin-bottom: 20px;
    }
  </style>
</head>
<body>
<div class="container">
  <h2>Add New Training</h2>
  <a href="dashboard.php" class="back-btn">⬅ Back to Dashboard</a>

  <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

  <form method="POST" enctype="multipart/form-data">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <label><input type="checkbox" id="use_link" name="use_link" onchange="toggleMaterial()"> Use Link Instead of File</label>

    <div id="fileInput">
      <label>Upload PDF/Video</label>
      <input type="file" name="material_file" accept=".pdf,.mp4,.mov,.avi">
    </div>

    <div id="linkInput" style="display:none;">
      <label>Provide Link</label>
      <input type="url" name="link" placeholder="https://...">
    </div>

    <label>Time of Delivery</label>
    <input type="text" name="time_of_delivery" placeholder="e.g. 10:00 AM - 12:00 PM" required>

    <label>Mode of Delivery</label>
    <select name="mode_of_delivery" required>
      <option value="">--Select--</option>
      <option value="In-person">In-person</option>
      <option value="Virtual">Virtual</option>
      <option value="Hybrid">Hybrid</option>
    </select>

    <label>Assessment Type</label>
    <select name="assessment_type" required>
      <option value="">--Select--</option>
      <option value="Quiz">Quiz</option>
      <option value="Written Test">Written Test</option>
      <option value="Practical Evaluation">Practical Evaluation</option>
      <option value="None">None</option>
    </select>

    <label>Training Date</label>
    <input type="datetime-local" name="training_date">

    <label>Assign to Departments:</label>
    <div class="checkbox-grid">
      <?php foreach ($departments as $d): ?>
        <label><input type="checkbox" name="departments[]" value="<?= $d['id'] ?>"> <?= ucfirst($d['name']) ?></label>
      <?php endforeach; ?>
    </div>

    <label>Assign to Regions:</label>
    <div class="checkbox-grid">
      <?php foreach ($regions as $r): ?>
        <label><input type="checkbox" name="regions[]" value="<?= $r['id'] ?>"> <?= ucfirst($r['name']) ?></label>
      <?php endforeach; ?>
    </div>

    <button type="submit">➕ Add Training</button>
  </form>
</div>

<script>
function toggleMaterial() {
  const useLink = document.getElementById("use_link").checked;
  document.getElementById("fileInput").style.display = useLink ? "none" : "block";
  document.getElementById("linkInput").style.display = useLink ? "block" : "none";
}
</script>
</body>
</html>
