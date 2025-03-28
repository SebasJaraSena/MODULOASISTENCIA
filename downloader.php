<?php
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
 * Boost.
 *
 * @package    local_asistencia
 * @author     Luis Pérez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__.'/externallib.php');

// Creacion de cache
$cache = cache::make('local_asistencia', 'coursestudentslist');
$userid = $USER->id;
$initialdate = $_GET['initialdate'];
$finaldate = $_GET['finaldate'];
$cumulous = $_GET['cumulous'];
$data = json_decode($urldata, true);
$courseid = $_GET['courseid'];
$PAGE->set_url(new moodle_url('/local/asistencia/downloader.php'));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title('Descargar reporte');

global $CFG, $DB;

$attendancehistory = json_decode(json_encode($DB->get_records('local_asistencia_permanente', ['course_id'=> $courseid])),true);
$shortname = json_decode(json_encode($DB->get_record('course', ['id'=> $courseid],'shortname')), true)['shortname'];
$result = local_asistencia_external::fetch_attendance_report($attendancehistory, $initialdate, $finaldate, $cumulous, $userid);
local_asistencia_external::attendance_report($result, $initialdate, $finaldate, $shortname);
redirect($CFG->wwwroot."/local/asistencia/history.php?courseid=$courseid&page=1&info=h&cumulous=$cumulous&filtro_fecha=0");