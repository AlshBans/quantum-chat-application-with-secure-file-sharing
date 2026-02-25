<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
    <h2>QuantumSecure</h2>
    <nav>
        <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
        <a href="logout.php">Logout</a>
        <a href="index.php">Home</a>
    </nav>
</header>

<section class="dashboard">
    <h2>Your Secure Control Center</h2>

    <div class="card-container">

        <div class="card">
            <img src="images/key.jpg" alt="Key">
            <h3>Generate Quantum Key</h3>
            <p>Create your own encryption keys.</p>
            <a href="key_generator.php">Open</a>
        </div>

        <div class="card">
            <img src="images/message.jpg" alt="Message">
            <h3>Secure Messaging</h3>
            <p>Send encrypted messages.</p>
            <a href="secure_message.php">Open</a>
        </div>

        <div class="card">
            <img src="images/file_sharing.jpg" alt="File">
            <h3>Secure File Sharing</h3>
            <p>Upload and share encrypted files.</p>
            <a href="secure_files.php">Open</a>
        </div>

        <div class="card">
            <img src="images/log.jpeg" alt="Logs">
            <h3>Access Logs</h3>
            <p>Audit all security actions.</p>
            <a href="access_logs.php">View</a>
        </div>

    </div>
</section>

</body>
</html>
