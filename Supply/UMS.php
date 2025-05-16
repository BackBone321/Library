<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Institution Landing Page</title>
    <link rel="icon" href="img/SIMS4_EP_C_CampusLife.webp">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .navbar-brand span {
            font-size: 1.5rem;
            font-weight: 500;
            color: #333;
        }
        .nav-item {
            margin-right: 10px;
        }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1') no-repeat center center/cover;
            height: 100vh;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .section {
            padding: 60px 0;
        }
        .section h2 {
            margin-bottom: 30px;
            font-weight: 600;
        }
        .about-img {
            max-width: 80%;
            height: auto;
            border-radius: 8px;
        }
        .about-text {
            display: flex;
            align-items: center;
        }
        @media (max-width: 768px) {
            .hero-section {
                height: 60vh;
            }
            .hero-section h1 {
                font-size: 2rem;
            }
            .nav-item {
                margin-right: 0;
                margin-bottom: 10px;
            }
            .navbar-brand {
                flex-direction: row;
                align-items: center;
            }
            .navbar-brand img {
                height: 30px;
            }
            .navbar-brand span {
                font-size: 1.2rem;
            }
            .about-text {
                text-align: center;
                margin-bottom: 20px;
            }
        }
        .col{
            margin-left: 150px;
        }
        .container hr{
            border-bottom: 5px solid ;
            width: 10%;
            border-radius: 6px;
            background-color: #0b1215;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <img src="img/SIMS4_EP_C_CampusLife.webp" alt="University System">
                <span>University Management System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#admission">Admission & Scholarship</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="forms.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Home Section -->
    <section id="home" class="hero-section">
        <div>
            <h1>Welcome to Our Institution</h1>
            <p class="lead">Empowering the future through education and innovation.</p>
            <a href="" class="btn btn-light btn-lg mt-3">Apply Now</a>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
        <div class="container">
            <h1>About Us</h1><hr>
            <div class="row align-items-center">
                <div class="col-12 col-md-6 about-text">
                    <p>The University of Science and Technology of the Philippines (USTP) is a premier institution dedicated to advancing knowledge, innovation, and societal progress through cutting-edge education and research. Rooted in a commitment to excellence, USTP fosters a dynamic learning environment that empowers students to become forward-thinking leaders and problem-solvers in a rapidly evolving world.</p>
                </div>
                <div class="col">
                    <img src="img/SIMS4_EP_C_CampusLife.webp" class="about-img" alt="Campus">
                </div>
            </div>
        </div>
    </section>

    <!-- Admission & Scholarship Section -->
    <section id="admission" class="section bg-light">
        <div class="container">
            <h2>Admission & Scholarship</h2>
            <div class="row">
                <div class="col-12 col-md-6">
                    <h4>Admission Process</h4>
                    <p>Join our institution by following our streamlined admission process. Submit your application, attend an interview, and start your journey with us.</p>
                </div>
                <div class="col-12 col-md-6">
                    <h4>Scholarships</h4>
                    <p>We offer a variety of scholarships to support deserving students. Explore our merit-based and need-based scholarship programs today.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap 5 JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>