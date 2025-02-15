<?php
session_start();
include 'conn.php';
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: ../setup.php");
    exit;
}
header('Content-Type: application/json'); // Ensure the response is in JSON format

// Get latitude and longitude from the GET request
$lat = isset($_GET['lat']) ? $_GET['lat'] : null;
$lon = isset($_GET['lon']) ? $_GET['lon'] : null;

if (!$lat || !$lon) {
    echo json_encode(["error" => "Latitude and Longitude are required"]);
    exit;
}

// Replace this with your actual OpenCage API key
$apiKey = "#######################";

// Construct the URL to get location information from OpenCage
$apiUrl = "https://api.opencagedata.com/geocode/v1/json?q=$lat+$lon&key=$apiKey";

// Fetch the location data
$response = file_get_contents($apiUrl);

if ($response === FALSE) {
    echo json_encode(["error" => "Failed to fetch location data from OpenCage API"]);
    exit;
}

// Decode the JSON response from OpenCage
$data = json_decode($response, true);

// Check if location data was returned
if (isset($data['results'][0])) {
    $city = $data['results'][0]['components']['city'] ?? "Unknown City";
    $state = $data['results'][0]['components']['state'] ?? "Unknown State";

    // Return the city and state as a JSON response
    echo json_encode(["city" => $city, "state" => $state]);
} else {
    echo json_encode(["error" => "Unable to retrieve location data"]);
}
?>
