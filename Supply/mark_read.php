<?php
include 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $id = $_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: index.php#notifications");
exit;
?>
