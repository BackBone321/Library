<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>University Management System</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Poppins Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f5f7f5; /* Light neutral background */
      position: relative;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path fill="none" stroke="%23FCFFF7" stroke-width="1" opacity="0.1" d="M10 10 L90 90 M90 10 L10 90 M50 10 L50 90 M10 50 L90 50"/></svg>'); /* Subtle weaving pattern in off-white */
      background-repeat: repeat;
    }
    .navbar {
      background-color: #201B51; /* Deep navy blue */
    }
    .navbar-brand, .nav-link {
      color: #FCFFF7 !important; /* Off-white text */
    }
    .hero-section {
      background: linear-gradient(45deg, #201B51, #FBB217, #FCFFF7, #201B51); /* Gradient with USTP colors */
      background-size: 400%;
      color: #FCFFF7; /* Off-white text */
      padding: 120px 0;
      text-align: center;
      animation: colorTransition 10s infinite; /* Color transition animation */
    }
    @keyframes colorTransition {
      0% {
        background-position: 0% 50%;
      }
      50% {
        background-position: 100% 50%;
      }
      100% {
        background-position: 0% 50%;
      }
    }
    .module-card {
      background-color: #FCFFF7; /* Off-white background */
      
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-bottom: 20px;
      transition: transform 0.3s, background-color 0.3s, color 0.3s;
      height: 200px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      text-decoration: none; /* Ensure no underline on links */
      color: inherit; /* Inherit text color */
    }
    .module-card:hover {
      background-color: #201B51; /* Blue on hover */
      transform: translateY(-5px);
    }
    .module-card:hover h3,
    .module-card:hover p,
    .module-card:hover i {
      color: #FCFFF7; /* White text on hover */
    }
    .module-card h3 {
      color: #201B51; /* Deep navy blue for headings */
      font-size: 1.5rem;
      font-weight: 600;
      margin-top: 10px;
    }
    .module-card p {
      color: #6c757d; /* Gray for descriptions */
      font-size: 0.9rem;
    }
    .module-card i {
      font-size: 2rem;
      color: #FBB217; /* Vibrant gold for icons */
    }
    .footer {
      background-color: #201B51; /* Deep navy blue */
      color: #FCFFF7; /* Off-white text */
      padding: 40px 0;
    }
    .footer a {
      color: #FBB217; /* Vibrant gold for links */
      text-decoration: none;
    }
    .footer a:hover {
      color: #FCFFF7; /* Off-white on hover */
    }
    .footer-logo {
      width: 50px;
      height: 50px;
      background-color: #FBB217; /* Vibrant gold */
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #201B51; /* Deep navy blue */
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="#">University Management System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Modules</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <h1 class="display-4 fw-bold">Welcome to University Management System</h1>
      <p class="lead">Streamline your university operations with our comprehensive modules.</p>
    </div>
  </section>

  <!-- Modules Section -->
  <section class="py-5">
    <div class="container">
      <div class="row">
        <!-- Supply Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="Supply/supply_admin.php" class="module-card">
            <i class="bi bi-box-seam"></i>
            <h3>Supply</h3>
            <p>Manage inventory efficiently with our Supply module.</p>
          </a>
        </div>
        <!-- Security Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-security" class="module-card">
            <i class="bi bi-shield-lock"></i>
            <h3>Security</h3>
            <p>Track incidents and logs to ensure campus safety.</p>
          </a>
        </div>
        <!-- Library Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-library" class="module-card">
            <i class="bi bi-book"></i>
            <h3>Library</h3>
            <p>Handle book borrowing and returns seamlessly.</p>
          </a>
        </div>
        <!-- Cashier Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-cashier" class="module-card">
            <i class="bi bi-cash-stack"></i>
            <h3>Cashier</h3>
            <p>Streamline uniform procurement for PE, ID, sling, and more.</p>
          </a>
        </div>
        <!-- Admission and Scholarship Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-admission" class="module-card">
            <i class="bi bi-mortarboard"></i>
            <h3>Admission & Scholarship</h3>
            <p>Simplify admissions and scholarship processes.</p>
          </a>
        </div>
        <!-- Canteen Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-canteen" class="module-card">
            <i class="bi bi-cup-straw"></i>
            <h3>Canteen</h3>
            <p>Manage canteen operations with ease.</p>
          </a>
        </div>
        <!-- Clinic Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-clinic" class="module-card">
            <i class="bi bi-heart-pulse"></i>
            <h3>Clinic</h3>
            <p>Keep track of health services and student records.</p>
          </a>
        </div>
        <!-- Alumni Office Module -->
        <div class="col-lg-3 col-md-6 col-sm-12">
          <a href="#module-alumni" class="module-card">
            <i class="bi bi-people"></i>
            <h3>Alumni Office</h3>
            <p>Stay connected with alumni through this module.</p>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-md-4 text-center text-md-start mb-3">
          <div class="footer-logo mx-auto mx-md-0">UMS</div>
          <p class="mt-2">University Management System</p>
        </div>
        <div class="col-md-4 text-center mb-3">
          <h5>Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="#">About Us</a></li>
            <li><a href="#">Support</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="col-md-4 text-center text-md-end mb-3">
          <h5>Contact Us</h5>
          <p>Email: support@ums.edu<br>Phone: +123-456-7890</p>
        </div>
      </div>
      <hr style="background-color: #FCFFF7;">
      <p class="text-center mb-0">© 2025 University Management System. All rights reserved.</p>
    </div>
  </footer>

  <!-- Bootstrap 5 JS and Popper.js -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>