
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Admin</title>
    <link rel="stylesheet" href="style1.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="supply.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="supply_user.css?v=<?php echo time(); ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-gradient p-3 rounded-circle me-3">
                                <i class="fas fa-dolly-flatbed fa-2x text-white"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold">Inventory Management</h3>
                                <p class="text-muted mb-0">Manage your supplies efficiently</p>
                            </div>
                        </div>
                        <div class="notification-wrapper">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#borrowRequestModal" class="position-relative">
                                <div class="p-2 bg-light rounded-circle">
                                    <i class="fas fa-bell fs-4 text-secondary"></i>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-badge">
                                            <?php echo $unread_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <div class="row g-4">
        <!-- Add New Supply Form -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow h-100 border-0 card-add-supply">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2 text-success"></i>Add New Supply</h5>
                </div>
                <div class="card-body">
                    <form action="supply_admin.php" method="POST" class="custom-form">
                        <div class="mb-3">
                            <label for="item_name" class="form-label">Item Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-box text-primary"></i></span>
                                <input type="text" name="item_name" id="item_name" class="form-control" placeholder="Enter item name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-hashtag text-primary"></i></span>
                                <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Enter quantity" required>
                            </div>
                        </div>
                        <button type="submit" name="add_supply" class="btn btn-success w-100">
                            <i class="fas fa-save me-2"></i>Add Supply
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Purchase In/Out Form -->
        <div class="col-md-6 col-lg-4">
            <div class="card shadow h-100 border-0 card-transaction">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="fas fa-exchange-alt me-2 text-primary"></i>Purchase In/Out</h5>
                </div>
                <div class="card-body">
                    <form class="custom-form" action="supply_admin.php" method="POST">
                        <div class="mb-3">
                            <label for="supply_id" class="form-label">Select Item</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-list text-primary"></i></span>
                                <select name="supply_id" class="form-select" required>
                                    <?php
                                    $result = $conn->query("SELECT * FROM supplies");
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['item_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="quantity2" class="form-label">Quantity</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-hashtag text-primary"></i></span>
                                <input type="number" name="quantity" class="form-control" placeholder="Enter quantity" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="action" class="form-label">Action</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-arrows-up-down text-primary"></i></span>
                                <select name="action" class="form-select" required>
                                    <option value="purchase_in">Purchase In</option>
                                    <option value="purchase_out">Purchase Out</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="transaction" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle me-2"></i>Submit Transaction
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Supply Status -->
        <div class="col-lg-4 d-flex">
            <div class="card shadow w-100 border-0 card-status">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2 text-warning"></i>Supply Status</h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="status-icon mx-auto mb-3">
                            <i class="fas fa-boxes-stacked fa-3x text-primary"></i>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <h4 class="fw-bold mb-1">
                                    <?php
                                    $total_items = $conn->query("SELECT COUNT(*) as count FROM supplies")->fetch_assoc()['count'];
                                    echo $total_items;
                                    ?>
                                </h4>
                                <p class="text-muted mb-0"><i class="fas fa-box me-1"></i>Total Items</p>
                            </div>
                            <div class="col-6">
                                <h4 class="fw-bold mb-1">
                                    <?php
                                    $total_quantity = $conn->query("SELECT SUM(quantity) as total FROM supplies")->fetch_assoc()['total'] ?? 0;
                                    echo $total_quantity;
                                    ?>
                                </h4>
                                <p class="text-muted mb-0"><i class="fas fa-cubes me-1"></i>Total Quantity</p>
                            </div>
                            <div class="col-6">
                                <h4 class="fw-bold mb-1">
                                    <?php
                                    $low_stock = $conn->query("SELECT COUNT(*) as count FROM supplies WHERE quantity <= 5")->fetch_assoc()['count'];
                                    echo $low_stock;
                                    ?>
                                    <?php if ($low_stock > 0): ?>
                                        <span class="badge bg-warning ms-1"><i class="fas fa-exclamation-triangle"></i></span>
                                    <?php endif; ?>
                                </h4>
                                <p class="text-muted mb-0"><i class="fas fa-exclamation-circle me-1"></i>Low Stock</p>
                            </div>
                            <div class="col-6">
                                <h4 class="fw-bold mb-1">
                                    <?php
                                    $out_of_stock = $conn->query("SELECT COUNT(*) as count FROM supplies WHERE quantity = 0")->fetch_assoc()['count'];
                                    echo $out_of_stock;
                                    ?>
                                    <?php if ($out_of_stock > 0): ?>
                                        <span class="badge bg-danger ms-1"><i class="fas fa-times-circle"></i></span>
                                    <?php endif; ?>
                                </h4>
                                <p class="text-muted mb-0"><i class="fas fa-ban me-1"></i>Out of Stock</p>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-grid gap-2">
                            <a href="supply_admin.php?export=1" class="btn btn-outline-primary mb-2">
                                <i class="fas fa-file-export me-2"></i>Export Data
                            </a>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recentTransactionsModal">
                                <i class="fas fa-history me-2"></i> View Recent Transactions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Section -->
   <div class="modal fade" id="recentTransactionsModal" tabindex="-1" aria-labelledby="recentTransactionsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recentTransactionsLabel">
                    <i class="fas fa-history me-2 text-success"></i>Recent Transactions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag me-2 text-primary"></i>ID</th>
                                <th><i class="fas fa-info-circle me-2 text-primary"></i>Transaction Details</th>
                                <th><i class="fas fa-cogs me-2 text-primary"></i>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $transaction_list = $conn->query("
                                SELECT t.id, s.item_name, t.quantity, t.action, t.transaction_code 
                                FROM transaction t 
                                JOIN supplies s ON t.supply_id = s.id 
                                ORDER BY t.id DESC 
                                LIMIT 10
                            ");
                            while ($row = $transaction_list->fetch_assoc()) {
                                $type = ($row['action'] === 'purchase_in') ? 'Purchase In' : 'Purchase Out';
                                $icon = ($type === 'Purchase In') ? 'fa-arrow-down text-success' : 'fa-arrow-up text-danger';
                                echo "<tr>
                                        <td>{$row['id']}</td>
                                        <td>Item: " . htmlspecialchars($row['item_name']) . "<br>Qty: {$row['quantity']}<br>Code: {$row['transaction_code']}</td>
                                        <td><i class='fas $icon me-2'></i>$type</td>
                                    </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- All Supplies Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow border-0 card-all-supplies">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-boxes-stacked me-2 text-info"></i>All Supplies</h5>
                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" class="form-control" id="searchSupplies" placeholder="Search items...">
                        <span class="input-group-text bg-primary">
                            <i class="fas fa-search text-white"></i>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2 text-primary"></i>ID</th>
                                    <th><i class="fas fa-box me-2 text-primary"></i>Item Name</th>
                                    <th><i class="fas fa-cubes me-2 text-primary"></i>Quantity</th>
                                    <th class="text-center"><i class="fas fa-cogs me-2 text-primary"></i>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $supply_list = $conn->query("SELECT * FROM supplies");
                                while ($row = $supply_list->fetch_assoc()) {
                                    $quantity = $row['quantity'];
                                    echo "<tr>
                                            <td>{$row['id']}</td>
                                            <td><span class='fw-medium'>{$row['item_name']}</span></td>
                                            <td>
                                                <span class='badge1 bg-" .
                                                    ($quantity > 10 ? 'success' : ($quantity > 5 ? 'warning' : 'danger')) .
                                                    " rounded-pill px-3 py-2'>{$quantity}</span>
                                                " .
                                                ($quantity <= 5 ? "<small class='text-danger ms-2'><i class='fas fa-exclamation-triangle'></i> Low stock</small>" : "") . "
                                            </td>
                                            <td class='text-center'>
                                                <form method='POST' action='supply_admin.php' onsubmit='return confirm(\"Are you sure you want to delete this item?\")' class='d-inline-block'>
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
    </div>

    <!-- Modal for Borrow Requests -->
    <div class="modal fade" id="borrowRequestModal" tabindex="-1" aria-labelledby="borrowRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="borrowRequestModalLabel">
                        <i class="fas fa-hand-holding me-2 text-warning"></i>Pending Borrow Requests
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>User Type</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
            <tbody>
                <?php
                $borrow_list = $conn->query("SELECT * FROM borrow_requests WHERE status = 'pending' ORDER BY id DESC");
                while ($row = $borrow_list->fetch_assoc()): ?>
                    <tr class="fade-request" data-id="<?= $row['id'] ?>">
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($row['user_type']) ?></span></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><small class="text-muted"><?= $row['created_at'] ?></small></td>
                        <td class="text-center">
                            <form method="POST" action="supply_admin.php" class="d-inline">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button name="action" value="approve" class="btn btn-success btn-sm">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                                <button name="action" value="disapprove" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Notification handling
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

    // Auto-hide requests after 60 seconds
    setTimeout(() => {
        document.querySelectorAll('.fade-request').forEach(row => {
            row.style.transition = 'opacity 1s ease';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 1000);
        });
    }, 60000);

    // Search functionality
    const searchInput = document.getElementById('searchSupplies');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchString = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const itemName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                if (itemName.includes(searchString)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
</body>
</html>