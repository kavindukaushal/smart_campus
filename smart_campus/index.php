<?php
// index.php - Landing page for the Smart Campus Management System
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Campus Management System</title>
    
    <!-- Link to external CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Modern Google Font for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            height: 100vh;
            background-size: cover;
            animation: changeBackground 10s infinite;
            background-image: url('images/03054(5).gif');
            
           
        }
    </style>
</head>
<body>


    <!-- Main Wrapper -->
    <div class="container">
        <!-- Header -->
        <header>
    <div class="logo">
        <img src="images/logo2.png" alt="Smart Campus Logo">
        <div class="text">
            <h1>Smart Campus</h1>
            <p>Management System</p>
        </div>
    </div>
    <nav>
        <ul>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        </ul>
    </nav>
</header>


       <!-- Main Content Section -->
<section class="main-content">
    <div class="transparent-box">
        <div class="hero">
            <h2>Welcome to the Smart Campus Management System</h2>
            <p>Manage your campus efficiently with ease.</p>
            <button class="website.php" onclick="openModal()">Learn More</button>
        </div>
    </div>
</section>
        <!-- Modal Popup for 'Learn More' -->
        <div id="modal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <h2>About Our System</h2>
                <p>The Smart Campus Management System is designed to help academic institutions manage resources, schedules, courses, and student participation effectively.</p>
            </div>
        </div>
    </div>

    <!-- Link to external JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>