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
 * @module     local_asistencia/attendance_views
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2024 Luis Pérez <lfperezv@sena.edu.co>
 **/

define(["jquery"], function ($) {
  return {
    init: function () {
      function tableStickyColumns(percent) {
        // Acomodador de columnas estáticas
        const stickyColumns = document.querySelectorAll(".sticky-column");
        const table = document.getElementById("attendance-table");
        const div = table.querySelectorAll("th.sticky-column").length;
        const tdColumns = table.querySelectorAll("td.sticky-column").length;
        stickyColumns.forEach((column, index) => {
          const width = column.offsetWidth;
          column.style.width = width + "px";
          if (index == 0) {
            table.querySelectorAll("th.sticky-column")[index].style.left =
              percent * 0 + "px";
            table.querySelectorAll("td.sticky-column")[index].style.left =
              percent * 0 + "px";
            table.querySelectorAll("th.sticky-column")[
              index
            ].style.backgroundColor = "#f1f1f1";
          } else if (index < div) {
            const columnWidth = parseInt(
              table
                .querySelectorAll("th.sticky-column")
                [index - 1].style.width.slice(0, -2)
            );
            const columnLeft = parseInt(
              table
                .querySelectorAll("th.sticky-column")
                [index - 1].style.left.slice(0, -2)
            );
            table.querySelectorAll("th.sticky-column")[index].style.left =
              (columnWidth + columnLeft) * percent + "px";
            table.querySelectorAll("th.sticky-column")[
              index
            ].style.backgroundColor = "#f1f1f1";
            const leftPosition =
              table.querySelectorAll("th.sticky-column")[index].style.left;
            table.querySelectorAll("td.sticky-column")[index].style.left =
              leftPosition;
          } else if (index < tdColumns) {
            const leftPosition =
              table.querySelectorAll("th.sticky-column")[index % div].style
                .left;
            table.querySelectorAll("td.sticky-column")[index].style.left =
              leftPosition;
          }
          if (index < tdColumns) {
            const text =
              table.querySelectorAll("td.sticky-column")[index].outerText;
            if (text === "SUSPENDIDO") {
              table.querySelectorAll("td.sticky-column")[
                index
              ].style.backgroundColor = "#fcefdc";
            } else if (text === "ACTIVO") {
              table.querySelectorAll("td.sticky-column")[
                index
              ].style.backgroundColor = "#def1de";
            } else {
              table.querySelectorAll("td.sticky-column")[
                index
              ].style.backgroundColor = "#f1f1f1";
            }
          }
        });
      }
      function waitForElement(selector, callback) {
        const interval = setInterval(() => {
          if (document.querySelector(selector)) {
            clearInterval(interval);
            callback();
          }
        }, 100); // Check every 100ms
      }
      $(document).ready(function () {
        waitForElement("#attendance-table", function () {
          // Your function or code here
          if (window.innerWidth > 768) {
            tableStickyColumns(1);
          } else {
            tableStickyColumns(0.2);
          }
          window.addEventListener("resize", function () {
            const mainbox = document.getElementById("region-main-box");
            mainbox.style.setProperty("flex", "0 0 100%", "important");
            mainbox.style.setProperty("max-width", "100%", "important");
            if (this.innerWidth <= 768) {
              tableStickyColumns(0.2);
            } else {
              tableStickyColumns(1);
            }
          });
        });
      });
    },
  };
});
