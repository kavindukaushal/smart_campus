<?php
session_start();
include 'db_connect.php';
require_once('vendor/autoload.php'); // Include TCPDF if installed via Composer

// Redirect if not logged in or not an admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all events for selection
$events_query = "SELECT id, title FROM events";
$events_result = $conn->query($events_query);

// Handle report generation
$report_data = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['event_id'], $_POST['total_count'], $_POST['participated_count'])) {
        $event_id = $_POST['event_id'];
        $total_count = $_POST['total_count'];
        $participated_count = $_POST['participated_count'];

        // Fetch event details from the database
        $event_query = "SELECT * FROM events WHERE id = '$event_id'";
        $event_result = $conn->query($event_query);

        if ($event_result && $event_result->num_rows > 0) {
            $event_details = $event_result->fetch_assoc();

            // Prepare report data
            $report_data = [
                'title' => $event_details['title'],
                'description' => $event_details['description'],
                'event_date' => $event_details['event_date'],
                'start_time' => $event_details['start_time'],
                'end_time' => $event_details['end_time'],
                'venue' => $event_details['venue'],
                'total_count' => $total_count,
                'participated_count' => $participated_count
            ];
            
            // Handle PDF download immediately if requested
            if (isset($_POST['download_pdf']) && $_POST['download_pdf'] === 'true') {
                // Generate the PDF
                $pdf = new TCPDF();
                $pdf->AddPage();

                // Title
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, "Event Participation Report: {$report_data['title']}", 0, 1, 'C');

                // Event Details
                $pdf->SetFont('helvetica', '', 12);
                $pdf->MultiCell(0, 10, "Event Description: {$report_data['description']}");
                $pdf->MultiCell(0, 10, "Event Date: {$report_data['event_date']}");
                $pdf->MultiCell(0, 10, "Start Time: {$report_data['start_time']}");
                $pdf->MultiCell(0, 10, "End Time: {$report_data['end_time']}");
                $pdf->MultiCell(0, 10, "Venue: {$report_data['venue']}");
                $pdf->MultiCell(0, 10, "Total Participants: {$report_data['total_count']}");
                $pdf->MultiCell(0, 10, "Participants Attended: {$report_data['participated_count']}");
                
                // Calculate percentage
                $attendance_percentage = ($report_data['participated_count'] / $report_data['total_count']) * 100;
                $attendance_percentage = round($attendance_percentage, 1);
                $pdf->MultiCell(0, 10, "Attendance Rate: {$attendance_percentage}%");
                
                // Add some spacing
                $pdf->Ln(5);
                
                // Draw a simple table for the data instead of using an image
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(90, 10, "Metric", 1, 0, 'C');
                $pdf->Cell(90, 10, "Count", 1, 1, 'C');
                
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(90, 10, "Total Participants", 1, 0, 'L');
                $pdf->Cell(90, 10, $report_data['total_count'], 1, 1, 'C');
                
                $pdf->Cell(90, 10, "Participants Attended", 1, 0, 'L');
                $pdf->Cell(90, 10, $report_data['participated_count'], 1, 1, 'C');
                
                $pdf->Cell(90, 10, "Attendance Rate", 1, 0, 'L');
                $pdf->Cell(90, 10, $attendance_percentage . "%", 1, 1, 'C');
                
                // Add a simple visual representation using TCPDF built-in graphics
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, "Visual Representation of Attendance", 0, 1, 'C');
                
                // Draw a horizontal bar chart
                $barWidth = 180;
                $startX = 15;
                $startY = $pdf->GetY() + 10;
                $height = 20;
                
                // Total bar (background)
                $pdf->SetFillColor(220, 220, 220);
                $pdf->Rect($startX, $startY, $barWidth, $height, 'F');
                
                // Attended bar (foreground)
                $attendedWidth = ($report_data['participated_count'] / $report_data['total_count']) * $barWidth;
                $pdf->SetFillColor(75, 192, 192);
                $pdf->Rect($startX, $startY, $attendedWidth, $height, 'F');
                
                // Add labels
                $pdf->SetY($startY + $height + 5);
                $pdf->SetX($startX);
                $pdf->Cell($barWidth, 10, "Attendance: {$report_data['participated_count']} of {$report_data['total_count']} ({$attendance_percentage}%)", 0, 1, 'C');
                
                // Add summary text
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, "Summary", 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 12);
                
                $summary_text = "This report summarizes the attendance for the event \"{$report_data['title']}\". ";
                $summary_text .= "Out of {$report_data['total_count']} registered participants, {$report_data['participated_count']} attended the event, ";
                $summary_text .= "resulting in an attendance rate of {$attendance_percentage}%.";
                
                $pdf->MultiCell(0, 10, $summary_text);

                // Output PDF to browser
                $pdf->Output('event_participation_report.pdf', 'D');
                exit;
            }
        } else {
            $report_data = [];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Participation Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            color: #333;
        }
        header {
            background-color: #005f72;
            color: white;
            padding: 15px 0;
            text-align: center;
        }
        header img {
            height: 40px;
        }
        .header-left {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .text h1 {
            margin: 0;
            font-size: 24px;
        }
        .text p {
            margin: 0;
            font-size: 14px;
            color: #ddd;
        }
        nav ul {
            list-style: none;
            padding: 0;
            display: flex;
            justify-content: center;
        }
        nav ul li {
            margin: 0 15px;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .report-container {
            margin: 20px auto;
            width: 80%;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .report-container h2 {
            font-size: 26px;
            margin-bottom: 20px;
        }
        label {
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 8px;
            font-size: 16px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #005f72;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #00474c;
        }
        .report-result {
            margin-top: 30px;
        }
        .download-button {
            background-color: #28a745;
        }
        .clear-button {
            background-color: #dc3545;
        }
        .clear-button:hover {
            background-color: #c82333;
        }
        canvas {
            max-width: 100%;
            margin: 20px 0;
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
    </style>
</head>
<body>

<!-- Header -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
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
                <li><a href="dashboard.php"> <div class="button-container">
        <button onclick="window.location.href='st_dash.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div></a></li>
            </ul>
        </nav>
    </header>

<!-- Report Generation Section -->
<div class="report-container">
    <h2>Generate Event Participation Report</h2>
    
    <!-- Event Selection Form -->
    <form id="reportForm" method="POST" action="generate_report.php">
        <label for="event_id">Select Event</label>
        <select name="event_id" id="event_id" required>
            <option value="">Select Event</option>
            <?php while ($event = $events_result->fetch_assoc()): ?>
                <option value="<?php echo $event['id']; ?>" data-title="<?php echo $event['title']; ?>"><?php echo $event['title']; ?></option>
            <?php endwhile; ?>
        </select>
        
        <label for="total_count">Total Participants</label>
        <input type="number" name="total_count" id="total_count" min="0" required>

        <label for="participated_count">Participants Attended</label>
        <input type="number" name="participated_count" id="participated_count" min="0" required>

        <button type="submit">Generate Report</button>
    </form>

    <?php if (!empty($report_data)): ?>
        <div class="report-result">
            <h3>Report for Event: <?php echo htmlspecialchars($report_data['title']); ?></h3>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($report_data['description'])); ?></p>
            <p><strong>Event Date:</strong> <?php echo htmlspecialchars($report_data['event_date']); ?></p>
            <p><strong>Start Time:</strong> <?php echo htmlspecialchars($report_data['start_time']); ?></p>
            <p><strong>End Time:</strong> <?php echo htmlspecialchars($report_data['end_time']); ?></p>
            <p><strong>Venue:</strong> <?php echo htmlspecialchars($report_data['venue']); ?></p>
            <p><strong>Total Participants:</strong> <?php echo $report_data['total_count']; ?></p>
            <p><strong>Participants Attended:</strong> <?php echo $report_data['participated_count']; ?></p>

            <!-- Chart for participation -->
            <canvas id="participationChart" width="400" height="200"></canvas>

            <!-- Use a separate form for PDF download to preserve the report data -->
            <form method="POST" action="generate_report.php" style="display: inline;">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($_POST['event_id']); ?>">
                <input type="hidden" name="total_count" value="<?php echo htmlspecialchars($_POST['total_count']); ?>">
                <input type="hidden" name="participated_count" value="<?php echo htmlspecialchars($_POST['participated_count']); ?>">
                <input type="hidden" name="download_pdf" value="true">
                <button type="submit" class="download-button">Download PDF</button>
            </form>

            <!-- Clear Button -->
            <button onclick="clearForm()" class="clear-button">Clear</button>
        </div>

        <script>
            // Render chart using Chart.js
            const ctx = document.getElementById('participationChart').getContext('2d');
            const participationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total Participants', 'Participants Attended'],
                    datasets: [{
                        label: 'Event Participation',
                        data: [<?php echo $report_data['total_count']; ?>, <?php echo $report_data['participated_count']; ?>],
                        backgroundColor: ['rgba(54, 162, 235, 0.2)', 'rgba(75, 192, 192, 0.2)'],
                        borderColor: ['rgba(54, 162, 235, 1)', 'rgba(75, 192, 192, 1)'],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Function to clear the form and report
            function clearForm() {
                window.location.href = 'generate_report.php';
            }
        </script>
    <?php endif; ?>
</div>

</body>
</html>
