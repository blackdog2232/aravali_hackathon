<?php
require 'presets/conn.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in
if (!isset($_SESSION['userName'])) {
    $conn->close();
    header("location:setup.php");
    exit;
}

// Validate event ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid event ID.");
}

$event_id = intval($_GET['id']);

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Event not found.");
}

// Fetch comments
$stmt = $conn->prepare("SELECT comment_id, event_id, userName, comment_text, sentiment, created_at FROM comments WHERE event_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #121212; color: #e0e0e0; margin: 20px; }
        .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #333; border-radius: 10px; background-color: #1e1e1e; }
        h2 { color: #fff; }
        .back-button { position: absolute; top: 20px; left: 20px; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .comment-box { margin-top: 20px; }
        textarea { width: 100%; padding: 10px; background-color: #333; color: #e0e0e0; border: 1px solid #444; border-radius: 5px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .comment { border-bottom: 1px solid #444; padding: 10px 0; }
        .toggle-comments-btn { background-color: #444; color: #fff; padding: 10px; border: none; border-radius: 5px; cursor: pointer; }
        .toggle-comments-btn:hover { background-color: #333; }
    </style>
</head>
<body>

<div class="container">
    <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
    <p><strong>Hosted by:</strong> <?php echo htmlspecialchars($event['host']); ?></p>
    <p><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($event['event_city']).", ".htmlspecialchars($event['event_state']); ?></p>
    <p><strong>Address:</strong> <?php echo htmlspecialchars($event['address']); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
    <a href="index.php" class="back-button">Back</a>

    <!-- Comment Section -->
    <div class="comment-box">
        <h3>Leave a Comment</h3>
        <form id="commentForm">
            <textarea name="comment" id="commentText" rows="4" placeholder="Write your comment here..." required></textarea>
            <br>
            <button type="submit">Post Comment</button>
            <button type="button" id="toggleCommentsBtn" class="toggle-comments-btn">Show Comments</button>
        </form>
    </div>

    <!-- Comments Section -->
    <div id="commentsSection" style="display: none;">
        <h3>Comments</h3>
        <?php while ($row = $comments->fetch_assoc()): ?>
            <div class="comment">
                <p><strong><?php echo htmlspecialchars($row['userName']); ?>:</strong> <?php echo nl2br(htmlspecialchars($row['comment_text'])); ?></p>
                <small>Posted on: <?php echo $row['created_at']; ?></small>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
document.getElementById("commentForm").addEventListener("submit", function(event) {
    event.preventDefault(); 

    let commentText = document.getElementById("commentText").value;
    if (!commentText.trim()) return;

    let formData = new FormData();
    formData.append("comment", commentText);
    formData.append("event_id", "<?php echo $event_id; ?>");

    fetch("sentiment_analysis.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Comment posted!");
            location.reload(); 
        } else {
            alert("Error posting comment.");
        }
    })
    .catch(error => console.error("Error:", error));
});

document.getElementById("toggleCommentsBtn").addEventListener("click", function() {
    let commentsSection = document.getElementById("commentsSection");
    commentsSection.style.display = commentsSection.style.display === "none" ? "block" : "none";
});
</script>

</body>
</html>
