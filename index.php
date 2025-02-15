<?php
session_start();
include 'presets/conn.php';
if (!isset($_SESSION['loginID']) || $_SESSION['loginID'] != true) {
    header("location: setup.php");
    exit;
}

$display = $_SESSION['userName'];

if (!isset($_SESSION['userName'])) {
    die("You must be logged in to update your profile.");
}

$display = $_SESSION['userName'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $rName = trim($_POST['name']);
    $rGender = trim($_POST['gender']);
    $rDOB = trim($_POST['dob']);
    $rBio = trim($_POST['bio']);
    $rInterests = $_POST['interests'];

    // Check if a file was uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {

        $fileName = $_FILES['photo']['name'];
        $fileTmpName = $_FILES['photo']['tmp_name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];

        $target_dir = "uploads/";
        $target_file = $target_dir . basename($fileName);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is an image
        $check = getimagesize($fileTmpName);
        if ($check !== false) {
            if ($imageFileType == "jpg" || $imageFileType == "jpeg" || $imageFileType == "png" || $imageFileType == "gif") {

                if (move_uploaded_file($fileTmpName, $target_file)) {
                    $photoPath = $target_file;

                    // Update profile photo in database
                    $sql = "UPDATE userprofile SET displayPicture = ? WHERE userName = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $photoPath, $display);
                    $stmt->execute();
                } else {
                    echo "Sorry, there was an error uploading your file.";
                }
            } else {
                echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            }
        } else {
            echo "File is not an image.";
        }
    }

    // Fetch existing data from the database
    $stmt = $conn->prepare("SELECT * FROM userprofile WHERE userName = ?");
    $stmt->bind_param("s", $display);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingData = $result->fetch_assoc();
    $stmt->close();

    if (!$existingData) {
        die("User profile not found.");
    }

    // Use existing data if a field is empty
    $finalName = !empty($rName) ? $rName : $existingData['name'];
    $finalGender = !empty($rGender) ? $rGender : $existingData['gender'];
    $finalDOB = !empty($rDOB) ? $rDOB : $existingData['DOB'];
    $finalBio = !empty($rBio) ? $rBio : $existingData['bio'];
    $finalInterests = !empty($rInterests) ? $rInterests : $existingData['interests'];

    // Update query
    $stmt = $conn->prepare("UPDATE userprofile 
                            SET name = ?, gender = ?, bio = ?, DOB = ?, interests = ? 
                            WHERE userName = ?");
    $stmt->bind_param("ssssss", $finalName, $finalGender, $finalBio, $finalDOB, $finalInterests, $display);

    if ($stmt->execute()) {
        $tagger = "lvl1";
        $stmt_two = $conn->prepare("UPDATE `userdata` SET `tag` = ? WHERE `userName` = ?");
        $stmt_two->bind_param("ss", $tagger, $display);
        $stmt_two->execute();
        echo "Profile updated successfully!";
    } else {
        echo "Error updating profile: " . $stmt->error;
    }
}


// EDIT PROFILE

if (isset($_POST['updateData'])) {
    // SQL query to update data
    include 'presets/conn.php';
    $newValue = "edit";
    $userId = $display;
    $sql = "UPDATE userdata SET tag = ? WHERE userName = ?";
    
    // Prepare and bind
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $newValue, $userId);

    // Execute the query
    if ($stmt->execute()) {
        // Redirect back to the same page to refresh it
        header("Location: " . $_SERVER['PHP_SELF']);  // This will refresh the page
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    
}

// Database connection (replace with your actual credentials)
$host = 'localhost';
$db = 'dustour';
$user = 'root';  // Replace with your DB username
$pass = '';  // Replace with your DB password

$query = "SELECT userId FROM userdata WHERE userName = ?";
    
// Prepare and execute the query for the sender username
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $display);
    $stmt->execute();
    $resultSender = $stmt->get_result();
    $senderID = null;

    if ($resultSender->num_rows > 0) {
        $senderRow = $resultSender->fetch_assoc();
        $senderID = $senderRow['userId']; // Get the sender's userID
    }
    $stmt->close();
}
try {
    // Create a new PDO connection to the database
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to get all friends of the user (excluding the user themselves)
    $stmt = $pdo->prepare('
        SELECT u.userID, u.userName, u.email, u.tag
        FROM userdata u
        JOIN friendships f ON (f.userID_1 = u.userID OR f.userID_2 = u.userID)
        WHERE (f.userID_1 = :senderID OR f.userID_2 = :senderID)
          AND u.userID != :senderID
    ');

    // Bind the $senderID to the placeholder in the query
    $stmt->bindParam(':senderID', $senderID, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch results as an associative array
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle any errors during the PDO connection or query
    echo 'Error: ' . $e->getMessage();
}

$userName = $_SESSION['userName'];

// Get all pending friend requests
$stmt = $conn->prepare("SELECT sender FROM friend_request WHERE receiver = ?");
$stmt->bind_param("s", $userName);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
// Get all pending friend requests
$stmtPhone = $conn->prepare("SELECT sender FROM friend_request WHERE receiver = ?");
$stmtPhone->bind_param("s", $userName);
$stmtPhone->execute();
$resultPhone = $stmtPhone->get_result();
$stmtPhone->close();
?>

<!-- Styles for Dropdown -->
<style>
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f1f1f1;
        min-width: 160px;
        box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #ddd;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }


/* Modal styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80%;
    max-width: 600px;
    background-color: rgba(0, 0, 0, 0.9); /* Darker background */
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    z-index: 100;
}

.modal-content {
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    color: white;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
}

.close-btn:hover {
    color: #f39c12;
}

/* Styling for the input fields (city and state) */
.manual-entry {
    display: flex;
    gap: 10px; /* Space between the inputs */
    margin-top: 20px;
}

.manual-entry input {
    padding: 10px;
    font-size: 16px;
    width: 100%;
    max-width: 200px; /* Limit width */
    border-radius: 5px;
    border: 1px solid #ccc;
}

.manual-entry input:focus {
    border-color: #f39c12;
}

/* Button group for Get Location and Save buttons side by side */
.button-group {
    display: flex;
    gap: 10px; /* Space between buttons */
    justify-content: center;
    margin-top: 20px;
}

.button-group button {
    padding: 10px 20px;
    background-color: #f39c12;
    border: none;
    border-radius: 5px;
    color: white;
    font-size: 16px;
    cursor: pointer;
}

.button-group button:hover {
    background-color: #e67e22;
}

/* Styling for the current location label */
#currentLocation {
    text-align: center;
    margin-top: 20px;
}

