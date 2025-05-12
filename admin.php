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

    // Handle add book request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = "Security validation failed";
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        }

        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_SPECIAL_CHARS);
        $genre = filter_input(INPUT_POST, 'genre', FILTER_SANITIZE_SPECIAL_CHARS);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

        $errors = [];
        if (empty($title)) $errors[] = "Title is required";
        if (empty($author)) $errors[] = "Author is required";
        if (empty($genre)) $errors[] = "Genre is required";
        if (!in_array($status, ['Available', 'Not Available'])) $errors[] = "Invalid status";

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, genre, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $author, $genre, $status]);
            $_SESSION['success_message'] = "Book added successfully";
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        } else {
            $_SESSION['error_message'] = implode(", ", $errors);
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        }
    }

    // Handle edit book request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_book'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = "Security validation failed";
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        }

        $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_SPECIAL_CHARS);
        $genre = filter_input(INPUT_POST, 'genre', FILTER_SANITIZE_SPECIAL_CHARS);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

        $errors = [];
        if (!$book_id) $errors[] = "Invalid book ID";
        if (empty($title)) $errors[] = "Title is required";
        if (empty($author)) $errors[] = "Author is required";
        if (empty($genre)) $errors[] = "Genre is required";
        if (!in_array($status, ['Available', 'Not Available'])) $errors[] = "Invalid status";

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, genre = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $author, $genre, $status, $book_id]);
                $_SESSION['success_message'] = "Book updated successfully";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
                exit;
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error updating book: " . $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
                exit;
            }
        } else {
            $_SESSION['error_message'] = implode(", ", $errors);
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        }
    }

    // Handle delete book request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_book'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = "Security validation failed";
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        }

        $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
        if ($book_id) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT id FROM borrowed_books WHERE book_id = ?");
                $stmt->execute([$book_id]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    $_SESSION['error_message'] = "Cannot delete book: It is currently borrowed";
                    header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
                    exit;
                }

                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$book_id]);

                $pdo->commit();
                $_SESSION['success_message'] = "Book deleted successfully";
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Delete book error: " . $e->getMessage());
                $_SESSION['error_message'] = "Error deleting book: " . $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
                exit;
            }
        } else {
            $_SESSION['error_message'] = "Invalid book ID";
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=admin");
            exit;
        }
    }

    // Handle search and filter
    $searchTerm = isset($_GET['search']) ? trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS)) : '';
    $showAvailable = isset($_GET['show']) && $_GET['show'] === 'available';

    // Fetch all books initially
    $sql = "SELECT * FROM books ORDER BY title ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $allBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Apply search and filter for display
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
    $filteredBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Use $allBooks for the table to allow client-side filtering
    $allBooksForDisplay = $allBooks;

    // Filter available books for counts
    $availableBooks = array_filter($allBooks, function($book) {
        return $book['status'] === 'Available';
    });

    // Count total, available, and borrowed books
    $totalBooks = count($allBooks);
    $availableBooksCount = count($availableBooks);
    $borrowedBooksCount = $totalBooks - $availableBooksCount;

    // Handle borrow request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['borrow'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = "Security validation failed";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

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

        $errors = [];
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS);
        $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $middle_name = filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
        $facebook = filter_input(INPUT_POST, 'facebook', FILTER_SANITIZE_URL);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $date_borrowed = filter_input(INPUT_POST, 'date_borrowed', FILTER_SANITIZE_SPECIAL_CHARS);
        
        if (!$email) $errors[] = "Invalid email address";
        
        $selectedBooks = isset($_POST['selected_books']) ? $_POST['selected_books'] : [];
        if (empty($selectedBooks)) $errors[] = "No books selected";
        
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
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT id FROM borrowers WHERE student_id = ?");
                $stmt->execute([$student['student_id']]);
                $existingBorrower = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingBorrower) {
                    $borrower_id = $existingBorrower['id'];
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

                $booksProcessed = 0;
                foreach ($selectedBooks as $bookId) {
                    $stmt = $pdo->prepare("SELECT status FROM books WHERE id = ?");
                    $stmt->execute([$bookId]);
                    $book = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($book && $book['status'] === 'Available') {
                        $stmt = $pdo->prepare("UPDATE books SET status = 'Not Available' WHERE id = ?");
                        $stmt->execute([$bookId]);
                        $stmt = $pdo->prepare("INSERT INTO borrowed_books (borrower_id, book_id, date_borrowed) VALUES (?, ?, ?)");
                        $stmt->execute([$borrower_id, $bookId, $student['date_borrowed']]);
                        $booksProcessed++;
                    }
                }

                $pdo->commit();
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
                $stmt = $pdo->prepare("DELETE FROM borrowed_books WHERE id = ?");
                $stmt->execute([$borrowed_id]);
                
                if ($stmt->rowCount() > 0) {
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

    // Fetch borrowed books with days out calculation
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
    <title>admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS from earlier code -->
    
    <?php include 'function/design.php'; ?>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="?page=home">
                <i class="fas fa-book-reader"></i>LIBRARY Opening hours - monday to friday 8:00 am to 5:00 pm
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'admin' ? 'active' : ''; ?>" href="?page=admin">
                            <i class="fas fa-user-shield me-1"></i> Admin Panel
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
                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="search" class="form-control" placeholder="Search by title, author, or genre" 
                                id="bookSearch" onkeyup="filterBooks()">
                           
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showOnlyAvailable" onchange="filterBooks()">
                            <label class="form-check-label" for="showOnlyAvailable">Show only available books</label>
                        </div>
                        <button type="submit" name="borrow" class="btn btn-primary mt-3">
                            <i class="fas fa-paper-plane me-2"></i>Submit Borrowing Request
                        </button>
                    </div>
                </div>

                <!-- Book List with Selection -->
                <div class="table-responsive">
                    <table class="table table-hover" id="bookTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 50px;"></th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Genre</th>
                                <th>Status</th>
                                <th>Days Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allBooksForDisplay as $book): ?>
                            <tr class="book-item <?php echo $book['status'] === 'Available' ? 'book-status-available' : 'book-status-unavailable'; ?>"
                                data-status="<?php echo htmlspecialchars($book['status']); ?>"
                                data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                data-genre="<?php echo htmlspecialchars($book['genre']); ?>">
                                <td><?php echo htmlspecialchars($book['id']); ?></td>
                                <td>
                                    <?php if ($book['status'] === 'Available'): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="selected_books[]" 
                                            value="<?php echo $book['id']; ?>" id="book<?php echo $book['id']; ?>">
                                    </div>
                                    <?php endif; ?>
                                </td>
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
                                                   name="search" id="catalogSearch">
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Book Catalog Table -->
                            <div class="table-responsive">
                                <table class="table table-hover" id="catalogTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Genre</th>
                                            <th>Status</th>
                                            <th>Days Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($allBooksForDisplay) > 0): ?>
                                            <?php foreach ($allBooksForDisplay as $book): ?>
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
                    <form method="GET" action="" class="mb-4">
                        <input type="hidden" name="page" value="borrowed">
                        <div class="input-group">
                            <input type="text" id="borrowed-search-input" name="search" class="form-control" placeholder="Search by student ID, name, title, or author" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </form>

                    <?php if (!empty($searchTerm)): ?>
                        <p class="text-muted mb-3">Showing results for: <strong><?php echo htmlspecialchars($searchTerm); ?></strong></p>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
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
        <?php elseif ($page === 'admin'): ?>
            <!-- Admin Panel Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Panel</h5>
                </div>
                <div class="card-body">
                    <!-- Add New Book Form -->
                    <h6 class="mb-3">Add New Book</h6>
                    <form method="POST" action="" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-3">
                                <label for="author" class="form-label">Author</label>
                                <input type="text" class="form-control" id="author" name="author" required>
                            </div>
                            <div class="col-md-3">
                                <label for="genre" class="form-label">Genre</label>
                                <input type="text" class="form-control" id="genre" name="genre" required>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Available">Available</option>
                                    <option value="Not Available">Not Available</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_book" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Add Book
                        </button>
                    </form>

                    <!-- List All Books -->
                    <!-- Inside the Admin Panel Card -->
