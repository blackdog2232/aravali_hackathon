<?php
session_start();

// CSRF token protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure token
}

include "presets/conn.php";

if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: setup.php");
    exit;
}

// Fetch events hosted by the logged-in user
$host = $_SESSION['userName'];
$query = "SELECT id, host, slots, event_name, event_category, event_type, event_date, event_state, event_city, address, description, created_at 
          FROM events 
          WHERE host = ? ORDER BY event_date DESC"; // Order by date (latest first)

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $host);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user has hosted any events
$events = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
} else {
    $no_events_message = "You haven't hosted any events yet.";
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Hosted Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #2e2e2e, #121212);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 40px;
            padding-top: 60px;
            padding-bottom: 60px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        h2 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto; /* Makes the table horizontally scrollable */
            max-width: 100%;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #444;
            text-align: center;
        }

        th, td {
            padding: 12px;
        }

        th {
            background-color: #6200ea;
            color: white;
        }

        td {
            background-color: #333;
        }

        /* Custom scrollbar for table container */
        .table-container::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background-color: #6200ea;
            border-radius: 8px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background-color: #8e24aa;
        }

        .table-container::-webkit-scrollbar-track {
            background-color: #333;
        }

        .back-button {
            text-decoration: none;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            display: inline-block;
            padding: 10px 20px;
            background-color: #6200ea;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #8e24aa;
        }

        /* Responsive design adjustments */
        @media (max-width: 768px) {
            h2 {
                font-size: 1.6rem;
            }
            table th, table td {
                font-size: 0.9rem;
            }
        }

    </style>
</head>
<body>

    <div class="form-container">
        <!-- Back Button -->
        <a href="index.php" class="back-button">Back to Home</a>
        
        <h2>Your Hosted Events</h2>

        <?php if (isset($no_events_message)) { ?>
            <div class="alert alert-warning"><?php echo $no_events_message; ?></div>
        <?php } else { ?>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Event Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Slots</th>
                    </tr>

                    <?php foreach ($events as $event) { ?>
                        <tr>
                            <td><?php echo $event['event_name']; ?></td>
                            <td><?php echo $event['event_category']; ?></td>
                            <td><?php echo $event['event_type']; ?></td>
                            <td><?php echo $event['event_date']; ?></td>
                            <td><?php echo $event['event_city']; ?></td>
                            <td><?php echo $event['event_state']; ?></td>
                            <td><?php echo $event['slots']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } ?>
    </div>

</body>
</html>

<?php
$conn->close();
?>
