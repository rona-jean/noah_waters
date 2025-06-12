<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$username = "root";
$password = ""; // Use your DB password
$dbname = "noah_waters"; // Replace with your DB name

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data
$fullname = $_POST['fullname'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Check if passwords match
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
$sql = "INSERT INTO users (fullname, email, phone, address, password, is_new_user) 
        VALUES (?, ?, ?, ?, ?, 1)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $fullname, $email, $phone, $address, $hashed_password);

if ($stmt->execute()) {
    echo "<script>
            alert('Registration successful!');
            window.location.href = 'index.html';
          </script>";
} else {
    echo "Error: " . $stmt->error;
}


$stmt->close();
$conn->close();
?>
