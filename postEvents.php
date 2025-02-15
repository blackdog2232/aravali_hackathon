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

// CSRF validation on POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if ($_POST['csrf_token'] != $_SESSION['csrf_token']) {
        echo "<div class='alert alert-danger'>CSRF token validation failed.</div>";
        exit;
    }

    // Sanitize and validate input
    $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
    $slots = (int) $_POST['slots'];  // Ensure slots is an integer
    if ($slots <= 0) {
        echo "<div class='alert alert-danger'>Slots must be a positive integer.</div>";
        exit;
    }
    $event_category = mysqli_real_escape_string($conn, $_POST['event_category']);
    $event_type = mysqli_real_escape_string($conn, $_POST['event_type']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $event_state = mysqli_real_escape_string($conn, $_POST['event_state']);
    $event_city = mysqli_real_escape_string($conn, $_POST['event_city']);
    $event_addr = mysqli_real_escape_string($conn, $_POST['event_addr']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Check if event already exists
    $host = $_SESSION['userName'];
    $check_stmt = $conn->prepare("SELECT * FROM events WHERE event_name = ? AND event_date = ? AND host = ?");
    $check_stmt->bind_param("sss", $event_name, $event_date, $host);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        echo "<div class='alert alert-warning'>Event already exists for this date. Please select a different date.</div>";
        exit;
    }
    $check_stmt->close();

    // Prepare SQL statement to insert event
    $stmt = $conn->prepare("INSERT INTO events (host, slots, event_name, event_category, event_type, event_date, event_state, event_city,address, description, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?,?, ?, ?, current_timestamp())");

    // Bind parameters
    $stmt->bind_param("sissssssss", $host, $slots, $event_name, $event_category, $event_type, $event_date, $event_state, $event_city,$event_addr, $description);

    // Execute the statement
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Event created successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    // Close connections
    $stmt->close();
} else {
    //echo "Invalid request method!";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
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
        overflow: hidden; /* Prevents the body from overflowing */
    }

    .form-container {
        background-color: rgba(0, 0, 0, 0.7);
        padding: 40px;
        padding-top: 60px; /* Adds padding at the top */
        padding-bottom: 60px; /* Adds padding at the bottom */
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        max-width: 600px;
        width: 100%;
        max-height: 90vh; /* Prevents the form from getting too tall */
        overflow-y: auto; /* Enables scrolling if the form content overflows */
    }

    /* Custom scrollbar styles */
    .form-container::-webkit-scrollbar {
        width: 8px;
    }

    .form-container::-webkit-scrollbar-thumb {
        background-color: #6200ea;
        border-radius: 8px;
    }

    .form-container::-webkit-scrollbar-thumb:hover {
        background-color: #8e24aa;
    }

    .form-container::-webkit-scrollbar-track {
        background-color: #333;
    }

    label {
        font-weight: 600;
        font-size: 1.1rem;
        color: #e0e0e0;
    }

    input[type="text"], input[type="number"], input[type="date"], select, textarea {
        width: 100%;
        padding: 15px;
        margin: 12px 0;
        border-radius: 8px;
        border: 1px solid #444;
        background-color: #333;
        color: #fff;
        font-size: 1rem;
        box-sizing: border-box;
        transition: all 0.3s;
    }

    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #6200ea;
    }

    input[type="submit"] {
        width: 100%;
        padding: 15px;
        background-color: #6200ea;
        border: none;
        border-radius: 8px;
        font-size: 1.2rem;
        cursor: pointer;
        color: white;
        transition: background-color 0.3s ease;
    }

    input[type="submit"]:hover {
        background-color: #8e24aa;
    }

    input[type="submit"]:disabled {
        background-color: #bbb;
        cursor: not-allowed;
    }

    .spinner {
        display: inline-block;
        font-size: 18px;
        color: #fff;
        font-weight: 500;
    }

    textarea {
        resize: vertical;
    }

    select {
        background-color: #333;
        color: #fff;
        border: 1px solid #444;
    }

    option {
        background-color: #333;
        color: #fff;
    }

    .form-group {
        margin-bottom: 20px;
        width: 100%;
    }

    /* Back Button Style */
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

    /* Alert Styles */
    .alert {
        padding: 15px;
        margin-bottom: 20px; /* Adds spacing below the alert */
        border-radius: 8px;
        font-size: 1rem;
        text-align: center;
        font-weight: bold;
        display: block;
        width: 30%; /* Ensures it takes the full width of its parent */
        box-sizing: border-box; /* Makes sure padding is considered in the width */
    }

    /* Success Alert */
    .alert-success {
        background-color: #4CAF50;
        color: white;
        border: 1px solid #45a049;
    }

    /* Error Alert */
    .alert-danger {
        background-color: #f44336;
        color: white;
        border: 1px solid #e57373;
    }

    /* Warning Alert */
    .alert-warning {
        background-color: #ff9800;
        color: white;
        border: 1px solid #ffb74d;
    }

    /* Position the alert at the top of the form container */
    .form-container {
        position: relative;
    }

    .form-container .alert {
        position: absolute; /* Position the alert absolutely */
        top: 20px; /* Distance from the top of the form */
        left: 50%; /* Center the alert horizontally */
        transform: translateX(-50%); /* Center the alert horizontally with respect to its own width */
        width: 90%; /* Optional: adjust the width to fit within the form */
        z-index: 10; /* Ensure the alert appears above other content */
    }

    /* Responsive adjustments */
    @media (max-width: 600px) {
        h2 {
            font-size: 1.8rem;
        }
        input[type="submit"] {
            font-size: 1rem;
        }
    }
    </style>

    <script>
        const events = {
            "Sports": ["Squash", "Badminton", "Football", "Swimming", "Yoga", "Cricket", "Pickleball", "Golf", "Table Tennis", "Basketball", "Cycling", "Tennis"],
            "Activities": ["Bowling", "Cafe Hopping", "Hangout", "House Party", "Wine Tasting", "Clubbing", "Paintball", "Go-Karting", "Book Club", "Art Gallery", "Stargazing", "Cooking", "Museums", "Board Games", "Volunteering", "Pottery", "Zumba", "Movies", "Gym", "Karaoke", "Photography", "Concerts", "Standups", "Chilling", "Dance"],
            "Travel": ["Hiking", "Road Trips", "Camping", "City Tours", "Backpacking"]
        };

        function updateEventTypes() {
            let category = document.getElementById("event_category").value;
            let eventType = document.getElementById("event_type");
            eventType.innerHTML = "<option value=''>-- Select an Event --</option>";
            
            if (category && events[category]) {
                events[category].forEach(event => {
                    let option = document.createElement("option");
                    option.value = event;
                    option.textContent = event;
                    eventType.appendChild(option);
                });
            }
        }

        function disableSubmitButton() {
            document.getElementById("submitBtn").disabled = true;
            document.getElementById("submitBtn").value = "Submitting...";
            document.getElementById("spinner").style.display = 'inline-block';
        }
    </script>
