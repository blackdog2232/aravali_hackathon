<?php
// Include database connection
include "presets/conn.php";

// Check for database connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get sender and receiver from the request
$sender = $_GET['sender'] ?? '';
$receiver = $_GET['receiver'] ?? '';

if (empty($sender) || empty($receiver)) {
    die("Sender and Receiver are required!");
}

// Prepare SQL query to fetch chat messages
$stmt = $conn->prepare("
    SELECT sender, message, timestamp 
    FROM messages 
    WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?) 
    ORDER BY timestamp ASC
");
$stmt->bind_param("ssss", $sender, $receiver, $receiver, $sender);
$stmt->execute();
$result = $stmt->get_result();

// Display chat messages
while ($row = $result->fetch_assoc()) {
    $class = ($row['sender'] == $sender) ? 'sender' : 'receiver';
    ?>
    <div class="message <?php echo $class; ?>">
        <strong><?php echo htmlspecialchars($row['sender']); ?>:</strong>
        <?php echo htmlspecialchars($row['message']); ?>
        <div class="timestamp"><?php echo date('h:i A', strtotime($row['timestamp'])); ?></div>
    </div>
    <?php
}

// Close the statement and database connection
$stmt->close();
$conn->close();
?>
