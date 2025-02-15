<style>
.events-container {
    margin: 20px;
    padding: 15px;
    background: #1f1d1d; /* Dark background */
    border-radius: 8px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3); /* Darker shadow */
    color: white; /* Light text for contrast */
}

.events-list {
    list-style: none;
    padding: 0;
}

.event-item {
    padding: 10px;
    margin: 10px 0;
    background: #2b2a2a; /* Darker shade for event items */
    border-radius: 6px;
    color: white;
}

.sentiment {
    font-weight: bold;
    padding: 3px 8px;
    border-radius: 4px;
}

.sentiment.positive {
    color: #4caf50; /* Green */
}

.sentiment.negative {
    color: #ff5252; /* Red */
}

.sentiment.neutral {
    color: #9e9e9e; /* Gray */
}
.event-header {
    display: flex;
    justify-content: space-between;
    cursor: pointer;
    padding: 10px;
    background: #333;
    border-radius: 6px;
}

.event-header h4 {
    margin: 0;
}

.event-details {
    display: none;
    padding: 10px;
    margin-top: 5px;
    background: #2b2a2a;
    border-radius: 6px;
}
    </style>
<?php
include 'presets/conn.php'; // Database connection
session_start();

if (!isset($_SESSION['userName'])) {
    header("location: setup.php");
    exit();
}

