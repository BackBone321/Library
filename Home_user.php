<?php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash messages
$successMessage = '';
$errorMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

try {
    // Database connection with UTF-8 encoding
    $pdo = new PDO("mysql:host=localhost;dbname=library;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle search and filter
    $searchTerm = isset($_GET['search']) ? trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS)) : '';
    $showAvailable = isset($_GET['show']) && $_GET['show'] === 'available';

    // Fetch books with search and filter
    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (title LIKE ? OR author LIKE ? OR genre LIKE ?)";
        $searchPattern = "%$searchTerm%";
        $params = [$searchPattern, $searchPattern, $searchPattern];
    }
    
    if ($showAvailable) {
        $sql .= " AND status = 'Available'";
    }
    
    $sql .= " ORDER BY title ASC";
    error_log("Search SQL: $sql, Params: " . print_r($params, true));
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter available books for display
    $availableBooks = array_filter($allBooks, function($book) {
        return $book['status'] === 'Available';
    });
    
    // Count total, available, and borrowed books
    $totalBooks = count($allBooks);
    $availableBooksCount = count($availableBooks);
    $borrowedBooksCount = $totalBooks - $availableBooksCount;

    // Handle borrow request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['borrow'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = "Security validation failed";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        // Save form data to session
        $_SESSION['borrower_form'] = [
            'student_id' => $_POST['student_id'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'facebook' => $_POST['facebook'] ?? '',
            'email' => $_POST['email'] ?? '',
            'date_borrowed' => $_POST['date_borrowed'] ?? ''
        ];

        // Validate inputs
        $errors = [];
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS);
        $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $middle_name = filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $facebook = filter_input(INPUT_POST, 'facebook', FILTER_SANITIZE_URL);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $date_borrowed = filter_input(INPUT_POST, 'date_borrowed', FILTER_SANITIZE_SPECIAL_CHARS);
        
        if (!$email) {
            $errors[] = "Invalid email address";
        }
        
        $selectedBooks = isset($_POST['selected_books']) ? $_POST['selected_books'] : [];
        if (empty($selectedBooks)) {
            $errors[] = "No books selected";
        }
        
        // Log selected books for debugging
        error_log("Selected Books: " . print_r($selectedBooks, true));
        
        // Convert empty middle_name to NULL
        $middle_name = empty($middle_name) ? null : $middle_name;

        if (empty($errors)) {
            $student = [
                'student_id' => $student_id,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'facebook' => $facebook,
                'email' => $email,
                'date_borrowed' => $date_borrowed
            ];
            
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Log POST data for debugging
                error_log("Borrow POST Data: " . print_r($student, true));

                // Check if borrower already exists
                $stmt = $pdo->prepare("SELECT id FROM borrowers WHERE student_id = ?");
                $stmt->execute([$student['student_id']]);
                $existingBorrower = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingBorrower) {
                    $borrower_id = $existingBorrower['id'];
                    
                    // Update borrower info
                    $stmt = $pdo->prepare("UPDATE borrowers SET first_name = ?, middle_name = ?, last_name = ?, phone = ?, facebook = ?, email = ? WHERE id = ?");
                    $stmt->execute([
                        $student['first_name'],
                        $student['middle_name'],
                        $student['last_name'],
                        $student['phone'],
                        $student['facebook'],
                        $student['email'],
                        $borrower_id
                    ]);
                } else {
                    // Insert new borrower
                    $stmt = $pdo->prepare("INSERT INTO borrowers (student_id, first_name, middle_name, last_name, phone, facebook, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $student['student_id'],
                        $student['first_name'],
                        $student['middle_name'],
                        $student['last_name'],
                        $student['phone'],
                        $student['facebook'],
                        $student['email']
                    ]);
                    $borrower_id = $pdo->lastInsertId();
                }

                // Insert borrowed books and update status
                $booksProcessed = 0;
                foreach ($selectedBooks as $bookId) {
                    // Verify book is available
                    $stmt = $pdo->prepare("SELECT status FROM books WHERE id = ?");
                    $stmt->execute([$bookId]);
                    $book = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($book && $book['status'] === 'Available') {
                        // Update book status
                        $stmt = $pdo->prepare("UPDATE books SET status = 'Not Available' WHERE id = ?");
                        $stmt->execute([$bookId]);

                        // Record borrowing
                        $stmt = $pdo->prepare("INSERT INTO borrowed_books (borrower_id, book_id, date_borrowed) VALUES (?, ?, ?)");
                        $stmt->execute([$borrower_id, $bookId, $student['date_borrowed']]);
                        $booksProcessed++;
                    }
                }

                $pdo->commit();
                // Clear borrower form data from session on success
                unset($_SESSION['borrower_form']);
                $_SESSION['success_message'] = "Successfully borrowed $booksProcessed book(s)";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Borrow error: " . $e->getMessage());
                $_SESSION['error_message'] = "Error processing request: " . $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['error_message'] = implode(", ", $errors);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    // Handle return request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = "Security validation failed";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $borrowed_id = filter_input(INPUT_POST, 'borrowed_id', FILTER_VALIDATE_INT);
        $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
        
        if ($borrowed_id && $book_id) {
            $pdo->beginTransaction();
            
            try {
                // Delete the borrowed book record
                $stmt = $pdo->prepare("DELETE FROM borrowed_books WHERE id = ?");
                $stmt->execute([$borrowed_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Update book status to available
                    $stmt = $pdo->prepare("UPDATE books SET status = 'Available' WHERE id = ?");
                    $stmt->execute([$book_id]);
                    
                    $pdo->commit();
                    $_SESSION['success_message'] = "Book returned successfully";
                } else {
                    $pdo->rollBack();
                    $_SESSION['error_message'] = "Borrowed book record not found";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Return error: " . $e->getMessage());
                $_SESSION['error_message'] = "Error processing return: " . $e->getMessage();
            }
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    // Determine which page to show
    $page = isset($_GET['page']) ? $_GET['page'] : 'home';

    // Refresh books list after any operation
    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (title LIKE ? OR author LIKE ? OR genre LIKE ?)";
        $searchPattern = "%$searchTerm%";
        $params = [$searchPattern, $searchPattern, $searchPattern];
    }
    
    if ($showAvailable) {
        $sql .= " AND status = 'Available'";
    }
    
    $sql .= " ORDER BY title ASC";
    error_log("Refresh SQL: $sql, Params: " . print_r($params, true));
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $availableBooks = array_filter($allBooks, function($book) {
        return $book['status'] === 'Available';
    });

    // Fetch borrowed books with middle_name and days out calculation
    $stmt = $pdo->prepare("SELECT bb.id, bb.borrower_id, bb.book_id, bb.date_borrowed, 
                           b.student_id, b.first_name, b.middle_name, b.last_name, b.email, b.facebook, b.phone, 
                           bk.title, bk.author, bk.genre,
                           DATEDIFF(CURDATE(), bb.date_borrowed) as days_out
                        FROM borrowed_books bb 
                        JOIN borrowers b ON bb.borrower_id = b.id 
                        JOIN books bk ON bb.book_id = bk.id 
                        ORDER BY bb.date_borrowed DESC");
    $stmt->execute();
    $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count overdue books (more than 14 days)
    $overdueBooks = array_filter($borrowedBooks, function($book) {
        return $book['days_out'] > 14;
    });
    $overdueBooksCount = count($overdueBooks);

} catch (PDOException $e) {
    $errorMessage = "Database connection failed: " . $e->getMessage();
    error_log("Database connection error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'function/design.php'; ?>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="?page=home">
                <i class="fas fa-book-reader"></i> LIBRARY Opening hours - Monday to Friday 8:00 AM to 5:00 PM
            </a> 
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>" href="?page=home">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'borrowed' ? 'active' : ''; ?>" href="?page=borrowed">
                            <i class="fas fa-hand-holding-heart me-1"></i> Borrowed Books
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($successMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($page === 'home' || $page === ''): ?>
            <!-- Dashboard Statistics -->
          
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card stats-total">
            <div class="icon"><i class="fas fa-books"></i></div>
            <div class="count"><?php echo $totalBooks; ?></div>
            <div class="title">Total Books</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card stats-available">
            <div class="icon"><i class="fas fa-book"></i></div>
            <div class="count"><?php echo $availableBooksCount; ?></div>
            <div class="title">Available Books</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card" style="background: linear-gradient(45deg, #FFC107, #87CEEB);">
            <div class="icon"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="count"><?php echo $borrowedBooksCount; ?></div>
            <div class="title">Borrowed Books and Request</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card" style="background: linear-gradient(45deg,rgb(226, 103, 2),rgb(151, 3, 3));">
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="count"><?php echo $overdueBooksCount; ?></div>
            <div class="title">Overdue Books</div>
        </div>
    </div>
</div>

            <!-- Main Content Tabs -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="libraryTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="borrow-tab" data-bs-toggle="tab" data-bs-target="#borrow-tab-pane" 
                                    type="button" role="tab" aria-controls="borrow-tab-pane" aria-selected="true">
                                <i class="fas fa-hand-holding me-1"></i> Borrow Books
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="books-tab" data-bs-toggle="tab" data-bs-target="#books-tab-pane" 
                                   type="button" role="tab" aria-controls="books-tab-pane" aria-selected="false">
                                <i class="fas fa-book me-1"></i> Books Catalog
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content p-3" id="libraryTabsContent">
                        <!-- Borrow Books Tab -->
                        <div class="tab-pane fade show active" id="borrow-tab-pane" role="tabpanel" aria-labelledby="borrow-tab" tabindex="0">
                            <form method="POST" action="" onsubmit="console.log('Borrow form submitted');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                
                                <!-- Borrower Information Card -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Borrower Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="student_id" class="form-label">Student ID</label>
                                                <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['student_id'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['first_name'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="middle_name" class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['middle_name'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['last_name'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="date_borrowed" class="form-label">Date Borrowed</label>
                                                <input type="date" class="form-control" id="date_borrowed" name="date_borrowed" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['date_borrowed'] ?? date('Y-m-d')); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['phone'] ?? ''); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="facebook" class="form-label">Facebook Profile</label>
                                                <input type="url" class="form-control" id="facebook" name="facebook" placeholder="https://www.facebook.com/username" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['facebook'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['borrower_form']['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Book Selection Card -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Select Books to Borrow</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Search, Filter, and Button Row -->
                                        <div class="row mb-4 align-items-center">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <input type="search" class="form-control" placeholder="Search by title, author, or genre" 
                                                           id="bookSearch" onkeyup="filterBooks()">
                                                    
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <div class="d-inline-block">
                                                    <div class="form-check form-switch d-inline-block me-3">
                                                        <input class="form-check-input" type="checkbox" id="showOnlyAvailable" checked onchange="filterBooks()">
                                                        <label class="form-check-label" for="showOnlyAvailable">Show only available books</label>
                                                    </div>
                                                    <button type="submit" name="borrow" class="btn btn-primary d-inline-block">
                                                        <i class="fas fa-paper-plane me-2"></i>Submit Borrowing Request
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Book List with Selection -->
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="bookTable">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 50px;"></th>
                                                        <th style="width: 50px;">I.D</th>
                                                        <th>Title</th>
                                                        <th>Author</th>
                                                        <th>Genre</th>
                                                        <th>Status</th>
                                                        <th>Days Out</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($allBooks as $book): ?>
                                                    <tr class="book-item <?php echo $book['status'] === 'Available' ? 'book-status-available' : 'book-status-unavailable'; ?>"
                                                        data-status="<?php echo htmlspecialchars($book['status']); ?>"
                                                        data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                                        data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                                        data-genre="<?php echo htmlspecialchars($book['genre']); ?>">
                                                        <td>
                                                            <?php if ($book['status'] === 'Available'): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="selected_books[]" 
                                                                      value="<?php echo $book['id']; ?>" id="book<?php echo $book['id']; ?>">
                                                            </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($book['id']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                        <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                                        <td>
                                                            <?php if ($book['status'] === 'Available'): ?>
                                                                <span class="badge bg-success">Available</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Borrowed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $stmt = $pdo->prepare("SELECT DATEDIFF(CURDATE(), date_borrowed) as days_out FROM borrowed_books WHERE book_id = ?");
                                                            $stmt->execute([$book['id']]);
                                                            $borrowed = $stmt->fetch(PDO::FETCH_ASSOC);
                                                            if ($borrowed && $book['status'] === 'Not Available') {
                                                                $days_out = $borrowed['days_out'];
                                                                if ($days_out > 14) {
                                                                    echo '<span class="badge bg-danger">' . $days_out . ' days</span>';
                                                                } elseif ($days_out > 7) {
                                                                    echo '<span class="badge bg-warning text-dark">' . $days_out . ' days</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-info">' . $days_out . ' days</span>';
                                                                }
                                                            } else {
                                                                echo 'N/A';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- No Books Message -->
                                        <div id="noBooks" class="alert alert-info text-center d-none">
                                            <i class="fas fa-info-circle me-2"></i> No books match your search criteria
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Books Catalog Tab -->
                        <div class="tab-pane fade" id="books-tab-pane" role="tabpanel" aria-labelledby="books-tab" tabindex="0">
                            <!-- Search and Filter -->
                            <form action="" method="GET" class="mb-4">
                                <input type="hidden" name="page" value="home">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <input type="search" class="form-control" placeholder="Search by title, author, or genre" 
                                                   name="search" id="catalogSearch" value="<?php echo htmlspecialchars($searchTerm); ?>">
                                            
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="filterAvailable" name="show" 
                                                   value="available" <?php echo $showAvailable ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="filterAvailable">Show only available books</label>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Book Catalog Table -->
                            <div class="table-responsive">
                                <table class="table table-hover" id="catalogTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">I.D</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Genre</th>
                                            <th>Status</th>
                                            <th>Days Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($allBooks) > 0): ?>
                                            <?php foreach ($allBooks as $book): ?>
                                            <tr class="<?php echo $book['status'] === 'Available' ? 'book-status-available' : 'book-status-unavailable'; ?>"
                                                data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                                data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                                data-genre="<?php echo htmlspecialchars($book['genre']); ?>"
                                                data-status="<?php echo htmlspecialchars($book['status']); ?>">
                                                <td><?php echo htmlspecialchars($book['id']); ?></td>
                                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                                <td>
                                                    <?php if ($book['status'] === 'Available'): ?>
                                                        <span class="badge bg-success">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Borrowed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $stmt = $pdo->prepare("SELECT DATEDIFF(CURDATE(), date_borrowed) as days_out FROM borrowed_books WHERE book_id = ?");
                                                    $stmt->execute([$book['id']]);
                                                    $borrowed = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    if ($borrowed && $book['status'] === 'Not Available') {
                                                        $days_out = $borrowed['days_out'];
                                                        if ($days_out > 14) {
                                                            echo '<span class="badge bg-danger">' . $days_out . ' days</span>';
                                                        } elseif ($days_out > 7) {
                                                            echo '<span class="badge bg-warning text-dark">' . $days_out . ' days</span>';
                                                        } else {
                                                            echo '<span class="badge bg-info">' . $days_out . ' days</span>';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No books found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- No Books Message -->
                            <div id="noCatalogBooks" class="alert alert-info text-center d-none">
                                <i class="fas fa-info-circle me-2"></i> No books match your search criteria
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($page === 'borrowed'): ?>
            <!-- Borrowed Books Page -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-heart me-2"></i>Borrowed Books</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">I.D</th>
                                    <th>Borrower</th>
                                    <th>Book</th>
                                    <th>Date Borrowed</th>
                                    <th>Days Out</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($borrowedBooks) > 0): ?>
                                    <?php foreach ($borrowedBooks as $book): ?>
                                    <tr class="<?php echo $book['days_out'] > 14 ? 'overdue-highlight' : ''; ?>">
                                        <td><?php echo htmlspecialchars($book['book_id']); ?></td>
                                        <td>
                                            <?php 
                                            echo htmlspecialchars($book['first_name']) . ' ';
                                            if (!empty($book['middle_name'])) {
                                                echo htmlspecialchars($book['middle_name']) . ' ';
                                            }
                                            echo htmlspecialchars($book['last_name']);
                                            ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($book['student_id']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                            <small class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['date_borrowed']); ?></td>
                                        <td>
                                            <?php if ($book['days_out'] > 14): ?>
                                                <span class="badge bg-danger"><?php echo $book['days_out']; ?> days</span>
                                            <?php elseif ($book['days_out'] > 7): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $book['days_out']; ?> days</span>
                                            <?php else: ?>
                                                <span class="badge bg-info"><?php echo $book['days_out']; ?> days</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="contact-icons">
                                            <a href="mailto:<?php echo htmlspecialchars($book['email']); ?>"><?php echo htmlspecialchars($book['email']); ?></a><br>
                                            <a href="tel:<?php echo htmlspecialchars($book['phone']); ?>"><?php echo htmlspecialchars($book['phone']); ?></a>
                                            <?php if (!empty($book['facebook'])): ?>
                                            <br>
                                            <a href="<?php echo htmlspecialchars($book['facebook']); ?>" target="_blank" title="Facebook">
                                                <i class="fab fa-facebook"></i> Facebook
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="borrowed_id" value="<?php echo $book['id']; ?>">
                                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                                <button type="submit" name="return" class="btn btn-success btn-sm" 
                                                        onclick="return confirm('Confirm this book has been returned?')">
                                                    <i class="fas fa-check-circle me-1"></i> Return
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No books currently borrowed</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>© <?php echo date('Y'); ?> Library Hub. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Version 1.0.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="floating-btn" data-bs-toggle="modal" data-bs-target="#quickActionsModal">
            <i class="fas fa-plus"></i>
        </div>
    </div>

    <!-- Quick Actions Modal -->
    <div class="modal fade" id="quickActionsModal" tabindex="-1" aria-labelledby="quickActionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickActionsModalLabel">Quick Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <a href="?page=home" class="btn btn-outline-light">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                        <a href="?page=borrowed" class="btn btn-outline-light">
                            <i class="fas fa-hand-holding-heart me-2"></i> Borrowed Books
                        </a>
                        <a href="?page=admin" class="btn btn-outline-light">
                            <i class="fas fa-user-shield me-2"></i> Admin Panel
                        </a>
                        <a href="#" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="tab" data-bs-target="#borrow-tab-pane">
                            <i class="fas fa-paper-plane me-2"></i> New Borrowing Request
                        </a>
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=jweek967@gmail.com&su=Library%20Inquiry&body=Please%20add%20your%20Gmail%20account%20at%20the%20top%20before%20sending%20this%20email.%0D%0A%0D%0AHello%20Library%20Team,%0D%0A%0D%0AI%20would%20like%20to%20inquire%20about%20a%20library%20matter.%20Please%20let%20me%20know%20how%20I%20can%20proceed.%0D%0A%0D%0AThanks,%0D%0A[Your%20Name]
" class="btn btn-success">
                            <i class="fas fa-envelope me-2"></i> Send Gmail
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterBooks() {
            const searchInput = document.getElementById('bookSearch').value.toLowerCase();
            const showOnlyAvailable = document.getElementById('showOnlyAvailable').checked;
            const bookRows = document.querySelectorAll('#bookTable .book-item');
            const noBooksMessage = document.getElementById('noBooks');
            let visibleRows = 0;

            bookRows.forEach(row => {
                const title = row.dataset.title.toLowerCase();
                const author = row.dataset.author.toLowerCase();
                const genre = row.dataset.genre.toLowerCase();
                const status = row.dataset.status;

                const matchesSearch = title.includes(searchInput) || author.includes(searchInput) || genre.includes(searchInput);
                const matchesStatus = !showOnlyAvailable || status === 'Available';

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            noBooksMessage.classList.toggle('d-none', visibleRows > 0);
        }

        // Run filter on page load
        document.addEventListener('DOMContentLoaded', filterBooks);
    </script>
    <?php include 'function/search.php'; ?>
</body>
</html>