<?php
include 'db_connect.php';

// Check if the request is coming from the admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all pending reservation requests
$query = "SELECT rr.id, s.first_name, s.last_name, r.resource_name, rr.start_time, rr.end_time, rr.status 
          FROM reservation_requests rr 
          JOIN student s ON rr.student_id = s.id
          JOIN resources r ON rr.resource_id = r.id
          WHERE rr.status = 'pending'";

$result = $conn->query($query);

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = [
        'id' => $row['id'],
        'student' => $row['first_name'] . ' ' . $row['last_name'],
        'resource' => $row['resource_name'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'status' => $row['status']
    ];
}

echo json_encode($reservations);
?>
