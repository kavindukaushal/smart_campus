<?php
session_start();
include 'db_connect.php';

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$student_id = $_SESSION['student_id'];

// Get unread message counts
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
        $unread_counts[$row['lecturer_id']] = (int)$row['count'];
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($unread_counts);
?>