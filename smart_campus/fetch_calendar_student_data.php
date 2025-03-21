<?php
session_start();
include 'db_connect.php';

// Check if the student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id']; // Assuming student_id is stored in session after login

// Fetch student details including registered course
$student_query = "SELECT id, first_name, last_name, email, registered_course, batch_id, birth_date, gender, username FROM student WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$registered_course = $student['registered_course']; // Get the registered course

// Fetch class schedules for the student's registered course
$class_schedule_query = "
    SELECT 
        c.id AS class_id, 
        c.course_id AS class_course_id,
        c.schedule_date, 
        c.start_time, 
        c.end_time
    FROM class_schedules c
    WHERE c.course_id = ?
";

$stmt = $conn->prepare($class_schedule_query);
$stmt->bind_param("i", $registered_course);
$stmt->execute();
$class_result = $stmt->get_result();

$class_schedules = [];
while ($row = $class_result->fetch_assoc()) {
    // Add class schedules to the array
    $class_schedules[] = [
        'id' => 'class_' . $row['class_id'],
        'title' => 'Class for Course ' . $row['class_course_id'],
        'start' => $row['schedule_date'] . 'T' . $row['start_time'], // Ensure ISO format for FullCalendar
        'end' => $row['schedule_date'] . 'T' . $row['end_time'],     // Ensure ISO format for FullCalendar
        'backgroundColor' => '#007bff',  // Blue for class schedules
        'borderColor' => '#007bff',
        'className' => 'event-type-class',
        'description' => 'Class Schedule: Course ' . $row['class_course_id']
    ];
}

// Fetch events for the student's registered course
$event_query = "
    SELECT 
        e.id AS event_id,
        e.title AS event_title,
        e.description AS event_description,
        e.event_date, 
        e.start_time AS event_start_time, 
        e.end_time AS event_end_time
    FROM events e
    LEFT JOIN event_courses ec ON e.id = ec.event_id
    WHERE ec.course_id = ?
";

$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $registered_course);
$stmt->execute();
$event_result = $stmt->get_result();

$events = [];
while ($row = $event_result->fetch_assoc()) {
    // Add events to the array
    $events[] = [
        'id' => 'event_' . $row['event_id'],
        'title' => $row['event_title'],
        'start' => $row['event_date'] . 'T' . $row['event_start_time'],  // Ensure ISO format for FullCalendar
        'end' => $row['event_date'] . 'T' . $row['event_end_time'],     // Ensure ISO format for FullCalendar
        'backgroundColor' => '#FF5733',  // Orange for general events
        'borderColor' => '#FF5733',
        'className' => 'event-type-event',
        'description' => $row['event_description']
    ];
}

// Return both class schedules and events as JSON
echo json_encode([
    'class_schedules' => $class_schedules,
    'events' => $events
]);
?>
