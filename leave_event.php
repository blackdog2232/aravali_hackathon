<?php
include 'presets/conn.php'; // Database connection
session_start();

if (!isset($_SESSION['userName'])) {
    echo "not_logged_in"; // Not logged in
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
    exit();
}
mysqli_stmt_close($usr_stmt);

if (isset($_POST['event_id'])) {
    $eventId = $_POST['event_id'];

    // Remove the user from the event in the event_participants table
    $query = "DELETE FROM event_participants WHERE user_id = ? AND event_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $userId, $eventId);

    if (mysqli_stmt_execute($stmt)) {
        echo "unjoined"; // Successfully left the event
    } else {
        echo "error";
    }

    mysqli_stmt_close($stmt);
}
?>
