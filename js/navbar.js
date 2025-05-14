function updateURL() {
    var selectedYear = document.getElementById('selectedYear').value;
    var selectedWeek = document.getElementById('selectedWeek').value;
    window.location.href = '?selected_year=' + selectedYear + '&selected_week=' + selectedWeek;
}

document.addEventListener("DOMContentLoaded", function () {
    const loginButton = document.getElementById("new-login-btn");
    const loginModal = document.getElementById("loginModal");

    // Add event listener only if the login button exists
    if (loginButton) {
        loginButton.addEventListener("click", function () {
            if (loginModal) { // Ensure modal exists before manipulating it
                loginModal.style.display = "flex"; // Set display before animation starts
                setTimeout(() => {
                    loginModal.classList.add("show");
                }, 10); // Small delay to allow display update
            }
        });
    }

    // Add event listener only if the loginModal exists
    if (loginModal) {
        loginModal.addEventListener("click", function (event) {
            if (event.target === this) {
                closeLoginModal();
            }
        });
    }

    // Check if loginModal exists before adding window.onclick listener
    if (loginModal) {
        window.onclick = function (event) {
            if (event.target === loginModal) {
                closeLoginModal();
            }
        };
    }
});


function openLoginModal() {
    let modal = document.getElementById("loginModal");
    modal.classList.add("show");
}

function closeLoginModal() {
    let modal = document.getElementById("loginModal");
    modal.classList.remove("show");
}

window.onclick = function (event) {
    var modal = document.getElementById("loginModal");
    if (event.target === modal) {
        closeLoginModal();
    }
};

function showToast(message, type) {
    var toast = document.getElementById("toast");
    toast.innerText = message;
    toast.className = "toast show " + (type === "success" ? "toast-success" : "toast-error");
    setTimeout(() => {
        toast.className = toast.className.replace("show", "");
    }, 3000);
}