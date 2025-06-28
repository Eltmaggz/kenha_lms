<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'hr') {
    header("Location: index.html");
    exit();
}

include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link = $_POST['link'];
    $department = $_POST['department'];
    $region = $_POST['region'];

    $sql = "INSERT INTO trainings (title, description, link, department, region) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $title, $description, $link, $department, $region);

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
    body {
      font-family: Arial, sans-serif;
      background: #f1f4f9;
      padding: 40px;
    }
    .container {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      max-width: 600px;
      margin: auto;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    h2 {
      color: #00793a;
      margin-bottom: 20px;
    }
    label {
      display: block;
      margin-top: 10px;
    }
    input, textarea, select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      background: #00793a;
      color: white;
      padding: 10px 20px;
      border: none;
      margin-top: 20px;
      border-radius: 6px;
    }
    .message {
      margin-top: 20px;
      color: green;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Add New Training</h2>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
    <form method="POST" action="">
      <label>Title</label>
      <input type="text" name="title" required>

      <label>Description</label>
      <textarea name="description" required></textarea>

      <label>PDF/Video Link</label>
      <input type="url" name="link" required>

      <label>Department</label>
      <select name="department" required>
        <option value="">-- Select Department --</option>
        <option value="ict">ICT</option>
        <option value="finance">Finance</option>
        <option value="hr">HR</option>
        <option value="planning">Planning</option>
        <option value="maintenance">Maintenance</option>
      </select>

      <label>Region</label>
      <select name="region" required>
        <option value="">-- Select Region --</option>
        <option value="nairobi">Nairobi</option>
        <option value="central">Central</option>
        <option value="coast">Coast</option>
        <option value="western">Western</option>
        <option value="rift-valley">Rift Valley</option>
        <option value="northeastern">North Eastern</option>
      </select>

      <button type="submit">Add Training</button>
    </form>
  </div>
</body>
</html>
