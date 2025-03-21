<?php
session_start();
include('db_connect.php');  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $course_id = $_POST['course_id'];
    $lecturer_id = $_SESSION['lecturer_id'];  // Assuming the lecturer is logged in
    $file = $_FILES['file'];

    // Get the file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Define allowed file extensions
    $allowed_extensions = ['pdf', 'docx', 'pptx'];

    // Check if the file extension is allowed
    if (!in_array($file_ext, $allowed_extensions)) {
        // If the file extension is not allowed, set session variable and redirect
        $_SESSION['upload_success'] = 0;  // Upload failed due to invalid file type
        $_SESSION['error_message'] = "Invalid file type. Only PDF, DOCX, and PPTX files are allowed.";  // Store error message
        header('Location: lec_dash.php');
        exit();  // Stop the script execution after redirect
    }

    // Define file upload directory
    $upload_dir = 'uploads/';

    // Make sure the upload directory exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);  // Create the directory if it doesn't exist
    }

    // Define the full file path for the uploaded file
    $file_path = $upload_dir . basename($file['name']);

    // Move uploaded file to the directory
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Save file details to the database
        $stmt = $conn->prepare("INSERT INTO uploads (course_id, lecturer_id, file_name, file_type, file_size, file_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissis", $course_id, $lecturer_id, $file['name'], $file_ext, $file['size'], $file_path);
        $stmt->execute();

        // Set session variable for success
        $_SESSION['upload_success'] = 1;  // Upload successful
        // Redirect to lec_dash.php
        header('Location: lec_dash.php');
        exit();  // Stop script execution after redirect
    } else {
        // Set session variable for failure
        $_SESSION['upload_success'] = 0;  // Upload failed
        $_SESSION['error_message'] = "Failed to upload file.";  // Store failure message
        // Redirect to lec_dash.php
        header('Location: lec_dash.php');
        exit();  // Stop script execution after redirect
    }
}
?>