</head>
<body>

    <div class="form-container">
        <!-- Back Button -->
        <a href="index.php" class="back-button">Back to Home</a>

        <form action="postEvents.php" method="POST" onsubmit="disableSubmitButton()">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <h2>Create an Event</h2>

            <div class="form-group">
                <label for="event_name">Event Name:</label>
                <input type="text" id="event_name" name="event_name" required>
            </div>

            <div class="form-group">
                <label for="slots">Slots:</label>
                <input type="number" name="slots" id="slots" placeholder="Enter number of slots" required min="1">
            </div>

            <div class="form-group">
                <label for="event_category">Select Category:</label>
                <select id="event_category" name="event_category" onchange="updateEventTypes()" required>
                    <option value="">-- Select a Category --</option>
                    <option value="Sports">Sports</option>
                    <option value="Activities">Activities</option>
                    <option value="Travel">Travel</option>
                </select>
            </div>

            <div class="form-group">
                <label for="event_type">Select Event:</label>
                <select id="event_type" name="event_type" required>
                    <option value="">-- Select an Event --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="event_date">Event Date:</label>
                <input type="date" id="event_date" name="event_date" required>
            </div>

            <div class="form-group">
                <label for="event_city">Address</label>
                <input type="text" name="event_addr" id="event_city" placeholder="Enter Address" required>
            </div>

            <div class="form-group">
                <label for="event_city">City:</label>
                <input type="text" name="event_city" id="event_city" placeholder="Enter city" required>
            </div>

            <div class="form-group">
                <label for="event_state">State:</label>
                <input type="text" name="event_state" id="event_state" placeholder="Enter state" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <div class="spinner" id="spinner" style="display:none;">Submitting...</div>
            </div>

            <div class="form-group">
                <input type="submit" id="submitBtn" value="Create Event">
            </div>
        </form>
    </div>

</body>
</html>
