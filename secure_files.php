<?php
session_start();
require_once "db/db.php";
require_once "log_action.php"; // ✅ ADD LOGGING

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* FETCH USERS */
$users = $conn->query(
    "SELECT id, name FROM users WHERE id != $user_id"
);

/* FETCH UNUSED KEYS */
$keys = $conn->query(
    "SELECT id, key_length 
     FROM quantum_keys 
     WHERE user_id = $user_id AND status = 'unused'"
);

/* ================= HANDLE FILE UPLOAD ================= */
if (isset($_POST['upload'])) {

    $receiver = $_POST['receiver'];
    $key_id   = $_POST['key_id'];
    $expiry   = $_POST['expiry'];

    $file     = $_FILES['file'];

    $original = $file['name'];
    $tmp      = $file['tmp_name'];
    $size     = $file['size'];
    $type     = pathinfo($original, PATHINFO_EXTENSION);

    $stored   = uniqid("secure_") . "." . $type;
    $path     = "uploads/secure_files/" . $stored;

    if (move_uploaded_file($tmp, $path)) {

        $stmt = $conn->prepare(
            "INSERT INTO secure_files
            (sender_id, receiver_id, original_filename, stored_filename,
             file_type, file_size, key_id, expiry_time)
             VALUES (?,?,?,?,?,?,?,?)"
        );

        $stmt->bind_param(
            "iisssiss",
            $user_id,
            $receiver,
            $original,
            $stored,
            $type,
            $size,
            $key_id,
            $expiry
        );

        $stmt->execute();

        /* ✅ LOG FILE UPLOAD */
        logAction(
            $conn,
            $user_id,
            "FILE_UPLOADED",
            "Uploaded file: $original"
        );

        header("Location: secure_files.php?sent=1");
        exit();
    }
}

/* FILES I SHARED */
$myFiles = $conn->query(
    "SELECT f.*, u.name AS receiver_name
     FROM secure_files f
     JOIN users u ON f.receiver_id = u.id
     WHERE f.sender_id = $user_id
     ORDER BY f.uploaded_at DESC"
);

/* FILES SHARED WITH ME */
$receivedFiles = $conn->query(
    "SELECT f.*, u.name AS sender_name
     FROM secure_files f
     JOIN users u ON f.sender_id = u.id
     WHERE f.receiver_id = $user_id
     ORDER BY f.uploaded_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure File Sharing</title>
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

<!-- 🔐 MAIN WRAPPER -->
<div class="secure-message-page">

<section class="section">
    <h2>Secure File Sharing</h2>
    <p>Share files securely using quantum-generated keys</p>
</section>

<?php if (isset($_GET['sent'])) { ?>
    <div class="success-msg">✅ File sent successfully</div>
<?php } ?>

<!-- ================= UPLOAD CARD ================= -->
<div class="messaging-container">
    <div class="message-card">

        <div class="message-image">
            <img src="images/register-quantum.avif" alt="Secure File Sharing">
        </div>

        <div class="message-form">
            <h3>Upload Secure File</h3>

            <form method="post" enctype="multipart/form-data">
                <input type="file" name="file" required>

                <select name="receiver" required>
                    <option value="">Select Receiver</option>
                    <?php while($u = $users->fetch_assoc()) { ?>
                        <option value="<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php } ?>
                </select>

                <select name="key_id" required>
                    <option value="">Select Quantum Key</option>
                    <?php while($k = $keys->fetch_assoc()) { ?>
                        <option value="<?= $k['id'] ?>">
                            <?= $k['key_length'] ?>-bit Key
                        </option>
                    <?php } ?>
                </select>

                <input type="datetime-local" name="expiry" required>

                <button type="submit" name="upload">
                    Encrypt & Share File
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ================= FILE LIST SECTION ================= -->
<div class="files-layout">
    <div class="files-content">

        <!-- FILES SHARED WITH ME -->
        <div class="inbox-container">
            <h2 class="file-title">📥 Files Shared With You</h2>

            <?php while($f = $receivedFiles->fetch_assoc()) { ?>
                <div class="inbox-card received">
                    <img src="images/image2.jpg">
                    <div class="inbox-content">
                        <strong><?= htmlspecialchars($f['original_filename']) ?></strong>
                        <p>From: <?= htmlspecialchars($f['sender_name']) ?></p>
                        <small>Expires: <?= $f['expiry_time'] ?></small>

                        <a class="download-btn"
                           href="download_file.php?id=<?= $f['id'] ?>">
                            Download
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
        

        <!-- FILES I SHARED -->
        <div class="inbox-container">
            <h2 class="file-title">📤 Files You Shared</h2>

            <?php while($f = $myFiles->fetch_assoc()) { ?>
                <div class="inbox-card sent">
                    <img src="images/image2.jpg">
                    <div class="inbox-content">
                        <strong><?= htmlspecialchars($f['original_filename']) ?></strong>
                        <p>To: <?= htmlspecialchars($f['receiver_name']) ?></p>
                        <small>Expires: <?= $f['expiry_time'] ?></small>
                    </div>
                </div>
            <?php } ?>
        </div>

    </div>
</div>

</div>
</body>
</html>
