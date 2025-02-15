<style>

    /* General body styling for dark theme */
body {
    background-color: #181818;
    color: #ecf0f1;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.container {
    width: 80%;
    margin: 0 auto;
    padding: 30px 20px; /* Added padding from top */
    background-color: #2c3e50;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
    margin-top: 20px; /* Space between top of page and container */
}

h1 {
    font-size: 32px;
    text-align: center;
    margin-bottom: 30px;
}

.event-card {
    background-color: #34495e;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.event-card:hover {
    background-color: #2c3e50;
}

.event-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.event-title {
    font-size: 24px;
    color: #ecf0f1;
    margin-bottom: 10px;
}

.dropdown-icon {
    font-size: 20px;
    color: #bdc3c7;
    transition: transform 0.3s ease;
}

.dropdown-icon.rotate {
    transform: rotate(180deg);
}

.event-details {
    display: none;
    margin-top: 10px;
    padding: 10px;
    background-color: #34495e;
    border-radius: 8px;
    color: #bdc3c7;
}

.event-details p {
    font-size: 16px;
    margin: 5px 0;
}

button {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #c0392b;
}

/* Button for Join Event */
button.join-btn {
    background-color: #27ae60;
}

button.join-btn:hover {
    background-color: #2ecc71;
}

button.leave-btn {
    background-color: #e74c3c;
}

button.leave-btn:hover {
    background-color: #c0392b;
}

/* Back Button Styling */
.back-button {
    margin-bottom: 20px;
    text-align: center;
}

.back-link {
    background-color: #3498db;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 18px;
    display: inline-block;
}

.back-link:hover {
    background-color: #2980b9;
}

</style>

<?php
include 'presets/conn.php'; // Database connection
session_start();

if (!isset($_SESSION['userName'])) {
    header("location: login.php"); // Redirect to login if not logged in
    exit();
}

$userName = $_SESSION['userName'];

// Get userId for session user
$usr_query = "SELECT userId FROM userdata WHERE userName = ?";
$usr_stmt = mysqli_prepare($conn, $usr_query);
mysqli_stmt_bind_param($usr_stmt, "s", $userName);
mysqli_stmt_execute($usr_stmt);
$usr_result = mysqli_stmt_get_result($usr_stmt);
$userId = 0;

if ($usr_row = mysqli_fetch_assoc($usr_result)) {
    $userId = $usr_row['userId'];
} else {
    echo "User not found.";
}
mysqli_stmt_close($usr_stmt);

// Query to fetch events that the user has joined
$sqlEvent = "
    SELECT e.id, e.event_name, e.event_category, e.event_type, e.event_date, e.event_city, e.event_state, e.host, e.address, e.description
    FROM events e
    JOIN event_participants ep ON e.id = ep.event_id
    WHERE ep.user_id = $userId
";

$resultEvent = mysqli_query($conn, $sqlEvent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcomming Events</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <div class="back-button">
            <a href="index.php" class="back-link">← Back to Home</a>
        </div>

        <h1>Upcomming Events</h1>

        <?php
        if (mysqli_num_rows($resultEvent) > 0) {
            while ($rowEvent = mysqli_fetch_assoc($resultEvent)) {
                echo "<div class='event-card' id='event-" . $rowEvent['id'] . "'>";
                echo "<div class='event-header'>";
                echo "<h3 class='event-title'>" . htmlspecialchars($rowEvent['event_name']) . "</h3>";
                echo "<span class='dropdown-icon'>&#9660;</span>"; // Dropdown icon (Chevron Down)
                echo "</div>";
                
                // Collapsible event details
                echo "<div class='event-details'>";
                echo "<p><strong>Category:</strong> " . htmlspecialchars($rowEvent['event_category']) . "</p>";
                echo "<p><strong>Type:</strong> " . htmlspecialchars($rowEvent['event_type']) . "</p>";
                echo "<p><strong>Date:</strong> " . htmlspecialchars($rowEvent['event_date']) . "</p>";
                echo "<p><strong>Location:</strong> " . htmlspecialchars($rowEvent['event_city']) . ", " . htmlspecialchars($rowEvent['event_state']) . "</p>";
                echo "<p><strong>Host:</strong> " . htmlspecialchars($rowEvent['host']) . "</p>";
                echo "<p><strong>Description:</strong> " . htmlspecialchars($rowEvent['description']) . "</p>";
                echo "<p><strong>Address:</strong> " . htmlspecialchars($rowEvent['address']) . "</p>";
                echo "</div>"; // Close event-details
                
                // Leave button
                echo "<button class='leave-btn' data-event-id='" . $rowEvent['id'] . "'>Leave Event</button>";
                echo "</div>"; // Close event-card
            }
        } else {
            echo "<p>You have not joined any events yet.</p>";
        }
        ?>
    </div>

    <script>
        $(document).ready(function() {
            // Toggle event details and rotate icon
            $(".event-header").click(function() {
                var details = $(this).next(".event-details");
                var icon = $(this).find(".dropdown-icon");

                details.slideToggle(); // Show/hide the event details
                icon.toggleClass("rotate"); // Rotate the icon when details are shown/hidden
            });

            $(".leave-btn").click(function() {
                var button = $(this);
                var eventId = button.data("event-id");

                $.ajax({
                    url: "leave_event.php", // PHP script to handle the leave request
                    type: "POST",
                    data: { event_id: eventId },
                    success: function(response) {
                        if (response.trim() === "unjoined") {
                            button.text("You left The event");
                            button.css("background-color", "#27ae60");
                        } else {
                            alert("Something went wrong while leaving the event.");
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