#currentLocation h4 {
    font-size: 18px;
}

#currentLocation p {
    font-size: 16px;
    font-weight: bold;
    color: #f39c12;
}
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="chat.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<!-- Navbar -->
<nav id="navbar" class="navbar">
    <ul>
        <!-- Dropdown placed at the start of the navbar -->
        <li class="dropdown">
                <button type="button" id="logoutBtn"><img src="icons/logo_bg_dastour.png" alt=""></button>
        </li>

        <!-- Profile and Important Things buttons (Only visible on mobile) -->
        <div class="navbar-mobile-buttons">
            <button class="mobile-btn"><img src="icons/logo_bg_dastour.png" alt=""></button>
        </div>

        <!-- Logout Button (Visible on both desktop and mobile) -->
        <li class="navbar-end">
        </li>
    </ul>
</nav>

<body>
    
    <div class="container">
        <!-- Left Side: Profile Details -->
            <div class="profile-view">
        <h2>Profile</h2>
        <div id="profileInfo">
<!-- Dynamically echoing the image source -->
<img id="profilePic" src="<?php echo $POP; ?>" alt="Profile Photo" class="profile-img" />
            <div class="profile-info">
                <p><strong>Name:</strong> <span id="userName"></span></p>
                <a href="myEvents.php"><button type="submit" name="updateData" id="updateBtn" style="width:100%">Events Hosted</button></a>
                <a href="joined.php"><button type="submit" name="updateData" id="updateBtn" style="width:100%">Events Joined</button></a>
            </div>
        </div>
    </div>

