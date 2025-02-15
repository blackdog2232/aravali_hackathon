// Event listener for the parent container
document.getElementById('friends-container').addEventListener('click', function(event) {
    let sender = document.getElementById('sender').value;

    // Check if the clicked element is a button with the class 'open-chat-btn'
    if (event.target && event.target.classList.contains('open-chat-btn')) {
        // Find the closest parent div that contains the h3 tag
        const activityDiv = event.target.closest('.activity');
        const friendName = activityDiv.querySelector('h3').innerText; // Get the friend's name from the h3 tag
        console.log('Opening chat for: ' + friendName);

        // Verify friendship before opening chat
        verifyFriendship(sender, friendName).then(isFriend => {
            if (isFriend) {
                // Open the chat modal and set the sender/receiver values
                document.getElementById('chat-modal').style.display = 'flex';
                document.getElementById('custom-navbar-unique').style.display = 'none';
                
                document.getElementById("sender").value = sender;  // Set sender as current user's username
                document.getElementById("receiver").value = friendName; // Set receiver as friend's username
        
                // Load messages for the current chat
                loadMessages();
            } else {
                alert("You can only chat with your friends.");
            }
        }).catch(error => {
            console.error("Error in friendship verification:", error);
            alert("An error occurred while verifying friendship.");
        });
    }
});

function verifyFriendship(sender, receiver) {
    return new Promise((resolve, reject) => {
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "verify_friendship.php?sender=" + sender + "&receiver=" + receiver, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                // Strip any unwanted whitespace and compare to "true"
                resolve(xhr.responseText.trim() === "true");
            } else {
                reject("Error verifying friendship.");
            }
        };
        xhr.send();
    });
}

// Close button functionality
document.getElementById('close-btn').onclick = function() {
    document.getElementById('chat-modal').style.display = 'none';
    document.getElementById('custom-navbar-unique').style.display = 'flex';
};

// Load messages for the current sender/receiver
function loadMessages() {
    let sender = document.getElementById("sender").value;
    let receiver = document.getElementById("receiver").value;

    if (sender === "" || receiver === "") return;

    // Verify friendship before loading messages
    verifyFriendship(sender, receiver).then(isFriend => {
        if (isFriend) {
            let xhr = new XMLHttpRequest();
            xhr.open("GET", "get_messages.php?sender=" + sender + "&receiver=" + receiver, true);
            xhr.onload = function () {
                document.getElementById("chat-box").innerHTML = this.responseText;
            };
            xhr.send();
        } else {
            alert("You can only load messages with friends.");
        }
    }).catch(error => {
        console.error("Error in friendship verification:", error);
        alert("An error occurred while verifying friendship.");
    });
}

// Send message functionality
function sendMessage() {
    let sender = document.getElementById("sender").value;
    let receiver = document.getElementById("receiver").value;
    let message = document.getElementById("message").value;

    if (sender === "" || receiver === "" || message === "") return;

    // Verify friendship before sending message
    verifyFriendship(sender, receiver).then(isFriend => {
        if (isFriend) {
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "send_message.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                document.getElementById("message").value = "";
                loadMessages();
            };
            xhr.send("sender=" + sender + "&receiver=" + receiver + "&message=" + message);
        } else {
            alert("You can only send messages to your friends.");
        }
    }).catch(error => {
        console.error("Error in friendship verification:", error);
        alert("An error occurred while verifying friendship.");
    });
}

// Refresh chat every 1 second
setInterval(loadMessages, 1000);
