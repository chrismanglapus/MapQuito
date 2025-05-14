function openEditModal(caseId) {
    document.body.classList.add("modal-active");

    // Fetch case details via AJAX
    $.ajax({
        url: 'barangay_data__fetch_case.php',
        type: 'POST',
        data: {
            case_id: caseId
        },
        dataType: 'json',
        success: function(data) {
            $('#edit-case-id').val(data.id);
            $('#edit-cases').val(data.cases);

            // Dynamically update the modal title with the morbidity week
            $('#edit-modal-title').text("Editing Week " + data.morbidity_week);

            $('#edit-modal').show();
            $('#modal-overlay').show();
        }
    });
}

function closeEditModal() {
    document.body.classList.remove("modal-active");
    $('#edit-modal').hide();
    $('#modal-overlay').hide();
}

// Handle form submission
$('#edit-form').submit(function(e) {
    e.preventDefault();

    $.ajax({
        url: 'barangay_data__update_case.php',
        type: 'POST',
        data: $('#edit-form').serialize(),
        success: function(response) {
            location.reload(); // Reload page to show updated data
        }
    });
});

function backBtn(year) {
    const url = `manage_data__barangay_list.php?year=${year}`;
    window.location.href = url;
}