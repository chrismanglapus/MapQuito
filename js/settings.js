document.addEventListener("DOMContentLoaded", function() {
    const deleteFlag = document.getElementById("delete_profile_picture");
    const saveModal = document.getElementById("saveModal");
    const closeModalBtn = document.querySelector(".close-modal-btn");

    if (showModal && !sessionStorage.getItem("modalShown")) {
        sessionStorage.setItem("modalShown", "true");
        saveModal.style.display = "flex";

        // Auto-close modal without reloading on delete
        setTimeout(() => {
            saveModal.style.display = "none";
            sessionStorage.removeItem("modalShown");

            // Reload only if not deleting the profile picture
            if (deleteFlag.value !== "1") {
                window.location.reload();
            }
        }, 2000);
    }

    closeModalBtn.addEventListener("click", function() {
        saveModal.style.display = "none";
        sessionStorage.setItem("modalClosed", "true"); // Prevent modal from reopening
        window.location.href = window.location.pathname; // Reload page
    });

    // Prevent save modal from reopening after it's closed
    if (sessionStorage.getItem("modalClosed") === "true" && !showModal) {
        saveModal.style.display = "none"; // Ensure it's hidden
    }
});