$userName = $_SESSION['userName'];
if (isset($_GET['user'])) {
    $userF = htmlspecialchars($_GET['user']); // Prevent XSS
    $query = "SELECT userId FROM userdata WHERE userName = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        $resultSender = $stmt->get_result();
        $senderID = null;
    
        if ($resultSender->num_rows > 0) {
            $senderRow = $resultSender->fetch_assoc();
            $sender = $senderRow['userId']; // Get the sender's userID
        }
    }
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $userF);
        $stmt->execute();
        $resultSender = $stmt->get_result();
        $senderID = null;
    
        if ($resultSender->num_rows > 0) {
            $senderRow = $resultSender->fetch_assoc();
            $receiver = $senderRow['userId']; // Get the sender's userID
        }
    }
    // Query to find user by userName
    $stmt = $conn->prepare("SELECT * FROM userprofile WHERE userName = ?");
    $stmt->bind_param("s", $userF);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userFound = true;
    } else {
        echo "User not found.";
        exit();
    }

    // Check if a friend request already exists
    $stmt_check = $conn->prepare("SELECT id FROM friend_request WHERE sender = ? AND receiver = ?");
    $stmt_check->bind_param("ss", $userName, $userF);
    $stmt_check->execute();
    $stmt_check->store_result();
    $isFollowing = $stmt_check->num_rows > 0;
    $stmt_check->close();

    // Check if they are already friends
    $stmt_friends = $conn->prepare("
        SELECT id FROM friendships WHERE 
        (userID_1 = ? AND userID_2 = ?) OR 
        (userID_2 = ? AND userID_1 = ?)
    ");
    $stmt_friends->bind_param("iiii", $sender, $receiver, $sender, $receiver);
    $stmt_friends->execute();
    $stmt_friends->store_result();
    $isFriend = $stmt_friends->num_rows > 0;
    $stmt_friends->close();

        // Fetch events hosted by the user
        $stmt = $conn->prepare("
        SELECT id, event_name, event_category, event_type, event_date, event_city, sentiment_summary 
        FROM events 
        WHERE host = ?
    ");
    $stmt->bind_param("s", $userF);
    $stmt->execute();
    $eventsResult = $stmt->get_result();
    $stmt->close();
} else {
    header("location: find.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($userName) ?>'s Profile</title>
    <link rel="stylesheet" href="oneUser.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Top Link Back -->
    <div class="top">
        <a href="index.php">Back</a>
    </div>

    <?php if (isset($userFound) && $userFound): ?>
        <div class="profile-container">
            <!-- Profile Image -->
            <img class="profile-img" src="<?= htmlspecialchars($row['displayPicture'] ?? 'default.png') ?>" alt="Profile Photo">

            <!-- Profile Info -->
            <div class="profile-info">
                <h2 class="username"><?= htmlspecialchars($row['name']) ?></h2>

                <div class="profile-actions">
                    <!-- Follow Button -->
                    <button class="btn follow-btn" data-user="<?= htmlspecialchars($userF) ?>">
                        <?= $isFriend ? "Unfollow" : ($isFollowing ? "Cancel Request" : "Follow") ?>
                    </button>
                </div>

                <!-- Bio and Interests -->
                <p class="bio"><strong>Bio:</strong> <?= htmlspecialchars($row['bio']) ?></p>
                <p class="interests"><strong>Interests:</strong> <?= htmlspecialchars($row['interests']) ?></p>
            </div>
        </div>
        <div class="events-container">
    <h3>Events Hosted by <?= htmlspecialchars($row['name']) ?>:</h3>
    <?php if ($eventsResult->num_rows > 0): ?>
        <ul class="events-list">
            <?php while ($event = $eventsResult->fetch_assoc()): ?>
                <li class="event-item">
                    <div class="event-header" onclick="toggleEventDetails(this)">
                        <h4><?= htmlspecialchars($event['event_name']) ?></h4>
                        <span class="sentiment <?= htmlspecialchars($event['sentiment_summary']) ?>">
                            <?= htmlspecialchars(ucfirst($event['sentiment_summary'] ?? 'Neutral')) ?>
                        </span>
                    </div>
                    <div class="event-details">
                        <p><strong>Category:</strong> <?= htmlspecialchars($event['event_category']) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($event['event_type']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
                        <p><strong>City:</strong> <?= htmlspecialchars($event['event_city']) ?></p>
                        <?php
$sentimentText = [
    "positive" => "Mostly Positive – Attendees had a great experience!",
    "negative" => "Mostly Negative – Users expressed concerns or dissatisfaction.",
    "neutral" => "Neutral – Mixed or balanced feedback."
];
$finalSentiment = htmlspecialchars($event['sentiment_summary'] ?? 'neutral');
                        ?>
<p><strong>Final Sentiment:</strong> 
    <span class="sentiment <?= $finalSentiment ?>">
        <?= $sentimentText[$finalSentiment] ?? "Neutral – Mixed or balanced feedback." ?>
    </span>
</p>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No events hosted by this user.</p>
    <?php endif; ?>
</div>

    <?php else: ?>
        <p>User data not found.</p>
    <?php endif; ?>

    <!-- JavaScript for Follow Button -->
    <script>
        $(document).ready(function(){
            $(".follow-btn").click(function(){
                var button = $(this);
                var receiver = button.data("user");
                var action = button.text().trim(); // Get current button text

                if (action === "Follow") action = "follow";
                else if (action === "Cancel Request") action = "cancel";
                else if (action === "Unfollow") action = "unfollow"; // Unfollow action

                console.log("Sending AJAX: " + action + " to " + receiver); // Debugging

                $.ajax({
                    url: "follow.php",
                    type: "POST",
                    data: { sender: "<?= $userName ?>", user: receiver, action: action },
                    success: function(response) {
                        console.log("Server Response: " + response); // Debugging

                        if (response.trim() === "followed") {
                            button.text("Cancel Request");
                        } else if (response.trim() === "canceled") {
                            button.text("Follow");
                        } else if (response.trim() === "unfollowed") { // Handle Unfollow case
                            button.text("Follow");
                        } else {
                            alert("Something went wrong: " + response);
                        }
                    }
                });
            });
        });
    </script>

<script>
    function toggleEventDetails(element) {
        var details = element.nextElementSibling;
        details.style.display = details.style.display === "none" || details.style.display === "" ? "block" : "none";
    }
</script>
</body>
</html>
