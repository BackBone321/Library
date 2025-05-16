<?php
require_once __DIR__ . '/db_conn.php';

        // Handle add supply
        if (isset($_POST['add_supply'])) {
            $item_name = $_POST['item_name'];
            $quantity = $_POST['quantity'];
            $stmt = $conn->prepare("INSERT INTO supplies (item_name, quantity) VALUES (?, ?)");
            $stmt->bind_param("si", $item_name, $quantity);
            $stmt->execute();
            $note = "New supply added: $item_name ($quantity pcs)";
            $conn->query("INSERT INTO notifications (message, status) VALUES ('$note', 'unread')");
            header('Location: supply_admin.php');
            exit;
        }

        // Handle purchase in/out
        if (isset($_POST['transaction'])) {
            $supply_id = $_POST['supply_id'];
            $quantity = $_POST['quantity'];
            $action = $_POST['action'];
            $adjustment = ($action === 'purchase_in') ? '+' : '-';
            $conn->query("UPDATE supplies SET quantity = quantity $adjustment $quantity WHERE id = $supply_id");
            $row = $conn->query("SELECT item_name FROM supplies WHERE id = $supply_id")->fetch_assoc();
            $note = ucfirst(str_replace('_', ' ', $action)) . ": {$row['item_name']} ($quantity pcs)";
            $conn->query("INSERT INTO notifications (message, status) VALUES ('$note', 'unread')");
            header('Location: index.php');
            exit;
        }

        // Handle borrow request
        if (isset($_POST['borrow_request'])) {
            $item_name = $_POST['item_name'];
            $quantity = $_POST['quantity'];
            $conn->query("INSERT INTO borrow_requests (item_name, quantity) VALUES ('$item_name', '$quantity')");
            $note = "Borrow request: $item_name ($quantity pcs)";
            $conn->query("INSERT INTO notifications (message, status) VALUES ('$note', 'unread')");
            header('Location: index.php');
            exit;
        }

        if (isset($_POST['delete_supply'])) {
            $delete_id = (int)$_POST['delete_id'];

            $check = $conn->query("SELECT quantity FROM supplies WHERE id = $delete_id LIMIT 1");

            if ($check && $check->num_rows > 0) {
                $row = $check->fetch_assoc();
                $quantity = (int)$row['quantity'];

                if ($quantity >= 0) {
                    $conn->query("DELETE FROM supplies WHERE id = $delete_id");
                    echo "<script>alert('Supply deleted successfully.'); window.location.href='supply_admin.php';</script>";
                } else {
                    echo "<script>alert('Cannot delete. Quantity must be zero. Current: $quantity'); window.location.href='index.php';</script>";
                }
            } else {
                echo "<script>alert('Supply not found.'); window.location.href='index.php';</script>";
            }
        }       

            $borrow_list = $conn->query("SELECT * FROM borrow_requests WHERE status = 'pending' ORDER BY created_at DESC");
            $unread_count = $borrow_list->num_rows;
        
        
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
                $id = intval($_POST['id']);
                $action = $_POST['action'];
                
                if ($action === 'approve') {
                    $status = 'approved';
                    $user_notified = 0;
                } else if ($action === 'disapprove') {
                    $status = 'disapproved';
                    $user_notified = 1;
                } else {
                    // Invalid action
                   // header("Location: supply_admin.php");
                   // exit;
                }
                
                // Update the request status
               $sql = "UPDATE borrow_requests SET status = '$status', user_notified = $user_notified, updated_at = NOW() WHERE id = $id";
                
                if ($conn->query($sql)) {
                    echo "<script>alert('Request has been $status successfully!'); window.location.href = 'supply_admin.php';</script>";
                } else {
                    echo "<script>alert('Error updating request: " . $conn->error . "'); window.location.href = 'supply_admin.php';</script>";
                }
            } else {
                // Redirect if accessed directly without proper parameters
               // header("Location: supply_admin.php");
               // exit;
            }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="style1.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="supply.css?v=<?php echo time(); ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJtJ7tJkPmcV9f9fGvGkUuJkqMX6IQWuK/4hDh3KpWwW9Dptf4U/JpP4OmVZ" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0v8FqI1P+8zUuK0CptlX+u0xP1z5DiH1ua7Tgpm2U4B7w+My" crossorigin="anonymous"></script>
