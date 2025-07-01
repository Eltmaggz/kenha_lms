<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $department = $_POST['department'];
    $region = $_POST['region'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password match check
    if ($password !== $confirm_password) {
        header("Location: register.html?error=password-mismatch");
        exit();
    }

    // Password policy enforcement
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        header("Location: register.html?error=weak-password");
        exit();
    }

    // Email domain check
    if (!preg_match("/@kenha\.co\.ke$/", $email)) {
        header("Location: register.html?error=invalid-domain");
        exit();
    }

    // Email uniqueness check
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        header("Location: register.html?error=email-exists");
        exit();
    }
    $check->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Auto-assign role as 'staff'
    $defaultRole = "staff";

    // Insert into database
    $sql = "INSERT INTO users (fullname, email, password, dob, department, region, role)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $fullname, $email, $hashed_password, $dob, $department, $region, $defaultRole);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: index.html?registered=success");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: register.html?error=insert-failed");
        exit();
    }
}
?>
