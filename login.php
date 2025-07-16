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
            // ✅ Generate a new session token
            $session_token = bin2hex(random_bytes(32));

            // ✅ Save the token in database
            $updateToken = $conn->prepare("UPDATE users SET session_token = ? WHERE id = ?");
            $updateToken->bind_param("si", $session_token, $user['id']);
            $updateToken->execute();

            // ✅ Set session variables
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['region'] = $user['region'];
            $_SESSION['profile_photo'] = $user['profile_photo'] ?? 'default-avatar.png';
            $_SESSION['session_token'] = $session_token; // save the token in session

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: index.html?error=invalid-password");
            exit();
        }
    } else {
        header("Location: index.html?error=user-not-found");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
