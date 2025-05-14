document.getElementById("selectAll")?.addEventListener("click", function() {
    let checkboxes = document.querySelectorAll("input[name='selected_barangays[]']");
    checkboxes.forEach(cb => cb.checked = this.checked);
});

document.getElementById("deleteForm")?.addEventListener("submit", function(event) {
    let checkboxes = document.querySelectorAll("input[name='selected_barangays[]']:checked");
    let totalCheckboxes = document.querySelectorAll("input[name='selected_barangays[]']").length;

    let deleteAllBtnClicked = event.submitter && event.submitter.name === "deleteAll";
    let deleteSelectedBtnClicked = event.submitter && event.submitter.name === "deleteSelected";

    // 🚨 Confirm when deleting ALL
    if (deleteAllBtnClicked) {
        let userInput = prompt("Type 'CONFIRM' to delete all barangays for this year:");
        if (userInput !== "CONFIRM") {
            alert("Deletion cancelled. Type 'CONFIRM' exactly to proceed.");
            event.preventDefault();
        } else {
            setTimeout(() => {
                window.location.href = "manage_data.php";
            }, 500);
        }
    } 
    // ✅ Confirm if all options are selected in "Delete Selected"
    else if (deleteSelectedBtnClicked) {
        if (checkboxes.length === 0) {
            alert("Please select at least one barangay before deleting.");
            event.preventDefault();
        } else if (checkboxes.length === totalCheckboxes) {
            let userInput = prompt("You selected ALL barangays. Type 'CONFIRM' to proceed with deletion:");
            if (userInput !== "CONFIRM") {
                alert("Deletion cancelled. Type 'CONFIRM' exactly to proceed.");
                event.preventDefault();
            }
        } else {
            setTimeout(() => {
                window.location.href = "manage_data.php";
            }, 500);
        }
    }
});

function showData(barangay, year) {
    const url = `manage_data__barangay_data.php?barangay=${barangay}&year=${year}`;
    window.location.href = url;
}

// JavaScript to add/remove the 'selected-row' class when a checkbox is clicked
document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const row = this.closest('tr');
        if (this.checked) {
            row.classList.add('selected-row');
        } else {
            row.classList.remove('selected-row');
        }
    });
});
