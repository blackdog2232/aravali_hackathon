// Toggle Profile Details visibility on mobile
function toggleProfile() {
    const profileDetailsPopup = document.getElementById('profileDetailsPopup');
    const importantThingsPopup = document.getElementById('importantThingsPopup');
    const blurBackground = document.getElementById('blurBackground');
    fetchProfileData()
    // Toggle visibility of Profile
    if (profileDetailsPopup.style.display === 'none' || profileDetailsPopup.style.display === '') {
        profileDetailsPopup.style.display = 'block';
        importantThingsPopup.style.display = 'none'; // Hide Important Things
        blurBackground.style.display = 'block'; // Show background blur
        fetchProfileData()
    } else {
        profileDetailsPopup.style.display = 'none';
        blurBackground.style.display = 'none'; // Hide background blur
    }
}

// Toggle Important Things visibility on mobile
function toggleImportantThings() {
    const profileDetailsPopup = document.getElementById('profileDetailsPopup');
    const importantThingsPopup = document.getElementById('importantThingsPopup');
    const blurBackground = document.getElementById('blurBackground');

    // Toggle visibility of Important Things
    if (importantThingsPopup.style.display === 'none' || importantThingsPopup.style.display === '') {
        importantThingsPopup.style.display = 'block';
        profileDetailsPopup.style.display = 'none'; // Hide Profile Details
        blurBackground.style.display = 'block'; // Show background blur
    } else {
        importantThingsPopup.style.display = 'none';
        blurBackground.style.display = 'none'; // Hide background blur
    }
}

// Close the modals when clicking anywhere outside the modal content



// PROFILE RELATED SCRIPT

    //HANDLES AJAX
    $(document).ready(function () {
    $('#profileForm').submit(function (event) {
        event.preventDefault();  // Prevent form submission (refresh)

        // Disable the submit button to prevent multiple clicks
        $('button[type="submit"]').attr('disabled', 'disabled');

        // Create FormData object from the form
        var formData = new FormData(this);

        // Send the data using AJAX
        $.ajax({
            url: '',  // Same page
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                // Reset the form on successful submission
                $('#profileForm')[0].reset();
                $('#selectedInterests').empty();
                $('#dropdownButton').prop('disabled', false);
                $('#photo').val('');
                $('#fileName').text('');
                // Re-enable the submit button after submission
                $('button[type="submit"]').removeAttr('disabled');
                fetchProfileData();
                document.getElementById("modalBackground").style.display = "none";
                const divToRemove = document.getElementById('proid');
                    // Add the fade-out class to the div to start the animation
                divToRemove.classList.add('fadeout');setTimeout(function() {
                    divToRemove.remove();
                }, 1200); // 500ms matches the duration of the fade-out transition
            },
            error: function () {
                alert('There was an error submitting your form.');
                $('button[type="submit"]').removeAttr('disabled'); // Re-enable the submit button
            }
        });
    });
});

$(document).ready(function() {
    // Call the function when the page is fully loaded
    fetchProfileData();
});

//HANDLES FETCHING 
function fetchProfileData() {
    $.ajax({
        url: 'presets/fetchprofile.php',  // PHP file to fetch profile data
        method: 'POST',  // Using POST
        success: function(response) {
            const data = JSON.parse(response);
            // Assuming data.dob is in the format 'YYYY-MM-DD'
            var dob = new Date(data.dob);
            var currentDate = new Date();
            var age = currentDate.getFullYear() - dob.getFullYear();
            var monthDifference = currentDate.getMonth() - dob.getMonth();
            if (monthDifference < 0 || (monthDifference === 0 && currentDate.getDate() < dob.getDate())) {
                age--;
            }
            // Populate the fields with the fetched data
            $('#userName').text(data.name);
            $('#userGender').text(data.gender);
            $('#userDOB').text(age);
            $('#userBio').text(data.bio);
            $('#userInterests').text(data.interests);
            $('#userNamephone').text(data.name);
            $('#userGenderphone').text(data.gender);
            $('#userDOBphone').text(age);
            $('#userBiophone').text(data.bio);
            $('#userInterestsphone').text(data.interests);
            
            // Set the profile picture
            $('#profilePic').attr('src', data.profilePic);
            $('#profilePicphone').attr('src', data.profilePic);
        },
        error: function() {
            $('#response').html("An error occurred while fetching profile data.");
        }
    });
}



//HANDLES PHOTO SECTIOn
document.getElementById('choosePhotoBtn').addEventListener('click', function() {
    document.getElementById('photo').click();
});

// When a file is selected
document.getElementById('photo').addEventListener('change', function(event) {
    var fileName = event.target.files[0].name;  // Get the selected file's name
    document.getElementById('fileName').textContent = fileName;  // Display the file name
});

    //HANDLES INTEREST SECTION
function selectInterest(interest, event) {
    event.preventDefault(); // Prevent the page from scrolling to the top
    
    // Add the interest to the selected list
    const selectedInterests = document.getElementById('selectedInterests');
    const existingTag = document.getElementById(interest);

    // Avoid duplicate interests
    if (!existingTag) {
        const tag = document.createElement('div');
        tag.classList.add('interest-tag');
        tag.id = interest;

        const textNode = document.createTextNode(interest);
        const removeButton = document.createElement('span');
        removeButton.classList.add('remove');
        removeButton.textContent = '×';
        removeButton.onclick = function () {
            tag.remove();
            updateHiddenInput();
        };

        tag.appendChild(textNode);
        tag.appendChild(removeButton);
        selectedInterests.appendChild(tag);

        updateHiddenInput();
    }
}


        function updateHiddenInput() {
            const selectedInterests = document.getElementById('selectedInterests');
            const selectedTags = selectedInterests.getElementsByClassName('interest-tag');
            const interestsArray = [];

            // Get all selected interests
            for (let tag of selectedTags) {
                interestsArray.push(tag.id);
            }

            // Update the hidden input with selected interests as a comma-separated string
            document.getElementById('interests').value = interestsArray.join(',');
        }

    function togglePlusDropdown() {
        var dropdown = document.getElementById("plusDropdown");
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    function openLocationModal() {
        alert("Open location modal!");
    }

    function openImportantModal() {
        alert("Open important things modal!");
    }

    function toggleDivs() {
        const centerDiv = document.querySelector('.center');
        const newDiv = document.querySelector('.new-div');
    
        // Toggle visibility between the .center and .new-div
        if (centerDiv.classList.contains('transparent')) {
            // If the .center div is transparent, make it opaque and hide .new-div
            centerDiv.classList.remove('transparent');
            newDiv.style.display = 'none'; // Hide new-div
        } else {
            // If the .center div is visible, make it transparent and show .new-div
            centerDiv.classList.add('transparent');
            newDiv.style.display = 'block'; // Show new-div
        }
    }