<h6 class="mb-3">All Books</h6>
<form method="GET" action="" class="mb-4">
    <input type="hidden" name="page" value="admin">
    <div class="row g-3 align-items-end">
        <div class="col-md-8">
            <div class="input-group">
                <input type="text" id="admin-search-input" name="search" class="form-control" placeholder="Search by title, author, or genre" value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="col-md-4">
            <label for="status_filter" class="form-label">Status</label>
            <select class="form-select" id="status_filter" name="status_filter" onchange="this.form.submit()">
                <option value="" <?php echo !isset($_GET['status_filter']) || $_GET['status_filter'] === '' ? 'selected' : ''; ?>>All</option>
                <option value="available" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="request" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'request' ? 'selected' : ''; ?>>Request</option>
                <option value="not_available" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'not_available' ? 'selected' : ''; ?>>Not Available</option>
                <option value="overdue" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
    </div>
</form>

<?php if (!empty($searchTerm)): ?>
    <p class="text-muted mb-3">Showing results for: <strong><?php echo htmlspecialchars($searchTerm); ?></strong></p>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Genre</th>
                <th>Status</th>
                <th>Borrower</th>
                <th>Days Out</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="admin-books-table">
            <?php
            // Fetch books with days_out calculation and apply status filter
            $sql = "SELECT b.*, 
                    DATEDIFF(CURDATE(), bb.date_borrowed) as days_out,
                    br.first_name, br.middle_name, br.last_name
                    FROM books b 
                    LEFT JOIN borrowed_books bb ON b.id = bb.book_id 
                    LEFT JOIN borrowers br ON bb.borrower_id = br.id 
                    WHERE 1=1";

            $params = [];
            if (!empty($searchTerm)) {
                $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.genre LIKE ?)";
                $searchPattern = "%$searchTerm%";
                $params = [$searchPattern, $searchPattern, $searchPattern];
            }

