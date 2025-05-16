<?php
include 'db_connection.php';

if ($conn->query("UPDATE borrow_requests SET notified = 1 WHERE status = 'pending' AND notified = 0")) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'failure', 'message' => $conn->error]);
}
?>

