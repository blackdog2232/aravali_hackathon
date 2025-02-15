<?php
session_start();
include 'conn.php';
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: ../setup.php");
    exit;
}

$display = $_SESSION['userName'];

// Turn on error reporting for debugging (optional, can be removed in production)
ini_set('display_errors', 1);

// Start by clearing any previous output, just in case
ob_clean();
header('Content-Type: application/json');  // Make sure we're returning JSON

if (isset($_POST['city']) && isset($_POST['state'])) {
    $city = $_POST['city'];
    $state = $_POST['state'];

    // Update the user's profile with the new city and state
    $stmt = $conn->prepare("UPDATE userprofile SET city = ?, state = ? WHERE userName = ?");
    $stmt->bind_param("sss", $city, $state, $display);

    if ($stmt->execute()) {
        // Successfully updated the location
        echo json_encode([
            'success' => true,
            'message' => 'Location saved successfully',
            'city' => $city,
            'state' => $state
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update location'
        ]);
    }

    $stmt->close();
} else {
    // If city or state is missing in POST data
    echo json_encode([
        'success' => false,
        'message' => 'City or state not provided'
    ]);
}

// If the user is not logged in or session is invalid, we already checked it at the start
?>
