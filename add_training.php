<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    header("Location: index.html");
    exit();
}

include 'config.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $created_by = $_SESSION['user_id']; // ✅ use user_id, not email
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $time = $_POST['time_of_delivery'];
    $mode = $_POST['mode_of_delivery'];
    $assessment = $_POST['assessment_type'];
    $date = !empty($_POST['training_date']) ? date('Y-m-d H:i:s', strtotime($_POST['training_date'])) : null;

    $use_link = isset($_POST['use_link']);
    $material_link = '';
    $departments = $_POST['departments'] ?? [];
    $regions = $_POST['regions'] ?? [];

    if (empty($departments) || empty($regions)) {
        $message = "❌ Please select at least one department and one region.";
    } else {
        if ($use_link) {
            $material_link = trim($_POST['link']);
        } else {
            if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('training_', true) . '.' . $ext;
                $uploadDir = 'uploads/trainings';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true); // ✅ create folder if not exist
                }
                $uploadPath = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadPath)) {
                    $material_link = $uploadPath;
                } else {
                    $message = "❌ Failed to upload the file.";
                }
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("SELECT id FROM trainings WHERE title = ? AND department = ? AND region = ?");
            $insert = $conn->prepare("INSERT INTO trainings (title, description, material_link, department, region, time_of_delivery, mode_of_delivery, assessment_type, training_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $addedCount = 0;
            foreach ($departments as $dept) {
                foreach ($regions as $reg) {
                    $stmt->bind_param("sss", $title, $dept, $reg);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows === 0) {
                        $insert->bind_param("ssssssssss", $title, $description, $material_link, $dept, $reg, $time, $mode, $assessment, $date, $created_by);
                        $insert->execute();
                        $addedCount++;
                    }
                }
            }
            $message = "✅ Training successfully added to $addedCount department-region combination(s)!";
            $stmt->close();
            $insert->close();
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
    body { font-family: Arial, sans-serif; background: #f1f4f9; padding: 40px; }
    .container {
      background: #fff; padding: 30px; border-radius: 10px;
      max-width: 800px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
      gap: 6px;
    }
    .checkbox-grid label { display: flex; align-items: center; gap: 8px; }
    button {
      background: #00793a; color: white; padding: 10px 20px; border: none;
      margin-top: 20px; border-radius: 6px; cursor: pointer;
    }
    .message { margin-top: 20px; font-weight: bold; color: green; }
  </style>
</head>
<body>
<div class="container">
  <h2>Add New Training</h2>
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

  <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  <form method="POST" enctype="multipart/form-data">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <div style="margin-top:12px;">
      <input type="checkbox" id="use_link" name="use_link" onchange="toggleMaterialInputs()">
      <label for="use_link">Use Link instead of File</label>
    </div>

    <div id="fileInput">
      <label>Upload PDF/Video File</label>
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
      <option value="">--Select Mode--</option>
      <option value="In-person">In-person</option>
      <option value="Virtual">Virtual</option>
      <option value="Hybrid">Hybrid</option>
    </select>

    <label>Assessment Type</label>
    <select name="assessment_type" required>
      <option value="">--Select Assessment--</option>
      <option value="Quiz">Quiz</option>
      <option value="Written Test">Written Test</option>
      <option value="Practical Evaluation">Practical Evaluation</option>
      <option value="None">None</option>
    </select>

    <label>Training Date (optional)</label>
    <input type="datetime-local" name="training_date">

    <label>Assign to Department(s)</label>
    <div class="checkbox-grid">
      <label><input type="checkbox" onclick="toggleAll(this, 'departments[]')"> <strong>Select All Departments</strong></label>
      <?php
      $depts = [
        "planning and design", "construction and maintenance", "procurement", "finance and accounts",
        "human resource and administration", "legal services", "corporate communications", "ict",
        "environment and social safeguards", "internal audit", "quality assurance",
        "research and development", "road asset management"
      ];
      foreach ($depts as $dept) {
          echo "<label><input type='checkbox' name='departments[]' value='$dept'> " . ucfirst($dept) . "</label>";
      }
      ?>
    </div>

    <label>Assign to Region(s)</label>
    <div class="checkbox-grid">
      <label><input type="checkbox" onclick="toggleAll(this, 'regions[]')"> <strong>Select All Regions</strong></label>
      <?php
      $regions = [
        "nairobi (hq)", "upper eastern", "lower eastern", "coast",
        "south rift", "north rift", "central", "nyanza",
        "north eastern", "western"
      ];
      foreach ($regions as $r) {
          echo "<label><input type='checkbox' name='regions[]' value='$r'> " . ucfirst($r) . "</label>";
      }
      ?>
    </div>

    <button type="submit">➕ Add Training</button>
  </form>
</div>

<script>
function toggleMaterialInputs() {
  const useLink = document.getElementById("use_link").checked;
  document.getElementById("fileInput").style.display = useLink ? "none" : "block";
  document.getElementById("linkInput").style.display = useLink ? "block" : "none";
}

function toggleAll(master, groupName) {
  const checkboxes = document.querySelectorAll(`input[name='${groupName}']`);
  checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>
</body>
</html>
