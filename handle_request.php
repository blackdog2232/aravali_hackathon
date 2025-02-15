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

$display = $_SESSION['userName'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $senderF = $_POST['sender'];
    $receiverF = $_POST['receiver'];
    $action = $_POST['action'];

    if ($action === "accept") {
        // Insert into friends table
        $query = "SELECT userId FROM userdata WHERE userName = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $senderF);
            $stmt->execute();
            $resultSender = $stmt->get_result();
            $senderID = null;
        
            if ($resultSender->num_rows > 0) {
                $senderRow = $resultSender->fetch_assoc();
                $sender = $senderRow['userId']; // Get the sender's userID
            }
        }
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $receiverF);
            $stmt->execute();
            $resultSender = $stmt->get_result();
            $senderID = null;
        
            if ($resultSender->num_rows > 0) {
                $senderRow = $resultSender->fetch_assoc();
                $receiver = $senderRow['userId']; // Get the sender's userID
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO friendships (userID_1, userID_2) VALUES (?, ?)");
        $stmt->bind_param("ii", $sender, $receiver);
        $stmt->execute();
        $stmt->close();
    }

    // Delete friend request after accept/reject
    $stmt = $conn->prepare("DELETE FROM friend_request WHERE sender = ? AND receiver = ?");
    $stmt->bind_param("ss", $senderF, $receiverF);
    $stmt->execute();
    $stmt->close();
}

header("Location: index.php");
exit();
?>
