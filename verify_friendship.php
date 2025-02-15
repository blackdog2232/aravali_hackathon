<?php
session_start();
include 'presets/conn.php';
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: setup.php");
    exit;
}


if (!isset($_SESSION['userName'])) {
    die("You must be logged in to update your profile.");
}
else{
// Ensure both sender and receiver are provided
$display = $_SESSION['userName'];
if (isset($_GET['sender']) && isset($_GET['receiver'])) {
    $senderF = $_GET['sender'];
    $receiverF = $_GET['receiver'];
    $sender = trim($senderF, '"');        
    $receiver = trim($receiverF, '"');
    if($sender!=$display){
        exit();
    }
    // First, fetch user IDs for the given sender and receiver usernames
    $query = "SELECT userId FROM userdata WHERE userName = ?";
    
    // Prepare and execute the query for the sender username
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $sender);
        $stmt->execute();
        $resultSender = $stmt->get_result();
        $senderID = null;

        if ($resultSender->num_rows > 0) {
            $senderRow = $resultSender->fetch_assoc();
            $senderID = $senderRow['userId']; // Get the sender's userID
        }
        $stmt->close();
    }

    // Prepare and execute the query for the receiver username
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $receiver);
        $stmt->execute();
        $resultReceiver = $stmt->get_result();
        $receiverID = null;

        if ($resultReceiver->num_rows > 0) {
            $receiverRow = $resultReceiver->fetch_assoc();
            $receiverID = $receiverRow['userId']; // Get the receiver's userID
        }
        $stmt->close();
    }

    // If both sender and receiver IDs are found, check if they are friends
    if ($senderID && $receiverID) {
        $friendQuery = "SELECT * FROM friendships WHERE 
                        (userID_1 = ? AND userID_2 = ?) 
                        OR (userID_1 = ? AND userID_2 = ?)";

        if ($stmt = $conn->prepare($friendQuery)) {
            $stmt->bind_param("iiii", $senderID, $receiverID, $receiverID, $senderID);
            $stmt->execute();
            $result = $stmt->get_result();

            // If the result is found, the users are friends
            if ($result->num_rows > 0) {
                echo "true";  // Friend pair exists
            } else {
                echo "false";  // No friendship found
            }

            $stmt->close();
        }
    } else {
        echo "false";  // One or both users not found
    }
}
}
?>
