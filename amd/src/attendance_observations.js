// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Events for the grading interface.
 * @module     local_asistencia/attendance_observations
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2024 Luis Pérez <lfperezv@sena.edu.co>
 **/

define(["jquery"], function ($) {
  return {
    init: function () {
      // Evento de delegación para manejar elementos select con una clase específica
      $("body").on("change", ".form-select", function () {
        const selectedValue = $(this).val();
        const inputContainer = $(this)
          .closest(".select-container")
          .find(".input-container");

        // Mostrar el contenedor de entrada si el valor seleccionado es '0', '2' o '3'
        if (
          selectedValue === "0" ||
          selectedValue === "2" ||
          selectedValue === "3"
        ) {
          inputContainer.show();
        } else if (selectedValue === "1" || selectedValue === "-") {
          inputContainer.hide();
        }

        // Llamar a la función que verifica el estado de todas las selecciones
        checkAllSelections();
      });

      // Attach change event listener for input fields associated with the form-select
      $("body").on("input change", ".input-container input", function () {
        const loc = window.location.pathname.split("/");
        const view = loc[loc.length - 1].split(".")[0];
        if (view == "attendance") {
          checkAllHours();
        } else if (view == "previous_attendance") {
          checkAllHours2();
        }
      });

      // When the page loads, trigger the same logic to show/hide inputs based on the selected value
      $(document).ready(function () {
        // Loop through all select elements and apply the show/hide logic
        $(".form-select").each(function () {
          const selectedValue = $(this).val();
          const inputContainer = $(this)
            .closest(".select-container")
            .find(".input-container");

          // Show/hide the input container based on the selected value
          if (
            selectedValue === "0" ||
            selectedValue === "2" ||
            selectedValue === "3"
          ) {
            inputContainer.show();
          } else if (selectedValue === "1" || selectedValue === "-") {
            inputContainer.hide();
          }
        });

        const mainbox = document.getElementById("region-main-box");
        mainbox.style.setProperty("flex", "0 0 100%", "important");
        mainbox.style.setProperty("max-width", "100%", "important");
        // Call checkAllSelections when the page loads to initialize the button state
        checkAllSelections();
        dateShower();
        reportDownloader();
      });

      // Function to check all hours
      function checkAllHours2() {
        let allSelected = true;
        $(".form-select").each(function () {
          const option = $(this).val();
          const value = $(this)
            .parent()
            .children()
            .eq(2)
            .children()
            .first()
            .val();
          const numberValue = parseInt(value);
          if (
            (numberValue < 1 || numberValue > 10 || value === "") &&
            option != "1" &&
            option != "-8"
          ) {
            allSelected = false;
            return false; // Exit the loop early
          }
        });
        $("#saveButton").prop("disabled", !allSelected);
      }
      function checkAllHours() {
        let allSelected = true;
        $(".form-select").each(function () {
          const option = $(this).val();
          const value = $(this)
            .parent()
            .children()
            .eq(1)
            .children()
            .first()
            .val();
          const numberValue = parseInt(value);
          if (
            (numberValue < 1 || numberValue > 10 || value === "") &&
            option != "1" &&
            option != "-8"
          ) {
            allSelected = false;
            return false; // Exit the loop early
          }
        });
        $("#saveButton").prop("disabled", !allSelected);
      }

      function checkAllSelections() {
        let allSelected = true;
        // $('.form-select').each(function() {
        //     const value = $(this).val();
        //     if (value === '-') {
        //         allSelected = false;
        //         return false; // Exit the loop early
        //     }
        // });
        const loc = window.location.pathname.split("/");
        const view = loc[loc.length - 1].split(".")[0];
        if (view == "attendance") {
          checkAllHours();
        } else if (view == "previous_attendance") {
          checkAllHours2();
        }
      }

      $(document).ready(function () {
        $("#date-range-select").on("change", function () {
          const selectedValue = $(this).val();
          const dateInputsContainer = $("#date-inputs-container");
          if (selectedValue === "range_dates") {
            dateInputsContainer.show(); // Show the date inputs
          } else {
            dateInputsContainer.hide(); // Hide the date inputs for other selections
            dateInputsContainer.find("input").val(""); // Clear the input fields
          }
        });
      });

      function dateShower() {
        const dateSelect = document.getElementById("date-range-select");
        const divRages = document.getElementById("date-inputs-container");

        if (dateSelect.value == "range_dates") {
          divRages.style.display = "";
        } else {
          divRages.style.display = "none";
        }
      }

      function reportDownloader() {
        const startDate = document.getElementById("start-date");
        const endDate = document.getElementById("end-date");
        const detailedDonwloader = document.getElementById(
          "detailed_donwloader"
        );

        if (
          (new Date(endDate.value) - new Date(startDate.value)) /
            (1000 * 60 * 60 * 24) <
          5
        ) {
          detailedDonwloader.disabled = false;
        } else {
          detailedDonwloader.disabled = true;
        }
      }

      $(document).ready(function () {
        $("#start-date").on("change", function () {
          const selectedValue = $(this).val();
          const finalDate = document.getElementById("end-date");
          finalDate.min = selectedValue;
        });
      });

      $(document).ready(function () {
        $("#end-date").on("change", function () {
          const selectedValue = $(this).val();
          const finalDate = document.getElementById("start-date");
          finalDate.max = selectedValue;
        });
      });

      $(document).ready(function () {
        $("#confirmAtt").on("click", function () {
          const courseid = document.getElementById("courseid").value;
          $("#course_" + courseid).submit();
        });
      });

      function adjustTableSize() {
        // Select the textarea and the table
        var textarea = document.querySelector('textarea[name="extrainfo[]"]');
        var table = document.querySelector("#attendance-table"); // Assuming you are using this ID for the table

        // Update the table width based on the textarea width
        table.style.width = textarea.offsetWidth + "px";
      }

      function tableSize() {
        // Listen for the resize event on the textarea and adjust the table width
        document
          .querySelector('textarea[name="extrainfo[]"]')
          .addEventListener("resize", adjustTableSize);

        // Ensure the table is resized when the page loads
        window.onload = function () {
          adjustTableSize();
        };
      }

      // Initial call to handle selections and visibility
      checkAllSelections();
      dateShower();
      reportDownloader();
      tableSize();
    },
  };
});
