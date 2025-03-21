<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipient = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $sender_email = "admin@smartcampus.com"; // Admin sender email

    // Connect to the database
    $conn = new mysqli("localhost", "root", "", "smart_campus_db");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Determine recipient role
    $recipient_role = "";
    $student_check = null;
    $lecturer_check = null;
    
    // Check if the email belongs to a student
    $student_check = $conn->prepare("SELECT id FROM student WHERE email = ?");
    $student_check->bind_param("s", $recipient);
    $student_check->execute();
    $student_result = $student_check->get_result();
    
    if ($student_result->num_rows > 0) {
        $recipient_role = "student";
    } else {
        // Check if the email belongs to a lecturer
        $lecturer_check = $conn->prepare("SELECT id FROM lecturer WHERE email = ?");
        $lecturer_check->bind_param("s", $recipient);
        $lecturer_check->execute();
        $lecturer_result = $lecturer_check->get_result();
        
        if ($lecturer_result->num_rows > 0) {
            $recipient_role = "lecturer";
        }
    }
    
    // Close the prepared statements safely
    if ($student_check) {
        $student_check->close();
    }
    
    if ($lecturer_check) {
        $lecturer_check->close();
    }

    // Only proceed if we found a valid recipient role
    if (!empty($recipient_role)) {
        // Prepare and execute the insert query with recipient_role
        $stmt = $conn->prepare("INSERT INTO emails (recipient_email, sender_email, subject, message, recipient_role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $recipient, $sender_email, $subject, $message, $recipient_role);

        if ($stmt->execute()) {
            // Close statement and connection
            $stmt->close();
            $conn->close();

            // SweetAlert success popup
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Message sent to the " . ucfirst($recipient_role) . "',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                });
            </script>";
        } else {
            // Close statement and connection
            $stmt->close();
            $conn->close();

            // SweetAlert error popup
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to send message. Error: " . $conn->error . "',
                        icon: 'error',
                        confirmButtonText: 'Try Again'
                    }).then(() => {
                        window.history.back();
                    });
                });
            </script>";
        }
    } else {
        // No valid recipient found
        $conn->close();
        
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Invalid recipient email. Please check the email address.',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                }).then(() => {
                    window.history.back();
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: auto;
        }
        .mail-container {
            max-width: 900px;
            max-height: 100PX;        margin: 50px auto;
        }
        .preview-area {
            transition: all 0.3s ease;
            min-height: 200px;
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
                <li><a href="dashboard.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>


    <div class="container mail-container">
        <div class="card shadow-lg p-4">
            <h2 class="text-center text-primary mb-4">📧 Send Message</h2>
            
            <form method="POST" action="send_mail.php" oninput="updatePreview()">
                <div class="mb-3">
                    <label for="email" class="form-label">Recipient Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject:</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Message:</label>
                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                </div>

                <!-- Message Preview Section -->
                <div class="mt-4 p-3 border rounded bg-light preview-area">
                    <h5 class="text-secondary">Message Preview:</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span><strong>To:</strong> <span id="previewEmail" class="text-muted"></span></span>
                        <span><strong>From:</strong> admin@smartcampus.com</span>
                    </div>
                    <p><strong>Subject:</strong> <span id="previewSubject" class="text-primary"></span></p>
                    <div class="mt-3">
                        <strong>Message:</strong>
                        <div id="previewMessage" class="p-2 mt-2 border-top"></div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update preview as user types
        function updatePreview() {
            document.getElementById("previewEmail").textContent = document.getElementById("email").value;
            document.getElementById("previewSubject").textContent = document.getElementById("subject").value;
            
            // Handle line breaks for message preview
            const messageText = document.getElementById("message").value;
            document.getElementById("previewMessage").innerHTML = messageText.replace(/\n/g, '<br>');
        }
        
        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
</body>
</html>