<!-- Center: Main Activities -->
<div class="center">
    <h2>Events near you</h2>
    <?php
    if (isset($_SESSION['userName'])) {
        $usr_session_name = $_SESSION['userName']; // Get username from session

        // Query to fetch city and state based on session username
        $usr_query = "SELECT city, state FROM userprofile WHERE userName = ?";
        
        $usr_stmt = mysqli_prepare($conn, $usr_query);
        mysqli_stmt_bind_param($usr_stmt, "s", $usr_session_name);
        mysqli_stmt_execute($usr_stmt);
        
        $usr_result = mysqli_stmt_get_result($usr_stmt);

        if ($usr_row = mysqli_fetch_assoc($usr_result)) {
            $usr_city = $usr_row['city'];
            $usr_state = $usr_row['state'];
        } else {
            echo "User not found.";
        }

        mysqli_stmt_close($usr_stmt);
    } else {
        echo "User is not logged in.";
    }
    $user_city = $usr_city;
    $user_state = $usr_state;

    // Get the user ID
    $usr_query = "SELECT userId FROM userdata WHERE userName = ?";
    $usr_stmt = mysqli_prepare($conn, $usr_query);
    mysqli_stmt_bind_param($usr_stmt, "s", $usr_session_name);
    mysqli_stmt_execute($usr_stmt);
    $usr_result = mysqli_stmt_get_result($usr_stmt);
    $userId = 0;

    if ($usr_row = mysqli_fetch_assoc($usr_result)) {
        $userId = $usr_row['userId'];
    } else {
        echo "User not found.";
    }
    mysqli_stmt_close($usr_stmt);

    // Query to fetch events based on the user's city and state
    $sqlEvent = "SELECT `id`, `host`, `slots`, `event_name`, `event_category`, `event_type`, `event_date`, `event_state`, `event_city`, `address`, `description`, `created_at` 
            FROM `events` 
            WHERE `event_city` = '$user_city' AND `event_state` = '$user_state'";

    // Execute the query
    $resultEvent = mysqli_query($conn, $sqlEvent);

    // Check if there are results
    if (mysqli_num_rows($resultEvent) > 0) {
        echo "<div style='display: flex; flex-direction: column; gap: 20px; padding: 20px;'>"; // Wrapper for all events

        while ($rowEvent = mysqli_fetch_assoc($resultEvent)) {
            // Check if the user has already joined the event
            $checkQuery = "SELECT id FROM event_participants WHERE user_id = ? AND event_id = ?";
            $checkStmt = mysqli_prepare($conn, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, "ii", $userId, $rowEvent['id']);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            $isJoined = mysqli_stmt_num_rows($checkStmt) > 0;
            mysqli_stmt_close($checkStmt);

            echo "<div style='background-color: #2c3e50; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); padding: 20px; width: 100%; box-sizing: border-box; transition: transform 0.3s ease, box-shadow 0.3s ease;'>";

            echo "<h3 style='font-size: 24px; font-weight: bold; color: #ecf0f1; margin-bottom: 10px;'>" . htmlspecialchars($rowEvent['event_name']) . "</h3>";
            
            echo "<a href='oneUser.php?user=" . htmlspecialchars($rowEvent['host']) . "'style='text-decoration:none;'><p style='font-size: 16px; color: #bdc3c7; margin-bottom: 8px;'><strong style='font-weight: bold; color: #ecf0f1;'>Host:</strong> " . htmlspecialchars($rowEvent['host']) . "</p></a>";

            
            echo "<p style='font-size: 16px; color: #bdc3c7; margin-bottom: 8px;'><strong style='font-weight: bold; color: #ecf0f1;'>Category:</strong> " . htmlspecialchars($rowEvent['event_category']) . "</p>";
            
            echo "<p style='font-size: 16px; color: #bdc3c7; margin-bottom: 8px;'><strong style='font-weight: bold; color: #ecf0f1;'>Type:</strong> " . htmlspecialchars($rowEvent['event_type']) . "</p>";
            
            echo "<p style='font-size: 16px; color: #bdc3c7; margin-bottom: 8px;'><strong style='font-weight: bold; color: #ecf0f1;'>Date:</strong> " . htmlspecialchars($rowEvent['event_date']) . "</p>";
            
            echo "<p style='font-size: 16px; color: #bdc3c7; margin-bottom: 8px;'><strong style='font-weight: bold; color: #ecf0f1;'>Location:</strong> " . htmlspecialchars($rowEvent['event_city']) . ", " . htmlspecialchars($rowEvent['event_state']) . "</p>";

            // Buttons for the event actions (Get more, Join/Leave)
            echo "<div style='display: flex; justify-content: flex-end; gap: 15px; margin-top: 15px;'>"; // Right-aligned buttons container
            echo "<a href='showEvent.php?id=" . htmlspecialchars($rowEvent['id']) . "' style='text-decoration:none;'>
            <button style='background-color: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 10px;'> 
                <i class='fa fa-info-circle'></i> know more
            </button>
          </a>";
    

            // Join/Leave button
            echo "<button class='join-btn' data-event-id='" . $rowEvent['id'] . "' style='background-color: " . ($isJoined ? "#e74c3c" : "#27ae60") . "; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 10px;'> 
                    <i class='fa fa-check'></i> " . ($isJoined ? "Joined" : "Join") . "
                  </button>";
            echo "</div>"; // Closing buttons container

            echo "</div>";  // Closing event card
        }

        echo "</div>";  // Closing events container
    } else {
        echo "<p>No events found.</p>";
    }
    ?>
