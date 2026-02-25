<?php
include("../db/db.php");


$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if ($password !== $confirm) {
    echo "Passwords do not match";
    exit();
}

/* Password Hashing */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* Insert User */
$sql = "INSERT INTO users (name, email, password)
        VALUES ('$name', '$email', '$hashedPassword')";

if (mysqli_query($conn, $sql)) {
    header("Location: ../login.html");
} else {
    echo "Email already exists";
}
?>
