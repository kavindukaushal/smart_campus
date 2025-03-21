<?php
session_start();
include 'db_connect.php';

// Ensure lecturer is logged in
if (!isset($_SESSION['lecturer_id'])) {
    header("Location: ../login.php");
    exit();
}

$lecturer_id = $_SESSION['lecturer_id'];

// Function to check for unread messages
function getUnreadMessageCounts($conn, $lecturer_id) {
    $unread_counts = [];
    
    $query = "
        SELECT student_id, COUNT(*) as count 
        FROM chat_messages 
        WHERE lecturer_id = '$lecturer_id' 
        AND sender_type = 'student' 
        AND (is_read = 0 OR is_read IS NULL)
        GROUP BY student_id
    ";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $unread_counts[$row['student_id']] = $row['count'];
        }
    }
    
    return $unread_counts;
}

// Get unread message counts
$unread_counts = getUnreadMessageCounts($conn, $lecturer_id);

// ✅ Fetch *all* students (even those without messages)
$students = mysqli_query($conn, "
    SELECT id, first_name, last_name 
    FROM student 
    ORDER BY first_name, last_name
");

// ✅ Handle lecturer replies
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_text'])) {
    $student_id = $_POST['student_id'];
    $reply_text = mysqli_real_escape_string($conn, $_POST['reply_text']);

    $reply_query = "INSERT INTO chat_messages (student_id, lecturer_id, message_text, sender_type) 
                    VALUES ('$student_id', '$lecturer_id', '$reply_text', 'lecturer')";

    if (mysqli_query($conn, $reply_query)) {
        header("Location: lecturer_messages.php?student_id=$student_id");
        exit();
    } else {
        // You can either remove this alert or keep it for error reporting
        echo "<script>alert('Failed to send reply.');</script>";
    }   
}