</div>

<!-- Include FontAwesome CDN for the icons -->
<script src="https://kit.fontawesome.com/a076d05399.js"></script>

        <div class="new-div" style="display:none;">
    <h2 style="display: flex; justify-content: space-between; align-items: center;">
        Friends
        <a href="find.php" class="add-friend-btn">+</a>
    </h2>
    <div class="friendsList">
        <div id="friends-container">
            <?php if (count($friends) > 0): ?>
                <ul class="friend-list" style="list-style-type: none; padding: 0;">
                    <?php foreach ($friends as $friend): 
                                $query = "SELECT displayPicture FROM userprofile WHERE userName = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("s", $friend['userName']);
                                $stmt->execute();
                                $stmt->bind_result($profilePicPath);
                                $stmt->fetch();
                                $stmt->close();
                        ?>
                        <div class="activity" style="margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center;">
                                <!-- Circular profile image -->
                                <img src="<?= !empty($profilePicPath) ? $profilePicPath : 'icons/default-profile.jpg'; ?>" alt="Profile Photo" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($friend['userName']); ?></h3>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="oneUser.php?user=<?php echo htmlspecialchars($friend['userName']); ?>"><button style="padding: 5px 10px; background-color:#1749ffa6;">Profile</button></a>
                                <button class="open-chat-btn" style="padding: 5px 10px;">Open Chat</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No friends found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
        <!-- Right Side: Important Things -->
        <div class="right-side">
            <h3>Updates</h3>
            <br>
            <?php while ($row = $result->fetch_assoc()): ?>
    <li style="margin-bottom: 15px; padding: 10px; background-color: #242424; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #ccc;">
        <span style="font-size: 16px; font-weight: bold; color: #f4f4f4;"><?= htmlspecialchars($row['sender']); ?></span>
        <form action="handle_request.php" method="post" style="display: flex; gap: 10px;">
            <input type="hidden" name="sender" value="<?= htmlspecialchars($row['sender']); ?>">
            <input type="hidden" name="receiver" value="<?= htmlspecialchars($userName); ?>">
            <button type="submit" name="action" value="accept" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background-color 0.3s ease;">
                Accept
            </button>
            <button type="submit" name="action" value="reject" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background-color 0.3s ease;">
                Reject
            </button>
        </form>
    </li>
