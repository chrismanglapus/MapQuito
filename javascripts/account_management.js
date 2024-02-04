function confirmDelete(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                if (this.responseText.trim() === "success") {
                    alert("User deleted successfully.");
                    // Reload the page to reflect the changes
                    location.reload();
                } else {
                    alert("Error deleting user.");
                }
            }
        };

        // Send a request to delete_user.php with user ID
        xmlhttp.open("GET", 'delete_user.php?user_id=' + userId, true);
        xmlhttp.send();
    }

    // Prevent the default behavior of the link
    return false;
}

function toggleUserStatus(userId, currentStatus) {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            // Update the page or provide feedback as needed
            alert(this.responseText);
            // Reload the page to reflect the changes
            location.reload();
        }
    };

    // Send a request to the server to toggle user status
    xmlhttp.open("GET", "toggle_user_status.php?userId=" + userId +
        "&currentStatus=" + currentStatus, true);
    xmlhttp.send();
}