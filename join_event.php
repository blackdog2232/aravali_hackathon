<?php
include 'presets/conn.php'; // Database connection
session_start();

if (isset($_SESSION['userName'])) {
    $usr_session_name = $_SESSION['userName']; // Get username from session

    // Query to get the user's ID
    $usr_query = "SELECT userId FROM userdata WHERE userName = ?";
    $usr_stmt = mysqli_prepare($conn, $usr_query);
    mysqli_stmt_bind_param($usr_stmt, "s", $usr_session_name);
    mysqli_stmt_execute($usr_stmt);
    $usr_result = mysqli_stmt_get_result($usr_stmt);

    if ($usr_row = mysqli_fetch_assoc($usr_result)) {
        $userId = $usr_row['userId']; // Get the user ID
    } else {
        echo "User not found.";
        exit();
    }

    mysqli_stmt_close($usr_stmt);

    // Get the event ID from POST
    if (isset($_POST['event_id'])) {
        $eventId = $_POST['event_id'];

        // Check if the user is already part of the event
        $check_query = "SELECT id FROM event_participants WHERE user_id = ? AND event_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $userId, $eventId);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            // User is already joined, so we need to "Leave" the event
            // Delete the participation record from event_participants
            $delete_query = "DELETE FROM event_participants WHERE user_id = ? AND event_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "ii", $userId, $eventId);
            if (mysqli_stmt_execute($delete_stmt)) {
                echo "unjoined"; // Successfully left the event
            } else {
                echo "error";
            }
            mysqli_stmt_close($delete_stmt);
        } else {
            // User is not part of the event, so we need to "Join"
            $insert_query = "INSERT INTO event_participants (user_id, event_id) VALUES (?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ii", $userId, $eventId);
            if (mysqli_stmt_execute($insert_stmt)) {
                echo "joined"; // Successfully joined the event
            } else {
                echo "error";
            }
            mysqli_stmt_close($insert_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
} else {
    echo "not_logged_in"; // User is not logged in
}
?>
