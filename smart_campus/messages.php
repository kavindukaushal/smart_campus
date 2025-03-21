<?php
session_start();
include 'db_connect.php';

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch all lecturers (even those without messages)
$lecturers = mysqli_query($conn, "
    SELECT id, first_name, last_name 
    FROM lecturer
    ORDER BY first_name, last_name
");

// Handle student messages
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message_text'])) {
    $lecturer_id = $_POST['lecturer_id'];
    $message_text = mysqli_real_escape_string($conn, $_POST['message_text']);

    $query = "INSERT INTO chat_messages (student_id, lecturer_id, message_text, sender_type) 
              VALUES ('$student_id', '$lecturer_id', '$message_text', 'student')";

    if (mysqli_query($conn, $query)) {
        header("Location: messages.php?lecturer_id=$lecturer_id");
        exit();
    } else {
        echo "<script>alert('Error sending message.');</script>";
    }
}

// Function to check for unread messages
function getUnreadMessageCounts($conn, $student_id) {
    $unread_counts = [];
    
    $query = "
        SELECT lecturer_id, COUNT(*) as count 
        FROM chat_messages 
        WHERE student_id = '$student_id' 
        AND sender_type = 'lecturer' 
        AND (is_read = 0 OR is_read IS NULL)
        GROUP BY lecturer_id
    ";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $unread_counts[$row['lecturer_id']] = $row['count'];
        }
    }
    
    return $unread_counts;
}

// Get unread message counts
$unread_counts = getUnreadMessageCounts($conn, $student_id);

// Fetch messages for a specific lecturer if selected
$selected_lecturer_id = isset($_GET['lecturer_id']) ? $_GET['lecturer_id'] : null;
$messages = [];
if ($selected_lecturer_id) {
    // Mark messages as read when viewing a conversation
    $mark_read_query = "
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE student_id = '$student_id' 
        AND lecturer_id = '$selected_lecturer_id' 
        AND sender_type = 'lecturer'
        AND (is_read = 0 OR is_read IS NULL)
    ";
    mysqli_query($conn, $mark_read_query);
    
    // Get messages
    $messages = mysqli_query($conn, "
        SELECT m.message_text, m.sent_at, m.sender_type, l.first_name, l.last_name 
        FROM chat_messages m
        JOIN lecturer l ON m.lecturer_id = l.id
        WHERE m.student_id = '$student_id' AND m.lecturer_id = '$selected_lecturer_id'
        ORDER BY m.sent_at ASC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Messaging</title>
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
            flex-direction: column;
            min-height: 100vh;
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
        }

        /* Lecturer List Styling */
        .lecturer-list ul {
            list-style: none;
            padding: 0;
            margin-bottom: 15px;
        }

        .lecturer-list li {
            padding: 8px 12px;
            margin: 5px 0;
            background-color: #ddd;
            cursor: pointer;
            border-radius: 5px;
            text-align: center;
            transition: 0.3s ease;
        }

        .lecturer-list li:hover {
            background-color: #bbb;
        }

        /* Chat Box Styling */
        .message-history {
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
            text-align: center;
            color: #333;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
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
        
        /* Make lecturer names with notifications stand out */
        .lecturer-list li a:has(.notification-badge) {
            font-weight: bold;
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
                <li><a href="st_dash.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Send a Message to Your Lecturer</h2>

        <!-- Display all lecturers -->
        <div class="lecturer-list">
            <h3>Lecturers</h3>
            <ul>
                <?php while ($lecturer = mysqli_fetch_assoc($lecturers)): ?>
                    <li>
                        <a href="?lecturer_id=<?= $lecturer['id']; ?>">
                            <?= $lecturer['first_name'] . " " . $lecturer['last_name']; ?>
                            <?php if (isset($unread_counts[$lecturer['id']]) && $unread_counts[$lecturer['id']] > 0): ?>
                                <span class="notification-badge"><?= $unread_counts[$lecturer['id']]; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Chat Box -->
        <div class="message-history">
            <?php if ($selected_lecturer_id): ?>
                <h3>Chat with Lecturer</h3>

                <!-- Show messages -->
                <?php if (mysqli_num_rows($messages) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($messages)): ?>
                        <div class="message-box <?= $row['sender_type'] == 'student' ? 'sent' : 'received'; ?>">
                            <p><strong><?= $row['sender_type'] == 'student' ? 'You' : $row['first_name'] . " " . $row['last_name']; ?>:</strong></p>
                            <p><?= $row['message_text']; ?></p>
                            <p><small>Sent on: <?= $row['sent_at']; ?></small></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No messages yet. Start a conversation!</p>
                <?php endif; ?>

                <!-- Message Form -->
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="lecturer_id" value="<?= $selected_lecturer_id; ?>">
                    <textarea name="message_text" placeholder="Type your message..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php else: ?>
                <p>Select a lecturer to start a conversation.</p>
            <?php endif; ?>
        </div>

        <a href="st_dash.php">Back to Dashboard</a>
    </div>

    <script>
    function checkNewMessages() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'check_messages.php', true);
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    
                    // Update notification badges
                    const lecturerLinks = document.querySelectorAll('.lecturer-list li a');
                    lecturerLinks.forEach(link => {
                        if (!link.href.includes('lecturer_id=')) return;
                        
                        const lecturerId = link.href.split('lecturer_id=')[1];
                        const existingBadge = link.querySelector('.notification-badge');
                        
                        if (response[lecturerId] && response[lecturerId] > 0) {
                            if (existingBadge) {
                                existingBadge.textContent = response[lecturerId];
                            } else {
                                const badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                badge.textContent = response[lecturerId];
                                link.appendChild(badge);
                            }
                        } else if (existingBadge) {
                            existingBadge.remove();
                        }
                    });
                    
                    // If new messages and currently viewing that lecturer, refresh the messages
                    const currentLecturerId = new URLSearchParams(window.location.search).get('lecturer_id');
                    if (currentLecturerId && response[currentLecturerId] && response[currentLecturerId] > 0) {
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