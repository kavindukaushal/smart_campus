<?php
session_start();
include 'db_connect.php';

// Ensure lecturer is logged in
if (!isset($_SESSION['lecturer_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$lecturer_id = $_SESSION['lecturer_id'];

// Get unread message counts
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
        $unread_counts[$row['student_id']] = (int)$row['count'];
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($unread_counts);
?>