<?php endwhile; ?>

        </div>
        </div>
            <div class="chatList">
            <div id="chat-modal">
        <div id="chat-container">
            <button id="close-btn"></button>
            <h2>Private Chat</h2>
            <input type="text" id="sender" placeholder="Your Name" value="<?php echo $_SESSION['userName']; ?>" disabled hidden>
            <input type="text" id="receiver" placeholder="Receiver's Name" disabled>
            <div id="chat-box"></div>
            <div class="input-container">
                <input type="text" id="message" placeholder="Type a message">
                <button onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>
    <?php
    $display = $_SESSION['userName'];
    $stmt_check = $conn->prepare("SELECT `tag` FROM `userdata` WHERE `userName` = ?");
    $stmt_check->bind_param("s", $display);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        // Bind result to variable
        $stmt_check->bind_result($tag);
        $stmt_check->fetch();
    
        // Check if the tag is 'lvl1'
        if ($tag === "lvl1") {
            //echo "The user has the lvl1 tag.";
        } elseif($tag ==="new") {
            ?>
            <div id="modalBackground" class="modal-background">
            <div class="profile-card" id="proid">
                <!-- Close Button -->
                <span class="close-btn" onclick="closeModal()">&times;</span>
                
                <h2>Create Profile</h2>
                <form id="profileForm" action="profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Form Fields Here -->
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name">
                    </div>
        
                    <div class="form-group-container">
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender">
                                <option value=""></option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth:</label>
                            <input type="date" id="dob" name="dob">
                        </div>
                    </div>
        
                    <!-- Bio Section -->
                    <div class="form-group">
                        <label for="bio">Bio:</label>
                        <textarea id="bio" name="bio" maxlength="250"></textarea>
                    </div>
        
                    <!-- Interests Section -->
                    <div class="form-group">
                        <label for="interests">Interests:</label>
                        <div id="selectedInterests"></div>
                        <div class="dropdown">
                            <button type="button" id="dropdownButton">Select Interest</button>
                            <div class="dropdown-content" id="interestList">
                                <a href="#" onclick="selectInterest('Sports', event)">Sports</a>
                                <a href="#" onclick="selectInterest('Music', event)">Music</a>
                                <a href="#" onclick="selectInterest('Travel', event)">Travel</a>
                                <a href="#" onclick="selectInterest('Technology', event)">Technology</a>
                                <a href="#" onclick="selectInterest('Reading', event)">Reading</a>
                            </div>
                        </div>
                        <input type="hidden" id="interests" name="interests">
                    </div>
        
                    <!-- Profile Photo Section -->
                    <div class="form-group">
                        <label for="photo">Profile Photo:</label>
                        <button type="button" id="choosePhotoBtn">Choose Photo</button>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                        <span id="fileName" style="margin-left: 10px;"></span>
                    </div>
        
                    <button type="submit" id="saveBtn">Save</button>
                </form>
            </div>
        </div>
        <?php
        }
        elseif($tag==="edit"){
            ?>
            <div id="modalBackground" class="modal-background">
            <div class="profile-card" id="proid">
                <!-- Close Button -->
                <span class="close-btn" onclick="closeModal()">&times;</span>
                
                <h2>Edit Profile</h2>
                <form id="profileForm" action="profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Form Fields Here -->
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name">
                    </div>
        
                    <div class="form-group-container">
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender">
                                <option value=""></option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth:</label>
                            <input type="date" id="dob" name="dob">
                        </div>
                    </div>
        
                    <!-- Bio Section -->
                    <div class="form-group">
                        <label for="bio">Bio:</label>
                        <textarea id="bio" name="bio" maxlength="250"></textarea>
                    </div>
        
                    <!-- Interests Section -->
                    <div class="form-group">
                        <label for="interests">Interests:</label>
                        <div id="selectedInterests"></div>
                        <div class="dropdown">
                            <button type="button" id="dropdownButton">Select Interest</button>
                            <div class="dropdown-content" id="interestList">
                                <a href="#" onclick="selectInterest('Sports', event)">Sports</a>
                                <a href="#" onclick="selectInterest('Music', event)">Music</a>
                                <a href="#" onclick="selectInterest('Travel', event)">Travel</a>
                                <a href="#" onclick="selectInterest('Technology', event)">Technology</a>
                                <a href="#" onclick="selectInterest('Reading', event)">Reading</a>
                            </div>
                        </div>
                        <input type="hidden" id="interests" name="interests">
                    </div>
        
                    <!-- Profile Photo Section -->
                    <div class="form-group">
                        <label for="photo">Profile Photo:</label>
                        <button type="button" id="choosePhotoBtn">Choose Photo</button>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                        <span id="fileName" style="margin-left: 10px;"></span>
                    </div>
        
                    <button type="submit" id="saveBtn">Save</button>
                </form>
            </div>
        </div>
        <?php
        }
        else{
            echo "Something went Wrong";
        }
    } else {
        echo "User not found.";
    }
    
    $stmt_check->close();
?>    

    <!-- ONLY FOR PHONE -->

