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

// Validate training ID and trainer assignment
if (!$training_id) {
    die("❌ Training ID not provided.");
}

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

// Handle material upload
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $uploadDir = '../uploads/materials';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('material_', true) . '.' . $ext;
        $uploadPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadPath)) {
            $insert = $conn->prepare("
                INSERT INTO classroom_materials (training_id, title, description, file_path, uploaded_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->bind_param("isssi", $training_id, $title, $description, $uploadPath, $trainer_id);
            if ($insert->execute()) {
                $message = "✅ Material uploaded successfully!";
            } else {
                $message = "❌ Failed to insert into database.";
            }
        } else {
            $message = "❌ Failed to upload file.";
        }
    } else {
        $message = "❌ No file selected or upload error.";
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
    <input type="file" name="material_file" accept=".pdf,video/*" required>

    <button type="submit">Upload Material</button>
  </form>

  <a href="../dashboard.php" class="back">⬅ Back to Dashboard</a>
</div>

</body>
</html>
