const toggleButton = document.getElementById('toggle-btn');
const sidebar = document.getElementById('sidebar');
const chartMain = document.getElementById('chartMain'); // Ensure this exists
const map = document.getElementById('map'); // Ensure this exists
const infoContainer = document.getElementById('infoContainer'); // Ensure this exists
const thresholdContainer = document.getElementById('thresholdContainer'); // Ensure this exists
const barangayList = document.getElementById('barangayList'); // Ensure this exists
const barangayData = document.getElementById('barangayData'); // Ensure this exists
const manageData = document.getElementById('manageData'); // Ensure this exists
const settings = document.getElementById('settings'); // Ensure this exists


function toggleSidebar() {
    sidebar.classList.toggle('close');
    toggleButton.classList.toggle('rotate');

    closeAllSubMenus();

    if (chartMain) chartMain.classList.toggle('close'); // Prevents errors if undefined
    if (map) map.classList.toggle('close');
    if (infoContainer) infoContainer.classList.toggle('close');
    if (thresholdContainer) thresholdContainer.classList.toggle('close');
    if (barangayList) barangayList.classList.toggle('close');
    if (barangayData) barangayData.classList.toggle('close');
    if (manageData) manageData.classList.toggle('close');
    if (settings) settings.classList.toggle('close');

}

function toggleSubMenu(button) {
    if (!button.nextElementSibling.classList.contains('show')) {
        closeAllSubMenus();
    }

    button.nextElementSibling.classList.toggle('show');
    button.classList.toggle('rotate');

    if (sidebar.classList.contains('close')) {
        sidebar.classList.toggle('close');
        toggleButton.classList.toggle('rotate');

        if (chartMain) chartMain.classList.toggle('close');
        if (map) map.classList.toggle('close');
        if (infoContainer) infoContainer.classList.toggle('close');
        if (thresholdContainer) thresholdContainer.classList.toggle('close');
        if (barangayList) barangayList.classList.toggle('close');
        if (barangayData) barangayData.classList.toggle('close');
        if (manageData) manageData.classList.toggle('close');
        if (settings) settings.classList.toggle('close');

    }
}

function closeAllSubMenus() {
    Array.from(sidebar.getElementsByClassName('show')).forEach(uL => {
        uL.classList.remove('show');
        uL.previousElementSibling.classList.remove('rotate');
    });
}
