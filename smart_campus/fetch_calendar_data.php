<?php
include 'db_connect.php';

// Fetch event details
$events = [];
$event_query = "SELECT id, title, event_date, start_time, end_time, venue FROM events";
$event_result = $conn->query($event_query);
while ($row = $event_result->fetch_assoc()) {
    $events[] = [
        'id' => 'event_' . $row['id'], 
        'title' => $row['title'],
        'start' => $row['event_date'],
        'backgroundColor' => '#FF5733',
        'extendedProps' => [
            'description' => "Time: " . $row['start_time'] . " - " . $row['end_time'] . "<br>Venue: " . $row['venue']
        ]
    ];
}

// Fetch class schedule details
$class_schedules = [];
$schedule_query = "SELECT s.id, s.course_id, s.resource_id, s.schedule_date, s.start_time, s.end_time, r.resource_name, c.course_name 
                   FROM class_schedules s
                   LEFT JOIN resources r ON s.resource_id = r.id
                   LEFT JOIN courses c ON s.course_id = c.id";
$schedule_result = $conn->query($schedule_query);
while ($row = $schedule_result->fetch_assoc()) {
    $class_schedules[] = [
        'id' => 'class_' . $row['id'], 
        'title' => $row['course_name'],
        'start' => $row['schedule_date'],
        'backgroundColor' => '#3498DB',
        'extendedProps' => [
            'description' => "Resource: " . $row['resource_name']
        ]
    ];
}

// Merge events and schedules
$data = array_merge($events, $class_schedules);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($data);
?>