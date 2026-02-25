<?php
session_start();
require_once "db/db.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= FETCH USERS ================= */
$users = $conn->query(
    "SELECT id, name FROM users WHERE id != $user_id"
);

/* ================= FETCH UNUSED KEYS ================= */
$keys = $conn->query(
    "SELECT id, key_length 
     FROM quantum_keys 
     WHERE user_id = $user_id AND status = 'unused'"
);

/* ================= SEND MESSAGE ================= */
if (isset($_POST['send'])) {

    $receiver = $_POST['receiver'];
    $key_id   = $_POST['key_id'];
    $message  = $_POST['message'];

    /* Fetch key value */
    $keyRow = $conn->query(
        "SELECT key_value FROM quantum_keys WHERE id = $key_id"
    )->fetch_assoc();

    $key = $keyRow['key_value'];

    /* Encrypt message */
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt(
        $message,
        "AES-256-CBC",
        $key,
        0,
        $iv
    );

    $final_message = base64_encode($iv . $encrypted);

    /* Store message */
    $stmt = $conn->prepare(
        "INSERT INTO messages (sender_id, receiver_id, key_id, encrypted_message)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "iiis",
        $user_id,
        $receiver,
        $key_id,
        $final_message
    );
    $stmt->execute();

    /* Mark key as used */
    $conn->query(
        "UPDATE quantum_keys SET status='used' WHERE id = $key_id"
    );

    header("Location: secure_message.php");
    exit();
}

/* ================= FETCH INBOX ================= */
$inbox = $conn->query(
    "SELECT m.*, u.name AS sender_name, q.key_value
     FROM messages m
     JOIN users u ON m.sender_id = u.id
     JOIN quantum_keys q ON m.key_id = q.id
     WHERE m.receiver_id = $user_id
     ORDER BY m.created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure Quantum Messaging</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ================= NAVBAR ================= -->
<header class="navbar">
    <h2>QuantumSecure</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="secure-message-page">

<!-- ================= TITLE ================= -->
<section class="section">
    <h2>Secure Quantum Messaging</h2>
    <p>Send and receive messages encrypted using quantum-generated keys</p>
</section>

<!-- ================= SEND MESSAGE ================= -->
<div class="messaging-container">

    <div class="message-card">

        <div class="message-image">
            <img src="images/message_send2.jpg" alt="Secure Messaging">
        </div>

        <div class="message-form">
            <h3>Send Encrypted Message</h3>

            <form method="post">

                <select name="receiver" required>
                    <option value="">Select Receiver</option>
                    <?php while ($u = $users->fetch_assoc()) { ?>
                        <option value="<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php } ?>
                </select>

                <select name="key_id" required>
                    <option value="">Select Quantum Key</option>
                    <?php while ($k = $keys->fetch_assoc()) { ?>
                        <option value="<?= $k['id'] ?>">
                            <?= $k['key_length'] ?>-bit Key
                        </option>
                    <?php } ?>
                </select>

                <textarea name="message"
                          placeholder="Enter secure message..."
                          required></textarea>

                <button type="submit" name="send">
                    Send Encrypted Message
                </button>

            </form>
        </div>
    </div>
</div>

<!-- ================= INBOX ================= -->
<div class="inbox-container">
    <h2 style="text-align:center;margin-bottom:25px;">Inbox</h2>

    <?php while ($msg = $inbox->fetch_assoc()) { ?>

        <?php
            $data = base64_decode($msg['encrypted_message']);
            $iv = substr($data, 0, 16);
            $cipher = substr($data, 16);

            $decrypted = openssl_decrypt(
                $cipher,
                "AES-256-CBC",
                $msg['key_value'],
                0,
                $iv
            );
        ?>


        <div class="inbox-card">
            <img src="images/message_send.jpg" alt="User">

            <div class="inbox-content">
                <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                <p><?= htmlspecialchars($decrypted) ?></p>
                <small><?= $msg['created_at'] ?></small>
            </div>
        </div>

    <?php } ?>
</div>
</div>

</body>
</html>
