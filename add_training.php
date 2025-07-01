<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    header("Location: index.html");
    exit();
}

include 'config.php';

$message = '';
$uploadPath = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $time_of_delivery = $_POST['time_of_delivery'];
    $mode_of_delivery = $_POST['mode_of_delivery'];
    $assessment_type = $_POST['assessment_type'];
    $use_link = isset($_POST['use_link']);
    $material_link = '';

    // Handle material input
    if ($use_link) {
        $material_link = trim($_POST['link']);
    } else {
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('training_', true) . '.' . $ext;
            $uploadPath = 'uploads/' . $filename;
            move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadPath);
            $material_link = $uploadPath;
        }
    }

    // Handle department and region assignment
    $department = isset($_POST['all_departments']) ? 'all' : $_POST['department'];
    $region = isset($_POST['all_regions']) ? 'all' : $_POST['region'];

    // Insert training
    $sql = "INSERT INTO trainings (title, description, material_link, department, region, time_of_delivery, mode_of_delivery, assessment_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $title, $description, $material_link, $department, $region, $time_of_delivery, $mode_of_delivery, $assessment_type);

    if ($stmt->execute()) {
        $message = "✅ Training successfully added!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
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
      max-width: 700px; margin: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    h2 { color: #00793a; margin-bottom: 20px; }
    label { display: block; margin-top: 12px; font-weight: bold; }
    input, textarea, select {
      width: 100%; padding: 10px; margin-top: 5px;
      border: 1px solid #ccc; border-radius: 6px;
    }
    .checkbox { display: flex; align-items: center; margin-top: 10px; }
    .checkbox input { margin-right: 8px; }
    button {
      background: #00793a; color: white; padding: 10px 20px; border: none;
      margin-top: 20px; border-radius: 6px; cursor: pointer;
    }
    .message { margin-top: 20px; color: green; }
  </style>
</head>
<body>
<div class="container">
  <h2>Add New Training</h2>
  <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  <form method="POST" enctype="multipart/form-data">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <div class="checkbox">
      <input type="checkbox" id="use_link" name="use_link" onchange="toggleMaterialInputs()">
      <label for="use_link">Use Link instead of File</label>
    </div>

    <div id="fileInput">
      <label>Upload PDF/Video File</label>
      <input type="file" name="material_file" accept=".pdf,video/*">
    </div>

    <div id="linkInput" style="display:none;">
      <label>Provide Link</label>
      <input type="url" name="link" placeholder="https://...">
    </div>

    <label for="time_of_delivery">Time of Delivery</label>
    <input type="text" name="time_of_delivery" placeholder="e.g. 10:00 AM - 12:00 PM" required>

    <label for="mode_of_delivery">Mode of Delivery</label>
    <select name="mode_of_delivery" required>
      <option value="">--Select Mode--</option>
      <option value="In-person">In-person</option>
      <option value="Virtual">Virtual</option>
      <option value="Hybrid">Hybrid</option>
    </select>

    <label for="assessment_type">Assessment Type</label>
    <select name="assessment_type" required>
      <option value="">--Select Assessment Type--</option>
      <option value="Quiz">Quiz</option>
      <option value="Written Test">Written Test</option>
      <option value="Practical Evaluation">Practical Evaluation</option>
      <option value="None">None</option>
    </select>

    <div class="checkbox">
      <input type="checkbox" id="all_departments" name="all_departments" onchange="toggleSelect('department', this)">
      <label for="all_departments">Assign to all departments</label>
    </div>
    <label for="department">Department</label>
    <select name="department" id="department" required>
      <option value="">--Select Department--</option>
      <option value="planning and design">Planning and Design</option>
      <option value="construction and maintenance">Construction and Maintenance</option>
      <option value="procurement">Procurement</option>
      <option value="finance and accounts">Finance and Accounts</option>
      <option value="human resource and administration">Human Resource and Administration</option>
      <option value="legal services">Legal Services</option>
      <option value="corporate communications">Corporate Communications</option>
      <option value="ict">ICT</option>
      <option value="environment and social safeguards">Environment and Social Safeguards</option>
      <option value="internal audit">Internal Audit</option>
      <option value="quality assurance">Quality Assurance</option>
      <option value="research and development">Research and Development</option>
      <option value="road asset management">Road Asset Management</option>
    </select>

    <div class="checkbox">
      <input type="checkbox" id="all_regions" name="all_regions" onchange="toggleSelect('region', this)">
      <label for="all_regions">Assign to all regions</label>
    </div>
    <label for="region">Region</label>
    <select name="region" id="region" required>
      <option value="">--Select Region--</option>
      <option value="nairobi (hq)">Nairobi (HQ)</option>
      <option value="upper eastern">Upper Eastern</option>
      <option value="lower eastern">Lower Eastern</option>
      <option value="coast">Coast</option>
      <option value="south rift">South Rift</option>
      <option value="north rift">North Rift</option>
      <option value="central">Central</option>
      <option value="nyanza">Nyanza</option>
      <option value="north eastern">North Eastern</option>
      <option value="western">Western</option>
    </select>

    <button type="submit">Add Training</button>
  </form>
</div>

<script>
  function toggleMaterialInputs() {
    const useLink = document.getElementById("use_link").checked;
    document.getElementById("fileInput").style.display = useLink ? "none" : "block";
    document.getElementById("linkInput").style.display = useLink ? "block" : "none";
  }

  function toggleSelect(selectId, checkbox) {
    document.getElementById(selectId).disabled = checkbox.checked;
  }
</script>
</body>
</html>
