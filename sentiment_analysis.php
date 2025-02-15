<?php
require 'presets/conn.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
    header("Content-Type: application/json");

    $user = $_SESSION['userName'];
    $comment = htmlspecialchars($_POST['comment']);
    $event_id = intval($_POST['event_id']);
    $current_time = date("Y-m-d H:i:s");

    // Sentiment Analysis API
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.apilayer.com/sentiment/analysis",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain",
            "apikey: iaL0CxTFiOOr9ZxNSdLszDZjevMm5Wpl"
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $comment
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response === false) {
        echo json_encode(["success" => false, "error" => "API request failed."]);
        exit;
    }

    $sentiment_data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["success" => false, "error" => "Invalid JSON response from API."]);
        exit;
    }

    $sentiment = $sentiment_data['sentiment'] ?? "neutral";

    // Insert comment into database
    $stmt = $conn->prepare("INSERT INTO comments (event_id, userName, comment_text, sentiment, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $event_id, $user, $comment, $sentiment, $current_time);
    
    if ($stmt->execute()) {
        $stmt->close();

        // Update event sentiment counts
        $stmt = $conn->prepare("
            UPDATE events
            SET positive_count = IFNULL(positive_count, 0) + IF(? = 'positive', 1, 0),
                negative_count = IFNULL(negative_count, 0) + IF(? = 'negative', 1, 0),
                neutral_count = IFNULL(neutral_count, 0) + IF(? = 'neutral', 1, 0)
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $sentiment, $sentiment, $sentiment, $event_id);
        $stmt->execute();
        $stmt->close();

        // Get updated sentiment counts
        $stmt = $conn->prepare("SELECT IFNULL(positive_count, 0), IFNULL(negative_count, 0), IFNULL(neutral_count, 0) FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->bind_result($positive_count, $negative_count, $neutral_count);
        $stmt->fetch();
        $stmt->close();

        // Determine overall sentiment summary
        $summary = "neutral"; // Default
        if ($positive_count > $negative_count) {
            $summary = "positive";
        } elseif ($negative_count > $positive_count) {
            $summary = "negative";
        }

        // Update sentiment summary in events table
        $stmt = $conn->prepare("UPDATE events SET sentiment_summary = ? WHERE id = ?");
        $stmt->bind_param("si", $summary, $event_id);
        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "error" => "Failed to update sentiment summary."]);
            exit;
        }
        $stmt->close();

        echo json_encode(["success" => true, "summary" => $summary]);
    } else {
        echo json_encode(["success" => false, "error" => "Error posting comment."]);
    }

    $conn->close();
    exit;
}
?>
