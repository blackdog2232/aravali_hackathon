<?php
session_start();
include 'conn.php';
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: ../setup.php");
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$display = $_SESSION['userName'];

// Start by clearing any previous output, just in case
ob_clean();
header('Content-Type: application/json');  // Make sure we're returning JSON

// Retrieve the current city and state from the database
$stmt = $conn->prepare("SELECT city, state FROM userprofile WHERE userName = ?");
$stmt->bind_param("s", $display);

$stmt->execute();
$stmt->bind_result($city, $state);
$stmt->fetch();

// If user data is found, return the city and state
if ($city && $state) {
    echo json_encode([
        'city' => $city,
        'state' => $state
    ]);
} else {
    echo json_encode([
        'error' => 'Location not found for this user'
    ]);
}

$stmt->close();
?>
