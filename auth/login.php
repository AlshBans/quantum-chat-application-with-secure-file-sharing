<?php
session_start();
include("../db/db.php");
require_once("../log_action.php"); // ✅ ADD THIS


$email = $_POST['email'];
$password = $_POST['password'];

/* Fetch user */
$sql = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 1) {

    $user = mysqli_fetch_assoc($result);

    /* Verify hashed password */
    if (password_verify($password, $user['password'])) {

        /* Create session */
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        /* ✅ LOG LOGIN ACTION */
        logAction(
            $conn,
            $user['id'],
            "LOGIN",
            "User logged in successfully"
        );

        header("Location: ../dashboard.php");
        exit();

    } else {
        echo "Invalid password";
    }

} else {
    echo "User not found";
}
?>
