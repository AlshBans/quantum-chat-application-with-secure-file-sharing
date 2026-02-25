<?php
function logAction($conn, $user_id, $action, $description) {
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare(
        "INSERT INTO access_logs (user_id, action_type, description, ip_address)
         VALUES (?, ?, ?, ?)"
    );
    

    $stmt->bind_param("isss", $user_id, $action, $description, $ip);
    $stmt->execute();
}
