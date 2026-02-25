<?php
session_start();
require_once "db/db.php";
require_once "log_action.php"; // ✅ ADD THIS

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* GENERATE KEY */
if (isset($_POST['generate'])) {

    $length = (int)$_POST['key_length']; // 128 or 256
    $bytes  = $length / 8;

    // Quantum randomness simulation
    $key = random_bytes($bytes);

    $stmt = $conn->prepare(
        "INSERT INTO quantum_keys (user_id, key_value, key_length)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param("isi", $user_id, $key, $length);
    $stmt->execute();

    /* ✅ LOG KEY GENERATION */
    logAction(
        $conn,
        $user_id,
        "KEY_GENERATED",
        "Generated {$length}-bit quantum key"
    );
}

/* FETCH USER KEYS */
$keys = $conn->prepare(
    "SELECT id, key_length, status, created_at 
     FROM quantum_keys 
     WHERE user_id = ? 
     ORDER BY created_at DESC"
);
$keys->bind_param("i", $user_id);
$keys->execute();
$result = $keys->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quantum Key Generator</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
    <h2>QuantumSecure</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="section">
    <h2>Quantum Random Key Generator</h2>
    <p>Generate cryptographically secure quantum-random encryption keys.</p>

    <img src="images/key_gen.jpg" alt="Quantum Image" class="quantum-img">

    <!-- KEY GENERATION -->
    <form method="post" class="key-box">
        <label>Select Key Strength</label>
        <select name="key_length" required>
            <option value="128">128-bit (Fast)</option>
            <option value="256">256-bit (Highly Secure)</option>
        </select>
        <button type="submit" name="generate">Generate Key</button>
    </form>
</section>


<!-- KEY LIST -->
<section class="section light">
    <h2>Your Generated Keys</h2>

    <table class="key-table">
        <tr>
            <th>Key ID</th>
            <th>Strength</th>
            <th>Status</th>
            <th>Created</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td>#QK<?= $row['id'] ?></td>
            <td><?= $row['key_length'] ?> bit</td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</section>

</body>
</html>
