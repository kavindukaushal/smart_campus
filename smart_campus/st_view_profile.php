<?php session_start(); 
include 'db_connect.php';

// Check if the student is logged in 
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id']; // Assuming student_id is stored in session after login

// Fetch student details (excluding password)
$student_query = "SELECT id, first_name, last_name, email, registered_course, batch_id, birth_date, gender, username FROM student WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Fetch course name
$course_query = "SELECT course_name FROM courses WHERE id = ?";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $student['registered_course']);
$stmt->execute();
$course_result = $stmt->get_result();
$course = $course_result->fetch_assoc();

// Fetch batch name
$batch_query = "SELECT batch_name FROM batch WHERE batch_id = ?";
$stmt = $conn->prepare($batch_query);
$stmt->bind_param("i", $student['batch_id']);
$stmt->execute();
$batch_result = $stmt->get_result();
$batch = $batch_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.3.2/dist/fullcalendar.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Custom styles for the profile page */
        :root {
            --primary-color:rgb(17, 15, 88);
            --secondary-color:rgb(55, 86, 177);
            --accent-color:rgb(20, 43, 109);
            --text-color: #5a5c69;
            --heading-color:rgb(4, 41, 152);
            --border-color:rgb(228, 240, 227);
            --card-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .profile-header h1 {
            color: #0F0E47;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .profile-header::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background-color: #0F0E47;
            margin: 10px auto;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .profile-banner {
            height: 120px;
            background: linear-gradient(135deg, var(--primary-color),rgb(6, 10, 53));
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            top: 60px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 5px solid white;
        }

        .profile-avatar i {
            font-size: 50px;
            color: var(--accent-color);
        }

        .profile-details {
            padding: 90px 30px 30px;
        }

        .profile-details h3 {
            color: #0F0E47;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.75rem;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background-color:rgb(177, 177, 219);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateY(-3px);
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
            display: block;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: #333;
        }

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


<div class="container">
    <div class="profile-header">
        <h1>Student Profile</h1>
    </div>
    
    <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-avatar">
            <i class="fas fa-user-graduate"></i>
        </div>
        
        <div class="profile-details">
            <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
            
            <div class="profile-info">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-user"></i> First Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['first_name']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-user"></i> Last Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['last_name']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-user-tag"></i> Username</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['username']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-venus-mars"></i> Gender</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['gender']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-birthday-cake"></i> Birth Date</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['birth_date']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-book"></i> Course</span>
                    <span class="info-value"><?php echo htmlspecialchars($course['course_name']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-users"></i> Batch</span>
                    <span class="info-value"><?php echo htmlspecialchars($batch['batch_name']); ?></span>
                </div>
            </div>
        </div>
    </div>
    

</div>

</body>
</html>