// ✅ Fetch messages for a specific student if selected
$selected_student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$messages = [];
if ($selected_student_id) {
    // Mark messages as read when viewing a conversation
    $mark_read_query = "
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE lecturer_id = '$lecturer_id' 
        AND student_id = '$selected_student_id' 
        AND sender_type = 'student'
        AND (is_read = 0 OR is_read IS NULL)
    ";
    mysqli_query($conn, $mark_read_query);

    $messages = mysqli_query($conn, "
        SELECT m.message_text, m.sent_at, m.sender_type, s.first_name, s.last_name 
        FROM chat_messages m
        JOIN student s ON m.student_id = s.id
        WHERE m.lecturer_id = '$lecturer_id' AND m.student_id = '$selected_student_id'
        ORDER BY m.sent_at ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lecturer Messaging</title>
    <style>
        /* Chat Page Styling */
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column; 
        }

        h2 {
            color: #333;
        }

        .container {
            width: 80%;
            max-width: 800px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
            display: flex;
            top: 0%;
        }

        /* Student List Styling */
        .student-list {
            width: 30%;
            padding-right: 10px;
            border-right: 1px solid #ddd;
        }

        .student-list h3 {
            margin-bottom: 10px;
        }

        .student-list ul {
            list-style: none;
            padding: 0;
            margin-bottom: 15px;
        }

        .student-list li {
            padding: 8px 12px;
            margin: 5px 0;
            background-color: #ddd;
            cursor: pointer;
            border-radius: 5px;
            text-align: center;
            transition: 0.3s ease;
        }

        .student-list li:hover {
            background-color: #bbb;
        }

        /* Notification Badge Styling */
        .notification-badge {
            display: inline-block;
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        /* Make student names with notifications stand out */
        .student-list li a:has(.notification-badge) {
            font-weight: bold;
        }

        /* Chat Box Styling */
        .chat-box {
            width: 70%;
            padding-left: 15px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: #3498db;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .message-history {
            flex-grow: 1;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            background: #f9f9f9;
            margin-bottom: 15px;
        }

        .message-box {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-size: 14px;
            width: fit-content;
            max-width: 70%;
            word-wrap: break-word;
        }

        .message-box.sent {
            background-color: #d1ecf1;
            text-align: right;
            margin-left: auto;
        }

        .message-box.received {
            background-color: #f8d7da;
            text-align: left;
        }

        .message-box p {
            margin: 5px 0;
        }

        /* Form Styling */
        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            resize: none;
        }

        button {
            padding: 10px 15px;
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            transition: 0.3s ease;
        }

        button:hover {
            background: #45a049;
        }

        a {
            display: block;
            margin-top: 15px;
            text-align: center;
            color: #333;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
        
/* Header Styling */
header {
    background: #0F0E47;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky; /* Makes the header stay at the top when scrolling */
    top: 0;
    width: 100%;
    z-index: 10;
}

header .logo {
    display: flex;
    align-items: center;
}

header .logo img {
    width: 50px;
    margin-right: 10px;
}

header .text h1 {
    font-size: 24px;
    margin: 0;
}

header nav ul {
    list-style: none;
    display: flex;
    margin: 0;
}

header nav ul li {
    margin-left: 20px;
}

header nav ul li a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    padding: 10px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

header nav ul li a:hover {
    background-color: #505081;
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
                <li><a href="lec_dash.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>
    <br>

    <div class="container">
        <!-- Student List Section -->
        <div class="student-list">
            <h3>Students</h3>
            <ul>
                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                    <li>
                        <a href="?student_id=<?= $student['id']; ?>">
                            <?= $student['first_name'] . " " . $student['last_name']; ?>
                            <?php if (isset($unread_counts[$student['id']]) && $unread_counts[$student['id']] > 0): ?>
                                <span class="notification-badge"><?= $unread_counts[$student['id']]; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Chat Box Section -->
        <div class="chat-box">
            <h2>Chat with Students</h2>

            <!-- Display Messages -->
            <div class="message-history">
                <?php if ($selected_student_id): ?>
                    <h3>Chat with Student</h3>

                    <!-- Show messages -->
                    <?php if (mysqli_num_rows($messages) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($messages)): ?>
                            <div class="message-box <?= $row['sender_type'] == 'lecturer' ? 'sent' : 'received'; ?>">
                                <p><strong><?= $row['sender_type'] == 'lecturer' ? 'You' : $row['first_name'] . " " . $row['last_name']; ?>:</strong></p>
                                <p><?= $row['message_text']; ?></p>
                                <p><small>Sent on: <?= $row['sent_at']; ?></small></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No messages yet. Start a conversation!</p>
                    <?php endif; ?>

                    <!-- Reply Form -->
                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="student_id" value="<?= $selected_student_id; ?>">
                        <textarea name="reply_text" placeholder="Write your reply..." required></textarea>
                        <button type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <p>Select a student to start a conversation.</p>
                <?php endif; ?>
            </div>

            <a href="lec_dash.php">Back to Dashboard</a>
        </div>
    </div>

    <script>
    function checkNewMessages() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'check_lecturer_messages.php', true);
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    
                    // Update notification badges
                    const studentLinks = document.querySelectorAll('.student-list li a');
                    studentLinks.forEach(link => {
                        if (!link.href.includes('student_id=')) return;
                        
                        const studentId = link.href.split('student_id=')[1];
                        const existingBadge = link.querySelector('.notification-badge');
                        
                        if (response[studentId] && response[studentId] > 0) {
                            if (existingBadge) {
                                existingBadge.textContent = response[studentId];
                            } else {
                                const badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                badge.textContent = response[studentId];
                                link.appendChild(badge);
                            }
                        } else if (existingBadge) {
                            existingBadge.remove();
                        }
                    });
                    
                    // If new messages and currently viewing that student, refresh the messages
                    const currentStudentId = new URLSearchParams(window.location.search).get('student_id');
                    if (currentStudentId && response[currentStudentId] && response[currentStudentId] > 0) {
                        location.reload();
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                }
            }
        };
        xhr.send();
    }

    // Check for new messages every 5 seconds
    setInterval(checkNewMessages, 5000);

    document.addEventListener("DOMContentLoaded", () => {
    const messageHistory = document.querySelector('.message-history');
    messageHistory.scrollTop = messageHistory.scrollHeight;
});
    </script>
</body>
</html>