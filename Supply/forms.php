<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login/sign in</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Boxicons CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        .bg-container {
            background-image: url('USTP.jpg');
            height: 100vh;
            background-position: center;
            background-size: cover;
            position: relative;
            overflow: hidden;
        }
        
        .bg-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(22, 2, 76, 0.7);
            backdrop-filter: blur(10px);
            z-index: 1;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
            padding: 2rem;
        }
        
        .card h4 {
            color: rgb(22, 2, 76);
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, rgb(22, 2, 76) 0%, rgb(55, 10, 150) 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(22, 2, 76, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(22, 2, 76, 0.5);
            background: linear-gradient(135deg, rgb(35, 4, 120) 0%, rgb(75, 20, 190) 100%);
        }
        
        .form-control, .input-group-text {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }
        
        .input-group-text {
            background-color: rgb(242, 242, 255);
            border-right: none;
            color: rgb(22, 2, 76);
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .form-control:focus {
            border-color: rgb(22, 2, 76);
            box-shadow: 0 0 0 0.25rem rgba(22, 2, 76, 0.25);
        }
        
        .form-check-input:checked {
            background-color: rgb(22, 2, 76);
            border-color: rgb(22, 2, 76);
        }
        
        .text-primary {
            color: rgb(55, 10, 150) !important;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .text-primary:hover {
            color: rgb(85, 35, 200) !important;
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo img {
            height: 60px;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.3rem;
        }

        .card-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .shape1 {
            top: -25px;
            right: -25px;
            width: 100px;
            height: 100px;
        }

        .shape2 {
            bottom: -20px;
            left: -20px;
            width: 80px;
            height: 80px;
        }
    </style>
</head>
<body>
    <div class="bg-container">
        <div class="content-wrapper">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-5">
                        <div class="card position-relative">
                            <div class="card-shape shape1"></div>
                            <div class="card-shape shape2"></div>
                            
                            <div class="logo">
                                <!-- Replace with your actual logo -->
                                <div style="font-size: 32px; font-weight: 800; color: rgb(22, 2, 76);">
                                    <i class='bx bx-lock-open-alt'></i> SecureApp
                                </div>
                            </div>
                            
                            <div class="form-container active" id="login-form">
                                <form action="supply_user.php">
                                    <h4 class="text-center mb-4">LOGIN</h4>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bxs-envelope'></i></span>
                                            <input type="email" class="form-control" name="email" placeholder="Username..." required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class='bx bxs-lock-alt'></i></span>
                                            <input type="password" class="form-control" name="password" placeholder="Password..." required>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                            <label class="form-check-label" for="remember">Remember Me</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">LOGIN</button>
                                </form>
                            </div>
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>