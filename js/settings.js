document.addEventListener("DOMContentLoaded", function() {
    const profilePreview = document.getElementById("profilePreview");
    const imageModal = document.getElementById("imageModal");
    const modalImage = document.getElementById("modalImage");
    const uploadInput = document.getElementById("upload_profile_picture");
    const deleteButton = document.querySelector(".delete__profile-picture");
    const deleteFlag = document.getElementById("delete_profile_picture");
    const saveModal = document.getElementById("saveModal");
    const closeModalBtn = document.querySelector(".close-modal-btn");

    // Handle image click to show modal
    profilePreview.addEventListener("click", function() {
        imageModal.style.display = "flex";
        modalImage.src = this.src;
    });

    // Handle close button for image modal
    document.querySelector(".close-modal").addEventListener("click", function() {
        imageModal.style.display = "none";
    });

    // Handle profile picture upload preview
    uploadInput.addEventListener("change", function(event) {
        let reader = new FileReader();
        reader.onload = function() {
            profilePreview.src = reader.result;
            deleteFlag.value = "0"; // Reset delete flag if new picture is uploaded
        };
        reader.readAsDataURL(event.target.files[0]);
    });

    // Handle profile picture deletion
    deleteButton.addEventListener("click", function(event) {
        event.preventDefault(); // Prevent form submission
        profilePreview.src = "assets/uploads.bak/default.png"; // Show default image
        deleteFlag.value = "1"; // Mark for deletion
    });

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