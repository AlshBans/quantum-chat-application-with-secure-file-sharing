<?php
session_start();
require_once "db/db.php";
require_once "log_action.php"; // ✅ ADD LOGGING SUPPORT

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}


$user_id = $_SESSION['user_id'];

/* ================= VALIDATE FILE ID ================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid file request.");
}

$file_id = (int) $_GET['id'];

/* ================= FETCH FILE ================= */
$stmt = $conn->prepare(
    "SELECT * FROM secure_files WHERE id = ?"
);
$stmt->bind_param("i", $file_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found.");
}

$file = $result->fetch_assoc();

/* ================= ACCESS CONTROL ================= */
/*
   Allowed:
   - Receiver
   - Sender
*/
if ($file['receiver_id'] != $user_id && $file['sender_id'] != $user_id) {
    die("You do not have permission to download this file.");
}

/* ================= EXPIRY CHECK ================= */
$current_time = date("Y-m-d H:i:s");

if ($current_time > $file['expiry_time']) {
    die("⛔ This file has expired and is no longer available.");
}

/* ================= FILE PATH ================= */
$file_path = "uploads/secure_files/" . $file['stored_filename'];

if (!file_exists($file_path)) {
    die("File is missing on the server.");
}

/* ================= LOG DOWNLOAD ================= */
logAction(
    $conn,
    $user_id,
    "FILE_DOWNLOADED",
    "Downloaded file: {$file['original_filename']}"
);

/* ================= FORCE DOWNLOAD ================= */
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header(
    "Content-Disposition: attachment; filename=\"" .
    basename($file['original_filename']) . "\""
);
header("Content-Length: " . filesize($file_path));
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Expires: 0");

readfile($file_path);
exit();
