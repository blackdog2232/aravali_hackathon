<?php
include "presets/conn.php";
session_start();

if (!isset($_SESSION['userName'])) {
    header("Location: setup.php");
    exit();
}

$userName = $_SESSION['userName'];

// Fetch friends where user is either user_one or user_two
$query = "
    SELECT u.name, u.userName, u.displayPicture 
    FROM friends f
    JOIN userprofile u ON 
        (f.user_one COLLATE utf8mb4_unicode_ci = ? AND f.user_two COLLATE utf8mb4_unicode_ci = u.userName) 
        OR (f.user_two COLLATE utf8mb4_unicode_ci = ? AND f.user_one COLLATE utf8mb4_unicode_ci = u.userName)
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $conn->error); // Debugging in case of errors
}

$stmt->bind_param("ss", $userName, $userName);
$stmt->execute();
$friends = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Friends</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .friend-list { display: flex; flex-wrap: wrap; gap: 15px; }
        .friend-card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; text-align: center; width: 200px; }
        .friend-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        .btn { text-decoration: none; background: #007bff; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 10px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>

<h2>My Friends</h2>

<?php if ($friends->num_rows > 0): ?>
    <div class="friend-list">
        <?php while ($friend = $friends->fetch_assoc()): ?>
            <div class="friend-card">
                <img src="<?= htmlspecialchars($friend['displayPicture'] ?: 'default.png') ?>" alt="Profile" class="friend-img">
                <div class="friend-info">
                    <h3><?= htmlspecialchars($friend['name']) ?></h3>
                    <p>@<?= htmlspecialchars($friend['userName']) ?></p>
                    <a href="oneUser.php?user=<?= urlencode($friend['userName']) ?>" class="btn">View Profile</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p>You have no friends yet.</p>
<?php endif; ?>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
