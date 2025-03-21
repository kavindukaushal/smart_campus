<?php
$host = "localhost";
$user = "root"; // Change if using a different username
$password = ""; // Change if using a different password
$dbname = "smart_campus_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>