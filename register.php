<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $department = $_POST['department'];
    $region = $_POST['region'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Passwords must match
    if ($password !== $confirm_password) {
        header("Location: index.html?error=password-mismatch");
        exit();
    }

    // Validate email domain
    if (!preg_match("/@kenha\.co\.ke$/", $email)) {
        header("Location: index.html?error=invalid-domain");
        exit();
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        header("Location: index.html?error=email-exists");
        exit();
    }
    $check->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the user without role and auto doe
    $sql = "INSERT INTO users (fullname, email, password, dob, department, region)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $fullname, $email, $hashed_password, $dob, $department, $region);

    if ($stmt->execute()) {
        header("Location: index.html?registered=success");
        exit();
    } else {
        header("Location: index.html?error=insert-failed");
        exit();
    }

$stmt->close();
$conn->close();
}
?>
