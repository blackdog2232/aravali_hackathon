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
    $userF = $_POST['user'];
    $action = $_POST['action'];
    if (!empty($senderF) && !empty($userF) && $senderF !== $userF) {
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
            $stmt->bind_param("s", $userF);
            $stmt->execute();
            $resultSender = $stmt->get_result();
            $senderID = null;
        
            if ($resultSender->num_rows > 0) {
                $senderRow = $resultSender->fetch_assoc();
                $receiver = $senderRow['userId']; // Get the receiver's userID
            }
        }
        if ($action === "follow") {
            $stmt_check = $conn->prepare("SELECT id FROM friend_request WHERE sender = ? AND receiver = ?");
            $stmt_check->bind_param("ss", $senderF, $userF);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 0) {
                $stmt_insert = $conn->prepare("INSERT INTO friend_request (sender, receiver) VALUES (?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ss", $senderF, $userF);
                    $stmt_insert->execute();
                    $stmt_insert->close();
                    echo "followed";
                }
            }
            $stmt_check->close();
        } elseif ($action === "cancel") {
            $stmt_delete = $conn->prepare("DELETE FROM friend_request WHERE sender = ? AND receiver = ?");
            $stmt_delete->bind_param("ss", $senderF, $userF);
            $stmt_delete->execute();
            $stmt_delete->close();
            echo "canceled";
        } elseif ($action === "unfollow") {
            error_log("Unfollow request received: $senderF -> $userF"); // Debugging
            $stmt_unfollow = $conn->prepare("DELETE FROM friendships WHERE (userID_1 = ? AND userID_2 = ?) OR (userID_2 = ? AND userID_1 = ?)");
            $stmt_unfollow->bind_param("iiii", $sender, $receiver, $sender, $receiver);
            if ($stmt_unfollow->execute()) {
                echo "unfollowed";
            } else {
                echo "error";
            }
            $stmt_unfollow->close();
            }
        }
    }

$conn->close();
?>
