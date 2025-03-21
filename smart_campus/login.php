<?php
session_start();
include 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type']; // Admin, Student, or Lecturer

    // Define query and table based on user type
    if ($user_type == 'admin') {
        $table = 'users';
        $redirect_page = 'dashboard.php';
    } elseif ($user_type == 'student') {
        $table = 'student';
        $redirect_page = 'st_dash.php';
    } elseif ($user_type == 'lecturer') {
        $table = 'lecturer';
        $redirect_page = 'lec_dash.php';
    } else {
        $error = "Invalid user type selected.";
    }

    // Query to check if the user exists
    $sql = "SELECT * FROM $table WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check password
        if (md5($password) === $user['password_hash']) {  // Keep md5 if needed
            $_SESSION[$user_type . '_logged_in'] = true;
            $_SESSION[$user_type . '_id'] = $user['id'];
            $_SESSION[$user_type . '_username'] = $user['username'];
            $_SESSION[$user_type . '_role'] = isset($user['role']) ? $user['role'] : null;

            // ✅ Store email in session
            $_SESSION['email'] = $user['email'];

            // Redirect to respective dashboard
            header("Location: $redirect_page");
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <!-- Left Side with Logo -->
        <div class="login-left">
            <img src="images/logo.png" alt="Smart Campus Logo">
        </div>

        <!-- Right Side with Login Form -->
        <div class="login-right">
            <h2>User Login</h2>
            
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

            <form method="POST" onsubmit="return validateForm()">
                <label for="user_type">Select User Type:</label>
                <select name="user_type" id="user_type" required>
                    <option value="admin">Admin</option>
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                </select>

                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <span class="toggle-password" onclick="togglePassword()">👁</span>
                </div>

                <button type="submit">Login</button>
            </form>

            <p class="forgot-password"><a href="#">Forgot Password?</a></p>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
