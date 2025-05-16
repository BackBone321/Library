<?php
require 'config.php'; // Or whatever DB connection file you use

$conn->query("UPDATE borrow_requests SET user_notified = 1 WHERE (status = 'approved' OR status = 'disapproved') AND user_notified = 0");
echo json_encode(['status' => 'success']);


