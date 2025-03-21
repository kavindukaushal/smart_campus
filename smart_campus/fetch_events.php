<?php
include 'db_connect.php';

$event_query = "SELECT events.*, courses.course_name FROM events 
                JOIN courses ON events.course_id = courses.id";
$event_result = $conn->query($event_query);

$events_array = [];
while ($row = $event_result->fetch_assoc()) {
    $events_array[] = [
        "id" => $row['id'],
        "title" => $row['title'],
        "start" => $row['event_date'] . "T" . $row['start_time'],
        "end" => $row['event_date'] . "T" . $row['end_time'],
        "description" => $row['description'],
        "venue" => $row['venue'],
        "course" => $row['course_name']
    ];
}

header('Content-Type: application/json');
echo json_encode($events_array);
?>