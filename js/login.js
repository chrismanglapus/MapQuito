document.getElementById("login-form").addEventListener("submit", function (event) {
    event.preventDefault();

    var formData = new FormData(this);

    fetch("login__process_login.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            showToast(data.message, data.status);

            if (data.status === "success") {
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        })
        .catch(error => console.error("Error:", error));
});