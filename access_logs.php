<?php
session_start();
require_once "db/db.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= FETCH LOGS ================= */
$logs = $conn->query(
    "SELECT action_type, description, ip_address, created_at
     FROM access_logs
     WHERE user_id = $user_id
     ORDER BY created_at DESC"
);

/* ================= ICON MAPPING ================= */
function logIcon($type) {
    switch ($type) {
        case "LOGIN": return "🔑";
        case "KEY_GENERATED": return "🧬";
        case "MESSAGE_SENT": return "📤";
        case "MESSAGE_READ": return "📥";
        case "FILE_UPLOADED": return "⬆️";
        case "FILE_DOWNLOADED": return "⬇️";
        default: return "🔐";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Logs</title>
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
    <h2>📜 Access Logs</h2>
    <p>Complete audit trail of your security-sensitive activities</p>
</section>

<div class="logs-container">

<?php if ($logs->num_rows === 0) { ?>
    <p class="empty-msg">No activity recorded yet.</p>
<?php } ?>


<?php while ($log = $logs->fetch_assoc()) { ?>
    <div class="log-card">
        <div class="log-icon">
            <?= logIcon($log['action_type']) ?>
        </div>

        <div class="log-content">
            <strong><?= htmlspecialchars($log['action_type']) ?></strong>
            <p><?= htmlspecialchars($log['description']) ?></p>
            <small>
                IP: <?= htmlspecialchars($log['ip_address']) ?> |
                <?= $log['created_at'] ?>
            </small>
        </div>
    </div>
<?php } ?>

</div>

</body>
</html>
