// Manage Modals
const addDataBtn = document.getElementById('addDataBtn');
const addDataModal = document.getElementById('addDataModal');
const closeAddDataModal = document.getElementById('closeAddDataModal');

const exportBtn = document.getElementById('exportBtn');
const exportModal = document.getElementById('exportModal');
const closeModal = document.getElementById('closeModal');

const modalOverlay = document.getElementById('modalOverlay');

// Function to show modal
function showModal(modal) {
    modal.style.display = 'block';
    modalOverlay.style.display = 'block';
}

// Function to hide modal
function hideModal(modal) {
    modal.style.display = 'none';
    modalOverlay.style.display = 'none';
}

// Event listeners for showing and hiding modals
addDataBtn.addEventListener('click', () => showModal(addDataModal));
closeAddDataModal.addEventListener('click', () => hideModal(addDataModal));

exportBtn.addEventListener('click', () => showModal(exportModal));
closeModal.addEventListener('click', () => hideModal(exportModal));

// Close only the visible modal when clicking outside
modalOverlay.addEventListener('click', () => {
    if (addDataModal.style.display === 'block') hideModal(addDataModal);
    if (exportModal.style.display === 'block') hideModal(exportModal);
});

// Set the default year to the current year
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById('dataYear').value = new Date().getFullYear();
});

// Export data with year validation
document.getElementById('confirmExport').addEventListener('click', function() {
    let selectedYear = document.getElementById('exportYear').value;
    if (!selectedYear) {
        alert("Please select a year.");
        return;
    }
    window.location.href = 'manage_data__export.php?year=' + selectedYear;
});

// Disable/Enable all buttons
function toggleButtons(disable) {
    const buttons = document.querySelectorAll('button, input[type="submit"]');
    buttons.forEach(btn => {
        if (!btn.classList.contains('modal-close-btn')) {
            btn.disabled = disable;
        }
    });
}

// Handle form submission with status feedback
document.getElementById('addDataForm').addEventListener('submit', function(event) {
    event.preventDefault();

    let formData = new FormData(this);
    let submitButton = document.querySelector('#addDataForm button[type="submit"]');
    let textSpan = submitButton.querySelector('.text'); 
    let statusElement = document.getElementById('uploadStatus');

    toggleButtons(true);
    submitButton.disabled = true;
    if (textSpan) textSpan.textContent = "Uploading...";
    statusElement.textContent = "Uploading, please wait...";
    statusElement.style.color = 'blue';

    fetch('upload_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            statusElement.textContent = data.message;
            statusElement.style.color = data.status === "success" ? 'green' : 'red';

            if (data.status === "success") {
                addYearButton(data.year);
                document.getElementById('addDataForm').reset();
                setTimeout(() => hideModal(addDataModal), 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusElement.textContent = "An error occurred. Please try again.";
            statusElement.style.color = 'red';
        })
        .finally(() => {
            if (textSpan) textSpan.textContent = "Confirm";
            submitButton.disabled = false;
            toggleButtons(false);
        });
});

// Function to add a new year button in descending order
function addYearButton(year) {
    let yearContainer = document.querySelector('.year-container');
    let newButton = document.createElement('form');
    newButton.method = 'get';
    newButton.action = 'manage_data__barangay_list.php';
    newButton.innerHTML = `
        <input type='hidden' name='year' value='${year}'>
        <button type='submit' class='year-btn'>${year}</button>
    `;

    let inserted = false;
    let buttons = yearContainer.children;
    for (let i = 0; i < buttons.length; i++) {
        let existingYear = parseInt(buttons[i].querySelector('input[name="year"]').value, 10);
        if (year > existingYear) {
            yearContainer.insertBefore(newButton, buttons[i]);
            inserted = true;
            break;
        }
    }
    if (!inserted) yearContainer.appendChild(newButton);
}

// Clear upload status on modal close
closeAddDataModal.addEventListener('click', () => {
    document.getElementById('uploadStatus').textContent = '';
});

const editPopulationBtn = document.getElementById('editPopulationBtn');
const editPopulationModal = document.getElementById('editPopulationModal');
const closeEditPopulationModal = document.getElementById('closeEditPopulationModal');

// Show and hide edit population modal
editPopulationBtn.addEventListener('click', () => showModal(editPopulationModal));
closeEditPopulationModal.addEventListener('click', () => hideModal(editPopulationModal));

// Update overlay click to include the new modal
modalOverlay.addEventListener('click', () => {
    if (addDataModal.style.display === 'block') hideModal(addDataModal);
    if (exportModal.style.display === 'block') hideModal(exportModal);
    if (editPopulationModal.style.display === 'block') hideModal(editPopulationModal);
});
