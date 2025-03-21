<?php
session_start();
include 'db_connect.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Get reservation ID and action (approve/reject)
if (isset($_GET['id'], $_GET['action'])) {
    $reservation_id = $_GET['id'];
    $action = $_GET['action'];
    
    // Validate action
    if ($action !== 'approve' && $action !== 'reject') {
        echo "Invalid action!";
        exit;
    }

    // Set the new status
    $status = $action === 'approve' ? 'approved' : 'rejected';
    
    // Update the reservation status in the database
    $update_query = "UPDATE reservation_requests SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $reservation_id);
    $stmt->execute();

    // Redirect back to the dashboard
    header("Location: dashboard.php");
    exit();
} else {
    echo "No reservation found!";
    exit();
}
?>
