

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Search</title>
    <link rel="stylesheet" href="find.css">
    <script src="script.js" defer></script>
</head>
<body>
<div class="top" style="position: absolute; top: 0; left: 0; width: 100%; text-align: center;">
    <a href="index.php" style="text-decoration:none;
    ">Back</a>
</div>
    <nav class="navbar">
        <h1>Search Users</h1>
    </nav>
    
    <div class="search-container">
        <form action="find.php" method = "get">
        <input type="text" id="searchInput" placeholder="Search users..." name = "find">
        <button onclick="searchUsers()">Search</button>
        </form>
    </div>
    
    <div class="results-container" id="resultsContainer">
        <!-- Search results will be displayed here -->
        <?php
        include 'presets/conn.php'; // Database connection

        if (isset($_GET['find'])) {
            $searchQuery = '%' . $_GET['find'] . '%';
            if($searchQuery == "%%"){
                exit();
            }else{
                $stmt = $conn->prepare("SELECT userName FROM userdata WHERE userName LIKE ? LIMIT 10");
                $stmt->bind_param("s", $searchQuery);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $users = [];
                echo '<div style="
            width: 100%;
            max-width: 400px;
            background: rgba(30, 30, 30, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(98, 0, 234, 0.3);
            backdrop-filter: blur(6px);
            margin: 20px auto;
        ">';
    
    while ($row = $result->fetch_assoc()) {
        echo '<form action="oneUser.php" method="get" style="
                display: flex; 
                justify-content: space-between;
                align-items: center; 
                background: rgba(40, 40, 40, 0.9);
                padding: 12px 18px; 
                border-radius: 12px; 
                box-shadow: 0 4px 15px rgba(98, 0, 234, 0.2); 
                transition: 0.3s; 
                backdrop-filter: blur(6px);
                width: 100%; 
                margin-bottom: 10px;
            ">
            
            <!-- Username on the left -->
            <span style="
                color: white;
                font-size: 16px;
                font-weight: bold;
                flex: 1;
            ">' . htmlspecialchars($row["userName"]) . '</span>
    
            <input type="hidden" name="user" value="' . htmlspecialchars($row["userName"]) . '"> 
            
            <!-- View button on the right -->
            <button type="submit" style="
                background: #6200ea; 
                color: white; 
                padding: 8px 16px; 
                border: none; 
                border-radius: 20px; 
                font-size: 14px; 
                cursor: pointer; 
                transition: background 0.3s, transform 0.2s; 
                text-align: center;
                font-weight: bold;">
                View
            </button>
        </form>';
    }
    
    echo '</div>'; // Close the container div
            }
        }
        ?>
    </div>
    

</body>
</html>
