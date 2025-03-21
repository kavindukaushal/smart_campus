<?php 
session_start(); 
error_reporting(E_ALL); 
ini_set('display_errors', 1); 

// Database Connection 
$conn = new mysqli("localhost", "root", "", "smart_campus_db"); 
if ($conn->connect_error) {     
    die("Connection failed: " . $conn->connect_error); 
} 

$user_email = $_SESSION['email']; 

// Check if user is a student or lecturer 
$is_student = false;
$is_lecturer = false;
$user_role = "";

$student_query = $conn->prepare("SELECT id FROM student WHERE email = ?"); 
$student_query->bind_param("s", $user_email); 
$student_query->execute(); 
$student_result = $student_query->get_result(); 
if ($student_result->num_rows > 0) {     
    $is_student = true;     
    $user_role = "student"; 
} else {
    $lecturer_query = $conn->prepare("SELECT id FROM lecturer WHERE email = ?"); 
    $lecturer_query->bind_param("s", $user_email); 
    $lecturer_query->execute(); 
    $lecturer_result = $lecturer_query->get_result(); 
    if ($lecturer_result->num_rows > 0) {     
        $is_lecturer = true;     
        $user_role = "lecturer"; 
    }
}

// Fetch messages for the logged-in user based on their role
// Using two checks: recipient_email and recipient_role to ensure proper filtering
$mail_query = $conn->prepare("SELECT sender_email, subject, message, sent_at FROM emails 
                             WHERE recipient_email = ? 
                             AND (recipient_role = ? OR recipient_role IS NULL)
                             ORDER BY sent_at DESC"); 
$mail_query->bind_param("ss", $user_email, $user_role); 
$mail_query->execute(); 
$mails = $mail_query->get_result();  

// Close statements and connection
if(isset($student_query)) $student_query->close(); 
if(isset($lecturer_query)) $lecturer_query->close(); 
$mail_query->close(); 
$conn->close(); 
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Inbox - Messages</title>     
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">     
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     
    <style>         
        body {             
            background-color: #f8f9fa;         
        }         
        .inbox-container {             
            max-width: 800px;             
            margin: auto;             
            margin-top: 50px;         
        }         
        .message-card {             
            transition: all 0.3s ease-in-out;         
        }         
        .message-card:hover {             
            transform: scale(1.02);             
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);         
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
            <li>
                <a href="index.php">
                    <div class="button-container">
                        <button onclick="window.location.href='st_dash.php'">
                            <i class="fas fa-arrow-left"></i> Log out
                        </button>
                    </div>
                </a>
            </li>
            <li>
                <a href="#" id="backToDashboardLink">
                    <div class="button-container">
                        <button>
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </button>
                    </div>
                </a>
            </li>
        </ul>
    </nav>
</header>

    <br>
    



<div class="container inbox-container">     
    <h2 class="text-center text-primary mb-4">📩 Your Inbox</h2>     
    <?php if ($is_student): ?>         
        <p class="text-center text-success">Welcome, Student! Here are your messages:</p>     
    <?php elseif ($is_lecturer): ?>         
        <p class="text-center text-warning">Welcome, Lecturer! Here are your messages:</p>     
    <?php endif; ?>     
    
    <?php if ($mails->num_rows > 0): ?>         
        <?php while ($mail = $mails->fetch_assoc()): ?>             
            <div class="card message-card shadow-sm p-3 mb-3" data-aos="fade-up">                 
                <div class="card-body">                     
                    <h5 class="card-title text-primary"><?= htmlspecialchars($mail['subject']) ?></h5>                     
                    <p class="card-text"><?= nl2br(htmlspecialchars($mail['message'])) ?></p>                     
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">From: <?= htmlspecialchars($mail['sender_email']) ?></small>
                        <small class="text-muted">Received: <?= $mail['sent_at'] ?></small>
                    </div>
                </div>             
            </div>         
        <?php endwhile; ?>     
    <?php else: ?>         
        <p class="text-center text-muted">No messages found for your account.</p>     
    <?php endif; ?> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script> 
<script>     
    AOS.init(); 
</script> 
<script>
    // Get the referrer page (from where the user came)
    const referrer = document.referrer;

    // Select the 'Back to Dashboard' link
    const backToDashboardLink = document.getElementById('backToDashboardLink');

    // Check if the user came from the lecture dashboard or student dashboard and update the link accordingly
    if (referrer.includes('lec_dash.php')) {
        backToDashboardLink.href = 'lec_dash.php';  // Redirect to the lecture dashboard
    } else if (referrer.includes('st_dash.php')) {
        backToDashboardLink.href = 'st_dash.php';  // Redirect to the student dashboard
    } else {
        backToDashboardLink.href = 'index.php';  // Default fallback if no referrer or unexpected page
    }
</script>  

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