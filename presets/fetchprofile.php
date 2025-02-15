<?php
session_start();
include 'conn.php';
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: ../setup.php");
    exit;
}
$display = $_SESSION['userName'];
if (isset($display)) {
    $userID = $display; // Use the user ID sent from the AJAX request
} else {
    // If the user ID is not set in POST, return an error
    echo json_encode(['error' => 'User not provided']);
    exit();
}
include 'conn.php';
// Fetch user profile data from the database using the user ID
$sql = "SELECT name, gender, bio,DOB, interests, displayPicture FROM userprofile WHERE userName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userID); // Bind the user ID parameter
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the profile data
    $row = $result->fetch_assoc();
    
    // Prepare the profile data to send back as a JSON response
    $profileData = [
        'name' => $row['name'],
        'gender' => $row['gender'],
        'dob' => $row['DOB'],
        'bio' => $row['bio'],
        'interests' => $row['interests'],
        'profilePic' => $row['displayPicture'] // Assuming profile_pic is a file path
    ];
    
    // Send the data as a JSON object
    echo json_encode($profileData);
} else {
    // If no profile data is found, return an error
    echo json_encode(['error' => 'Profile not found.']);
}

$conn->close();
?>
