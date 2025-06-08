<?php
session_start();
include ('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $username = $conn->real_escape_string($username);

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.html");
            exit();
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='index.html';</script>";
            exit;
        }
    } else {
        echo "<script>alert('User not found. Please register first.'); window.location.href='index.html';</script>";
        exit;
    }
} else {
    header("Location: index.html");
    exit();
}
