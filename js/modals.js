document.addEventListener("DOMContentLoaded", function () {
    var ctaButton = document.querySelector(".cta-btn");
    var modalDetails = document.getElementById("cta-details");
    var modalInfo = document.getElementById("modal-info");
  
    var closeCtaModal = document.getElementById("close-cta-modal");
    var closeInfoModal = document.getElementById("close-info-modal");
  
    function updateDengueInsight() {
      const currentWeek = parseInt(document.getElementById("selectedWeek").value);
      const currentCases = totalCasesYearData[selectedYear]?.[currentWeek] || 0;
      const message = checkDengueRisk(selectedYear, currentWeek, currentCases);
      document.getElementById("modal-text").innerHTML = message;
    }
  
    ctaButton.addEventListener("click", function () {
      updateDengueInsight();
      modalDetails.style.display = "flex";
    });
  
    // Close the CTA modal
    closeCtaModal.addEventListener("click", function () {
      modalDetails.style.display = "none";
    });
  
    // Close the Barangay Info modal
    closeInfoModal.addEventListener("click", function () {
      modalInfo.style.display = "none";
    });
  
    // Close modals when clicking outside
    window.addEventListener("click", function (event) {
      if (event.target === modalDetails) {
        modalDetails.style.display = "none";
      }
      if (event.target === modalInfo) {
        modalInfo.style.display = "none";
      }
    });
  });
  