<!-- Pop-up for Profile Details -->
<div id="profileDetailsPopup" class="popup" style="display: none;">
    <h2>Profile Details</h2>
    <div id="profileInfo">
        <img id="profilePicphone" src="<?php echo $POP; ?>" alt="Profile Photo" class="profile-img" />
        <div class="profile-info">
            <p><strong>Name:</strong> <span id="userNamephone"></span></p>
            <p><strong>Gender:</strong> <span id="userGenderphone"></span></p>
            <p><strong>Age:</strong> <span id="userDOBphone"></span></p>
            <p class="bio" style="margin-top: 10px;">
                <strong>Bio:</strong>
                <span id="userBiophone"></span>
            </p>
            <p class="interests"><strong>Interests:</strong> <span id="userInterestsphone"></span></p>
            <form action="index.php" method="POST">
                <button type="submit" name="updateData" id="updateBtn">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModalLoca()">&times;</span>
        
        <h3>Your Location</h3>

        <!-- City and State inputs side by side -->
        <div class="manual-entry">
            <input type="text" id="cityInput" placeholder="City" />
            <input type="text" id="stateInput" placeholder="State" />
        </div>

        <!-- Get Location and Save buttons side by side below inputs -->
        <div class="button-group">
            <button type="button" id="getLocationBtn">Get Location</button>
            <button type="button" id="saveLocationBtn">Save</button>
        </div>

        <!-- Current Location Label -->
        <div id="currentLocation">
            <h4>Current Saved Location:</h4>
            <p id="savedLocation">None</p> <!-- Will be updated with saved location -->
        </div>
    </div>
</div>



<!-- Pop-up for Important Things -->
<div id="importantThingsPopup" class="popup" style="display: none;">
    <h2>Updates</h2>
    <br>
<?php while ($rowPhone = $resultPhone->fetch_assoc()): ?>
    <li style="margin-bottom: 15px; padding: 10px; background-color: #242424; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #ccc;">
        <span style="font-size: 16px; font-weight: bold; color: #f4f4f4;"><?= htmlspecialchars($rowPhone['sender']); ?></span>
        <form action="handle_request.php" method="post" style="display: flex; gap: 10px;">
            <input type="hidden" name="sender" value="<?= htmlspecialchars($rowPhone['sender']); ?>">
            <input type="hidden" name="receiver" value="<?= htmlspecialchars($userName); ?>">
            <button type="submit" name="action" value="accept" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background-color 0.3s ease;">
                Accept
            </button>
            <button type="submit" name="action" value="reject" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background-color 0.3s ease;">
                Reject
            </button>
        </form>
    </li>
<?php endwhile; ?>
</div>

<!-- Full-screen background blur overlay -->
<div id="blurBackground" class="blur-background" style="display: none;"></div>
    <!-- ENDS HERE FOR ONLY PHONE -->

    <!-- Background Blur Overlay -->
    <div id="blurBackground" class="blur-background" style="display: none;"></div>

<!-- BOTTOM NAVIGATION -->
<div class="custom-navbar" id="custom-navbar-unique">
        <!-- Home Button with Dropdown Wrapper -->
        <div class="custom-navbar-dropdown">
            <button class="custom-navbar-item">
                <img src="icons/setting.png" alt="Settings">
                <span>Settings</span>
            </button>

            <!-- Dropdown Content -->
            <div class="custom-navbar-dropdown-menu">
                <a href="" id="locationBtn">Location</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
<button class="custom-navbar-item" onclick="toggleDivs()">
    <img src="icons/friends.png" alt="Friends">
    <span>Friends</span>
</button>
        <!-- Custom Central Add Button (Slim & Responsive) -->
         <a href="postEvents.php">
        <button class="custom-navbar-item custom-navbar-add-btn">
        <i class="fa-solid fa-plus fa-lg"></i>
        </button>
        </a>
        <button class="custom-navbar-item" onclick="toggleProfile()">
            <img src="icons/profile.png" alt="Profile">
            <span>Profile</span>
        </button>

        <button class="custom-navbar-item" onclick="toggleImportantThings()">
            <img src="icons/bell.png" alt="Updates">
            <span >Updates</span>
        </button>
    </div>
<!-- ENDS HERE -->
</body>
</html>
<script>

// JavaScript to show/hide the modal
document.addEventListener("DOMContentLoaded", function() {
    // Show modal if user doesn't have 'lvl1' tag
    if (<?php echo ($tag !== 'lvl1' ? 'true' : 'false'); ?>) {
        document.getElementById("modalBackground").style.display = "flex";
    }
});
// Get modal element
var modal = document.getElementById("myModal");

// Get button to trigger modal (Location)
var locationBtn = document.getElementById("locationBtn");

// Function to open the modal when "Location" is clicked
locationBtn.onclick = function(event) {
    event.preventDefault(); // Prevent the default link behavior
    modal.style.display = "block"; // Show the modal
}

// Function to close the modal
function closeModalLoca() {
    modal.style.display = "none";
}

// Close modal if the user clicks anywhere outside of it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

