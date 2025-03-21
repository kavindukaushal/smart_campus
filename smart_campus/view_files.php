<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - View Files</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

</head>
<body>
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

                <li><a href="index.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Log out
        </button>
    </div></a></li>
                <li><a href="st_dash.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>






<!-- Container for the content -->
<div class="container mt-4">
    <!-- Your PHP code will be inserted here -->
  <?php
session_start();
include('db_connect.php');  // Database connection

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    echo "<div class='alert alert-danger' role='alert'>You need to log in first.</div>";
    exit;
}

// Get the student ID from session
$student_id = $_SESSION['student_id'];  // Assuming the student ID is stored in the session

// Query to get all courses the student is enrolled in from student_courses table
$sql_courses = "SELECT c.id AS course_id, c.course_name 
                FROM courses c 
                JOIN student_courses sc ON sc.course_id = c.id 
                WHERE sc.student_id = ?";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();

// Check if student is enrolled in any courses
if ($courses_result->num_rows > 0) {
    echo "<h2 class='text-success'>Your Enrolled Courses</h2>";
    echo "<div class='list-group'>";

    // Loop through each course the student is enrolled in
    while ($course = $courses_result->fetch_assoc()) {
        $course_id = $course['course_id'];
        $course_name = $course['course_name'];

        echo "<div class='list-group-item'>
                <h4 class='list-group-item-heading'>$course_name</h4>
                <ul class='list-group'>";

        // Query to get uploaded files for the specific course
        $sql_files = "SELECT file_name, file_path FROM uploads WHERE course_id = ?";
        $stmt_files = $conn->prepare($sql_files);
        $stmt_files->bind_param("i", $course_id);
        $stmt_files->execute();
        $files_result = $stmt_files->get_result();

        // Display the files for the course
        if ($files_result->num_rows > 0) {
            while ($file = $files_result->fetch_assoc()) {
                echo "<li class='list-group-item'>
                        <a href='" . $file['file_path'] . "' download class='btn btn-link'>
                            " . $file['file_name'] . "
                        </a>
                      </li>";
            }
        } else {
            echo "<li class='list-group-item text-muted'>No files available for this course.</li>";
        }

        echo "</ul></div>";
    }
    echo "</div>";
} else {
    echo "<div class='alert alert-warning' role='alert'>You are not enrolled in any courses.</div>";
}
?>

</div>

<!-- Bootstrap JS and dependencies (jQuery, Popper) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<style>
    
    .button-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .button-container button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .button-container button:hover {
            background-color: #3a5fd9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78,115,223,0.4);
        }

        .button-container button i {
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .profile-banner {
                height: 100px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                top: 50px;
            }
            
            .profile-details {
                padding-top: 70px;
            }
        }
 </style>

</body>
</html>
