<?php
    $login_stat = false;
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include "presets/conn.php"; // Ensure this file exists and connects to the database

    if (isset($_POST['signUp_Login'])) {
        // Sign-Up Logic
        if ($_POST['signUp_Login'] === 'signUp') {
            $userName = mysqli_real_escape_string($conn, $_POST['userName']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = mysqli_real_escape_string($conn, $_POST['password']);

            if(empty($userName)|| empty($email) || empty($password)){
                echo "Fields are empty";
            }
            else{
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $checkQuery = "SELECT * FROM `userdata` WHERE `userName` = ? OR `email` = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param("ss", $userName, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "Username or email is already taken. Please try a different one.";
                } else {
                    $insertQuery = "INSERT INTO `userdata` (`userName`, `passWord`, `email`,`tag`, `token`, `DandT`) VALUES (?, ?, ?,?, ?, current_timestamp())";
                    $stmt = $conn->prepare($insertQuery);
                    $token = rand();
                    $blank_val = "";
                    $tagger = "new";
                    $stmt->bind_param("sssss", $userName, $hashedPassword, $email,$tagger, $token);
                    $inserttwoQuery = "INSERT INTO `userprofile`(`userName`, `name`, `gender`, `bio`, `DOB`, `interests`, `displayPicture`) VALUES (?,?,?,?,?,?,?)";
                    $stmtTwo = $conn->prepare($inserttwoQuery);
                    $stmtTwo->bind_param("sssssss",$userName,$blank_val,$blank_val,$blank_val,$blank_val,$blank_val,$blank_val);
                    if ($stmt->execute() && $stmtTwo->execute()) {
                        echo "Sign-Up Successful!";
                    } else {
                        echo "Sign-Up Failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
                $conn->close();
            }
        }
        // Login Logic
        elseif ($_POST['signUp_Login'] === 'Login') {
            $userName = mysqli_real_escape_string($conn, $_POST['userName']);
            $password = $_POST['passWord'];

            $sql = "SELECT password FROM `userdata` WHERE `userName` = '$userName'";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                // $user_id = $row['user_id'];
                $hashedPassword = $row['password'];

                if (password_verify($password, $hashedPassword)) {
                    session_start(); // Start the session
                
                    // Set session variables
                    $_SESSION['loginID'] = true;
                    $_SESSION['userName'] = $userName;
                    $conn->close();
                    header("Location: index.php");
                    exit();
                } else {
                    // Handle invalid login
                    $login_stat = true;
                    $show_error =  "Invalid credentials";
                }
                
            } else {
                $login_stat = true;
                $show_error =  "User not found";
            }
        } else {
            $login_stat = true;
            $show_error =  "Invalid Request!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup</title>
    <link rel="stylesheet" href="setup.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <?php
    if($login_stat){
        ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $show_error;?>
</div>
        <?php
    }
    ?>
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- Login Form -->
    <form method="POST" id="login-form" action="setup.php">
        <h3>Log In</h3>

        <label for="userName">Username</label>
        <input type="text" placeholder="Username" name="userName" id="userName" required>

        <label for="passWord">Password</label>
        <input type="password" placeholder="Password" name="passWord" id="passWord" required>
        
        <input type="hidden" name="signUp_Login" value="Login">

        <button type="submit" name="action" value="login">Log In</button>

        <a href="javascript:void(0);" class="toggle-link" onclick="toggleForm('signup')" style="text-decoration:none;">Don't have an account? Sign Up</a>
    </form>

    <!-- Sign Up Form -->
    <form method="POST" id="signup-form" action = "setup.php">
        <h3>Sign Up</h3>
        <label for="email">Email</label>
        <input type="email" placeholder="Email" name="email" required>

        <label for="userName">Username</label>
        <input type="text" placeholder="Username" name="userName" required>

        <label for="password">Password</label>
        <input type="password" placeholder="Password" name="password" required>
        <input type="hidden" name="signUp_Login" value="signUp">
        <button type="submit" name="action" value="signup">Sign Up</button>

        <a href="javascript:void(0);" class="toggle-link" onclick="toggleForm('login')" style="text-decoration:none;">Already have an account? Log In</a>
    </form>

    <script>
        // Function to toggle between Login and SignUp forms
        function toggleForm(formType) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const loginToggleButton = document.getElementById('login-toggle');
            const signupToggleButton = document.getElementById('signup-toggle');

            if (formType === 'signup') {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                loginToggleButton.classList.remove('active');
                signupToggleButton.classList.add('active');
            } else {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                signupToggleButton.classList.remove('active');
                loginToggleButton.classList.add('active');
            }
        }

        // Initialize form visibility
        document.getElementById('login-form').style.display = 'block';
        document.getElementById('signup-form').style.display = 'none';
    </script>
</body>
</html>