</head>
<body>
<div class="container-fluid mt-4 p-4 rounded" style="background-color:rgb(216, 216, 216);">
            <div class="d-flex align-items-center gap-2 mb-3 inventory-header">
                <i class="fas fa-dolly-flatbed fa-2x supply-icon"></i>
                <h2 class="supply supply-font">Inventory Management</h2>
            </div>
                    <div class="row align-items-start">
                    <!-- Add New Supply Form -->
                    <div class="col-md-6">
                        <h4>Add New Supply</h4>
                        <form action="supply_admin.php" method="POST" class="custom-form">
                            <div class="mb-3">
                                <label for="item_name">Item Name</label>
                                <input type="text" name="item_name" id="item_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quantity">Quantity</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" required>
                            </div>
                            <button type="submit" name="add_supply" class="btn btn-primary w-100">Add Supply</button>
                        </form>
                    </div>

                    <?php
                    $unread_count = $conn->query("SELECT COUNT(*) AS count FROM borrow_requests WHERE status = 'pending'")->fetch_assoc()['count'];
                    ?>

                    <!-- Notification Icon -->
                    <div class="notification-wrapper text-end mb-2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#borrowRequestModal">
                            <i class="fas fa-bell" style="color: black; font-weight: 100;"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge" id="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <!-- Purchase In/Out Form -->
                    <div class="col-md-6">
                        <h4>Purchase In/Out</h4>
                        <form class="custom-form" action="index.php" method="POST">
                            <div class="mb-3">
                                <label for="supply_id">Select Item</label>
                                <select name="supply_id" class="form-select" required>
                                    <?php
                                    $result = $conn->query("SELECT * FROM supplies");
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['item_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantity2">Quantity</label>
                                <input type="number" name="quantity" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="action">Action</label>
                                <select name="action" class="form-select" required>
                                    <option value="purchase_in">Purchase In</option>
                                    <option value="purchase_out">Purchase Out</option>
                                </select>
                            </div>
                            <button type="submit" name="transaction" class="btn btn-primary w-100">Submit Transaction</button>
                        </form>
                    </div>
                </div>


                    <!-- Right Section -->
                        <h4 class="supply">All Supplies</h4>
                        <div class="table-responsive">

                        <?php
                        $borrow_list = $conn->query("SELECT * FROM borrow_requests WHERE status = 'pending' ORDER BY created_at DESC");
                        ?>
                        
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-body">
                                    <h4 class="card-title mb-3"><i class="fas fa-boxes-stacked me-2"></i>All Supplies</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover table-striped w-100">
                                            <thead class="table-dark text-center">
                                                <tr><th>ID</th><th>Item Name</th><th>Quantity</th><th>Action</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $supply_list = $conn->query("SELECT * FROM supplies");
                                                while ($row = $supply_list->fetch_assoc()) {
                                                    echo "<tr>
                                                            <td>{$row['id']}</td>
                                                            <td>{$row['item_name']}</td>
                                                            <td>{$row['quantity']}</td>
                                                            <td class='text-center'>
                                                                <form method='POST' action='supply_admin.php' onsubmit='return confirm(\"Are you sure?\")'>
                                                                    <input type='hidden' name='delete_id' value='{$row['id']}'>
                                                                    <button type='submit' name='delete_supply' class='btn btn-danger btn-sm'>
                                                                        <i class='fas fa-trash-can'></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- Modal for Borrow Requests -->
                    <div class="modal fade" id="borrowRequestModal" tabindex="-1" aria-labelledby="borrowRequestModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="borrowRequestModalLabel">Pending Borrow Requests</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-warning text-center">
                                            <tr><th>ID</th><th>Name</th><th>User Type</th><th>Item Name</th><th>Quantity</th><th>Date</th><th>Action</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $borrow_list = $conn->query("SELECT * FROM borrow_requests WHERE status = 'pending' ORDER BY id DESC");
                                        while ($row = $borrow_list->fetch_assoc()): ?>
                                            <tr class="fade-request" data-id="<?= $row['id'] ?>">
                                                <td><?= $row['id'] ?></td>
                                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                                <td><?= htmlspecialchars($row['user_type']) ?></td> 
                                                <td><?= htmlspecialchars($row['item_name']) ?></td>
                                                <td><?= $row['quantity'] ?></td>
                                                <td><?= $row['created_at'] ?></td>
                                                <td class="text-center">
                                                    <form method="POST" action="supply_admin.php" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                        <button name="action" value="disapprove" class="btn btn-danger btn-sm">Disapprove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                        setTimeout(() => {
                        document.querySelectorAll('.fade-request').forEach(row => {
                            row.style.transition = 'opacity 1s ease';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 1000); 
                        });
                    }, 60000); /
                    </script>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const borrowModal = document.getElementById('borrowRequestModal');
                        if (borrowModal) {
                            borrowModal.addEventListener('shown.bs.modal', function () {
                                console.log('Modal opened, marking notifications as read...');
                                fetch('mark_notifications_read.php')
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('Network response was not ok');
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        console.log(data);
                                        if (data.status === 'success') {
                                            const badge = document.getElementById('notification-badge');
                                            if (badge) badge.remove();
                                        } else {
                                            console.error('Error in marking notifications as read');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error marking notifications as read:', error);
                                    });
                            });
                        }
                    });
                    </script>

            <!-- Bootstrap JS (optional for dropdowns/modal etc) -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>