<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // ✅ Set session variables
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id']; // $user is the row from `users` table

            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['region'] = $user['region'];
            $_SESSION['profile_photo'] = $user['profile_photo'] ?? 'default-avatar.png'; // Added line

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Wrong password
            header("Location: index.html?error=invalid-password");
            exit();
        }
    } else {
        // Email not found
        header("Location: index.html?error=user-not-found");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