document.getElementById('getLocationBtn').addEventListener('click', function() {
    // Check if geolocation is supported
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            // Send the lat and lon to the PHP script using AJAX
            fetch(`presets/hello.php?lat=${lat}&lon=${lon}`)
                .then(response => response.json())  // Parse the JSON response
                .then(data => {
                    // Check if the data contains city and state
                    if (data.city && data.state) {
                        // Fill the input fields with the fetched city and state
                        document.getElementById('cityInput').value = data.city;
                        document.getElementById('stateInput').value = data.state;
                    } else {
                        alert("Error fetching location data.");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Error fetching location data.");
                });
        }, function() {
            alert("Location access denied!");
        });
    } else {
        alert("Geolocation is not supported by your browser!");
    }
});

// Save button functionality (using jQuery's $ajax)
$('#saveLocationBtn').on('click', function() {
    const city = $('#cityInput').val();
    const state = $('#stateInput').val();

    if (city && state) {
        // Send the city and state to the PHP script via AJAX ($ajax)
        $.ajax({
            url: 'presets/save_location.php',  // The PHP script URL
            type: 'POST',              // HTTP method (POST)
            data: {
                city: city,            // Data to send to the server
                state: state
            },
            dataType: 'json',          // Expected response data type (JSON)
            success: function(data) {
                if (data.success) {
                    // Successfully saved, update the current location text
                    $('#savedLocation').text(city + ', ' + state);
                } else {
                    alert('Failed to save location: ' + data.message);
                }
            },
            error: function(xhr, status, error) {
                // Try to parse the response as JSON if possible
                try {
                    const response = JSON.parse(xhr.responseText);
                    alert('Error: ' + (response.message || 'Unknown error'));
                } catch (e) {
                    alert('Unexpected error: ' + xhr.responseText);
                }
            }
        });
    } else {
        alert('Please enter both city and state.');
    }
});


// Fetch current location from the database on page load
document.addEventListener('DOMContentLoaded', function() {
    // Fetch the current location for the logged-in user
    fetch('presets/get_location.php')
        .then(response => response.json())
        .then(data => {
            if (data.city && data.state) {
                // Fill the input fields with the city and state from the database
                document.getElementById('savedLocation').innerText = `${data.city}, ${data.state}`;
            } else {
                console.log('Error fetching user location:', data.error);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Error fetching location from the database.");
        });
});

//MODAL REMOVE  HANDLER
window.onclick = function(event) {
    const profileDetailsPopup = document.getElementById('profileDetailsPopup');
    const importantThingsPopup = document.getElementById('importantThingsPopup');
    const blurBackground = document.getElementById('blurBackground');

    if (event.target === blurBackground) {
        profileDetailsPopup.style.display = 'none';
        importantThingsPopup.style.display = 'none';
        blurBackground.style.display = 'none'; // Hide background blur
    }
}

function closeModal() {
    // Close the modal by hiding the modal background and content
    document.getElementById("modalBackground").style.display = "none";
}

// Close the modal when clicking outside of the modal content (optional)
document.getElementById("modalBackground").addEventListener("click", function(e) {
    // If the click is outside the modal content, close the modal
    if (e.target === this) {
        closeModal();
    }
});

    function togglePopup() {
        document.getElementById('popupModal').style.display = 'block';
    }

    function closePopup() {
        document.getElementById('popupModal').style.display = 'none';
    }

    function openLocationModal() {
        alert("Open location modal!");
    }

    function openImportantModal() {
        alert("Open important things modal!");
    }
    <?php 

    ?>
</script>

<script>
    // Handle the "Join/Leave" button click
    $(document).ready(function() {
        $(".join-btn").click(function() {
            var button = $(this);
            var eventId = button.data("event-id");
            var currentText = button.text().trim(); // Get current button text

            // Determine the action based on the button text
            var action = currentText === "Join" ? "join" : "leave";

            $.ajax({
                url: "join_event.php",
                type: "POST",
                data: { event_id: eventId },
                success: function(response) {
                    if (response.trim() === "joined") {
                        button.text("Joined");
                        button.css("background-color", "#e74c3c"); // Change to red for "Leave"
                    } else if (response.trim() === "unjoined") {
                        button.text("Join");
                        button.css("background-color", "#27ae60"); // Change to green for "Join"
                    } else {
                        alert("There was an error.");
                    }
                }
            });
        });
    });
</script>
<script src="chat.js"></script>
<script src="index.js"></script>