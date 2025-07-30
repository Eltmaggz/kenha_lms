<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'TRAINER') {
    header("Location: ../index.html");
    exit();
}

include '../config.php';

$trainer_id = $_SESSION['user_id'];
$training_id = $_GET['training_id'] ?? null;
$message = '';

if (!$training_id) {
    die("❌ Training ID not provided.");
}

// Verify that the trainer is assigned to this training
$check = $conn->prepare("
    SELECT t.id, t.title FROM trainings t
    JOIN trainer_assignments ta ON t.id = ta.training_id
    WHERE t.id = ? AND ta.trainer_id = ?
");
$check->bind_param("ii", $training_id, $trainer_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    die("❌ Unauthorized access or training not found.");
}
$training = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $material_link = trim($_POST['material_link']);
    $material_file_path = '';

    // Handle file upload if provided
    if (!empty($_FILES['material_file']['name']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/materials';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('material_', true) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
            $material_file_path = 'uploads/materials/' . $filename;
        } else {
            $message = "❌ Failed to upload file.";
        }
    }

    // Insert into database
    if ($material_file_path || $material_link) {
        $stmt = $conn->prepare("
            INSERT INTO classroom_materials (training_id, module_title, module_description, material_file, material_link, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssi",
            $training_id,
            $title,
            $description,
            $material_file_path,
            $material_link,
            $trainer_id
        );

        if ($stmt->execute()) {
            $message = "✅ Material uploaded successfully!";
        } else {
            $message = "❌ Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "❌ You must upload a file or provide a material link.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Material - <?= htmlspecialchars($training['title']) ?></title>
  <style>
    body { font-family: Arial; background: #f2f5fa; padding: 40px; }
    .container { background: white; padding: 30px; max-width: 700px; margin: auto; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    h2 { color: #003366; }
    label { display: block; margin-top: 12px; font-weight: bold; }
    input, textarea {
      width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;
    }
    input[type="file"] { padding: 4px; }
    button {
      background: #00793a; color: white; border: none; padding: 10px 20px;
      margin-top: 20px; border-radius: 6px; cursor: pointer;
    }
    .msg { margin-top: 15px; font-weight: bold; color: green; }
    a.back { display: inline-block; margin-top: 20px; text-decoration: none; color: #0055aa; }
  </style>
</head>
<body>

<div class="container">
  <h2>➕ Add Material for: <?= htmlspecialchars($training['title']) ?></h2>
  <?php if (!empty($message)) echo "<p class='msg'>$message</p>"; ?>

 <form method="POST" enctype="multipart/form-data">
  <label>Material Title</label>
  <input type="text" name="title" required>

  <label>Description</label>
  <textarea name="description" required></textarea>

  <label>Upload File (PDF or Video)</label>
  <input type="file" name="material_file" accept=".pdf,video/*">

  <label>OR Provide External Link</label>
  <input type="url" name="material_link" placeholder="https://example.com/material" />

  <button type="submit">Upload Material</button>
</form>


  <a href="../dashboard.php" class="back">⬅ Back to Dashboard</a>
</div>

</body>
</html>