// Apply status filter based on days_out and availability
$statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
if ($statusFilter === 'available') {
    $sql .= " AND bb.book_id IS NULL"; // Not borrowed
} elseif ($statusFilter === 'request') {
    $sql .= " AND bb.book_id IS NOT NULL AND DATEDIFF(CURDATE(), bb.date_borrowed) BETWEEN -50 AND -1";
} elseif ($statusFilter === 'not_available') {
    $sql .= " AND bb.book_id IS NOT NULL AND DATEDIFF(CURDATE(), bb.date_borrowed) BETWEEN 1 AND 14";
} elseif ($statusFilter === 'overdue') {
    $sql .= " AND bb.book_id IS NOT NULL AND DATEDIFF(CURDATE(), bb.date_borrowed) BETWEEN 15 AND 500";
}

            $sql .= " ORDER BY b.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $filteredBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filteredBooks as $book):
                $days_out = $book['days_out'] !== null ? $book['days_out'] : 'N/A';
                $borrowerName = 'N/A';
                if ($book['first_name']) {
                    $borrowerName = htmlspecialchars($book['first_name']);
                    if (!empty($book['middle_name'])) {
                        $borrowerName .= ' ' . htmlspecialchars($book['middle_name']);
                    }
                    $borrowerName .= ' ' . htmlspecialchars($book['last_name']);
                }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($book['id']); ?></td>
                <td><?php echo htmlspecialchars($book['title']); ?></td>
                <td><?php echo htmlspecialchars($book['author']); ?></td>
                <td><?php echo htmlspecialchars($book['genre']); ?></td>
                <td>
                    <span class="badge <?php echo $book['status'] === 'Available' ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo htmlspecialchars($book['status']); ?>
                    </span>
                </td>
                <td><?php echo $borrowerName; ?></td>
                <td>
                    <?php
                    if ($days_out !== 'N/A') {
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
                <td>
                    <button type="button" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editBookModal<?php echo $book['id']; ?>">
                        <i class="fas fa-edit me-1"></i>Edit
                    </button>
                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit" name="delete_book" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash-alt me-1"></i>Delete
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
                </div>
            </div>

            <!-- Edit Book Modals -->
            <?php foreach ($allBooksForDisplay as $book): ?>
                <div class="modal fade" id="editBookModal<?php echo $book['id']; ?>" tabindex="-1" aria-labelledby="editBookModalLabel<?php echo $book['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editBookModalLabel<?php echo $book['id']; ?>">Edit Book</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <div class="mb-3">
                                        <label for="edit_title_<?php echo $book['id']; ?>" class="form-label">Title</label>
                                        <input type="text" class="form-control" id="edit_title_<?php echo $book['id']; ?>" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_author_<?php echo $book['id']; ?>" class="form-label">Author</label>
                                        <input type="text" class="form-control" id="edit_author_<?php echo $book['id']; ?>" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_genre_<?php echo $book['id']; ?>" class="form-label">Genre</label>
                                        <input type="text" class="form-control" id="edit_genre_<?php echo $book['id']; ?>" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_status_<?php echo $book['id']; ?>" class="form-label">Status</label>
                                        <select class="form-select" id="edit_status_<?php echo $book['id']; ?>" name="status" required>
                                            <option value="Available" <?php echo $book['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="Not Available" <?php echo $book['status'] === 'Not Available' ? 'selected' : ''; ?>>Not Available</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="edit_book" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=jweek967@gmail.com&su=Library%20Inquiry&body=Hello%20Library%20Team,%0D%0A%0D%0AI%20would%20like%20to%20inquire%20about%20a%20library%20matter.%20Please%20let%20me%20know%20how%20I%20can%20proceed.%0D%0A%0D%0AThanks,%0D%0A[Your%20Name]" class="btn btn-success">
                            <i class="fas fa-envelope me-2"></i> Send Gmail
                        </a>
                    </div>
                </div>
            </div>
        </div>
    cunt>
    
    <?php include 'function/adminsearch.php'; ?>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>