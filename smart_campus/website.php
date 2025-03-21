<?php
// logo.php (This is where the logo is dynamically generated)
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Campus</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: #0F0E47;
            color: white;
            padding: 15px 0;
        }

        .header img {
            width: 50px;
            margin-right: 15px;
        }

        .hero-section {
            background-image: url('https://via.placeholder.com/1500x800'); /* Example Image */
            background-size: cover;
            color: white;
            text-align: center;
            padding: 80px 20px;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: 600;
        }

        .hero-section p {
            font-size: 1.2rem;
        }

        .cta-btn {
            background-color: #505081;
            color: white;
            padding: 12px 30px;
            border: none;
            font-size: 1.1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .cta-btn:hover {
            background-color: #8686AC;
        }

        .content {
            padding: 40px 20px;
        }

        .footer {
            background: #272757;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="header d-flex justify-content-between align-items-center container">
        <div class="d-flex align-items-center">
            <img src="images/logo2.png" alt="Smart Campus Logo">
            <h1>Smart Campus</h1>
            <p class="ms-2">Management System</p>
        </div>
        <nav>
            <ul class="nav">
                <li class="nav-item">
                    <a href="login.php" class="nav-link text-white">Login</a>
                </li>
                <li class="nav-item">
                    <a href="register.php" class="nav-link text-white">Register</a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Welcome to the Smart Campus Management System</h1>
            <p>Manage your campus efficiently with ease, streamline operations and enhance collaboration.</p>
            <button class="cta-btn" onclick="openModal()">Learn More</button>
        </div>
    </section>

    <!-- Content Section -->
    <section class="content">
        <div class="container">
            <h2>Our Mission</h2>
            <p>The Smart Campus Management System is designed to help academic institutions streamline operations and improve communication between students, lecturers, and administrators. Our platform provides a unified interface to manage class schedules, events, resources, and more.</p>

            <h3>Key Features:</h3>
            <ul>
                <li>Efficient scheduling of classes and events</li>
                <li>Real-time resource booking and monitoring</li>
                <li>Communication and collaboration tools for staff and students</li>
            </ul>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2023 Smart Campus Management System. All rights reserved.</p>
    </footer>

    <!-- Modal Popup -->
    <div id="learnMoreModal" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">About the Smart Campus System</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>The Smart Campus Management System is designed to integrate various campus operations into a single platform. It aims to enhance resource utilization, improve collaboration, and provide real-time insights into campus activities.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>

    <!-- Custom JS for Modal -->
    <script>
        function openModal() {
            var modal = new bootstrap.Modal(document.getElementById('learnMoreModal'));
            modal.show();
        }
    </script>

</body>

</html>
