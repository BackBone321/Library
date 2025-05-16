<?php
include 'db_conn.php';

if (isset($_POST['notification_id'])) {
    $id = intval($_POST['notification_id']);
    $conn->query("UPDATE notifications SET status = 'read' WHERE id = $id");
}
header("Location: notify.php"); // Redirect back
exit();
?>
