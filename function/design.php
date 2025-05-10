<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <style>

        body {
    background-color: #f5f7fb;
    font-family: 'Poppins', sans-serif;
}

              :root {
    --primary: rgb(2, 22, 67);
    --secondary: #6c757d;
    --success: #198754;
    --danger: #dc3545;
    --warning: #ffc107;
    --info: #0dcaf0;
    --light: #f8f9fa;
    --dark: #212529;
    --bs-body-font-family: 'Poppins', sans-serif;
}


.navbar {
    background-color: var(--primary);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 0.8rem 1rem;
}

.navbar-brand {
    color: white;
    font-weight: 600;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
}

.navbar-brand i {
    margin-right: 10px;
    font-size: 1.8rem;
}

.navbar-brand:hover {
    color: rgba(255, 255, 255, 0.9);
}

.nav-link {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
    margin: 0 5px;
    padding: 10px 15px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
    color: white;
    background-color: rgba(255, 255, 255, 0.2);
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 25px;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1.25rem 1.5rem;
    font-weight: 600;
    color: var(--dark);
    border-radius: 10px 10px 0 0 !important;
}

.card-body {
    padding: 1.5rem;
}

.form-control {
    border-radius: 8px;
    padding: 0.6rem 1rem;
    border: 1px solid #e0e6ed;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary);
    background-color: white;
    box-shadow: 0 0 0 0.2rem rgba(2, 22, 67, 0.15);
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.btn {
    border-radius: 8px;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
}

.btn-primary:hover, .btn-primary:focus {
    background-color: rgb(1, 18, 54);
    border-color: rgb(1, 18, 54);
}

.btn-success {
    background-color: var(--success);
    border-color: var(--success);
}

.btn-success:hover, .btn-success:focus {
    background-color: #157347;
    border-color: #157347;
}

.btn-outline-light {
    color: var(--primary);
    border-color: #d1d9e6;
    background-color: white;
}

.btn-outline-light:hover, .btn-outline-light.active {
    color: white;
    background-color: var(--primary);
    border-color: var(--primary);
}

.badge {
    padding: 0.5rem 0.8rem;
    font-weight: 500;
    border-radius: 6px;
}

.badge.bg-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
    color: var(--success);
}

.badge.bg-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
    color: var(--danger);
}

.badge.bg-info {
    background-color: rgba(13, 202, 240, 0.1) !important;
    color: rgb(2, 22, 67);
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: var(--dark);
    background-color: #f9fafb;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

.table tr:hover {
    background-color: rgba(2, 22, 67, 0.02);
}

.table-responsive {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.input-group {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
}

.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.alert {
    border-radius: 8px;
    border: none;
}

.alert-success {
    background-color: rgba(25, 135, 84, 0.1);
    color: var(--success);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger);
}

.alert-info {
    background-color: rgba(13, 202, 240, 0.1);
    color: #0aa2c0;
}

.footer {
    background-color: #f8f9fa;
    padding: 1.5rem 0;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    font-size: 0.9rem;
    color: var(--secondary);
}

.contact-icons a {
    text-decoration: none;
}

.contact-icons i {
    width: 25px;
}

.stats-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 10px;
    color: white;
    margin-bottom: 20px;
}

.stats-card .icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.stats-card .count {
    font-size: 2rem;
    font-weight: 600;
}

.stats-card .title {
    font-size: 1rem;
    opacity: 0.8;
}

.stats-total {
    background-color: var(--primary);
}

.stats-available {
    background-color: var(--success);
}

.stats-borrowed {
    background-color: var(--danger);
}

.stats-overdue {
    background-color: var(--warning);
    color: #212529;
}

.tab-content {
    padding: 20px 0;
}

.nav-tabs {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.nav-tabs .nav-link {
    color: var(--secondary);
    background-color: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    font-weight: 500;
    padding: 12px 20px;
    margin-right: 5px;
}

.nav-tabs .nav-link:hover {
    color: var(--primary);
    border-color: transparent;
}

.nav-tabs .nav-link.active {
    color: var(--primary);
    border-bottom: 2px solid var(--primary);
    background-color: transparent;
}

.book-status-available {
    background-color: rgba(25, 135, 84, 0.05);
}

.book-status-unavailable {
    background-color: rgba(220, 53, 69, 0.05);
}

.overdue-highlight {
    background-color: rgba(255, 193, 7, 0.1);
}

.quick-actions {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.floating-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px
} 
</style>
    