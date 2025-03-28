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

use block_rss_client\output\item;
use core\plugininfo\local;

use function PHPSTORM_META\type;

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__.'/externallib.php');
require_once($CFG->dirroot.'/local/asistencia/lib.php'); 

require_login(); 

global $CFG, $USER;

// Creacion de cache
$cache = cache::make('local_asistencia', 'coursestudentslist');

$courseid = $_GET['courseid'];

$close = local_asistencia_external::close_validation($courseid);
$context = context_course::instance($courseid);
$currenturl = new moodle_url('/local/asistencia/index.php');
$dircomplement = explode("/",$currenturl->get_path());
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->set_title('Lista Asistencia');
$PAGE->requires->js_call_amd('local_asistencia/attendance_observations', 'init');
$PAGE->requires->css(new moodle_url('/local/asistencia/styles/styles.css', array('v'=> time())));

require_capability('local/asistencia:view', $context);

$pageurl = $attendancepage-1??0;
$currentpage = $pageurl+1;
    
    local_asistencia_setup_breadcrumb('Menu');

    echo $OUTPUT->header();
    $userid = $USER->id;
    $adminsarray = explode(",",$DB->get_record('config', ['name' => 'siteadmins'])->value);
    $configbutton = in_array($userid, $adminsarray)?1:0;
             
    $temporalattendance = array_values($DB->get_records('local_asistencia', ['courseid' => $courseid]));
        
   
    $students = json_decode($studentsstring, true);
    $templatecontext = (object)[
        'students' => $students[$attendancepage],
        'courseid' => $courseid,
        'teacherid' => $userid,
        'config' => $configbutton,
        'closeattendance' => $closeattendance,
        'dirroot' => $dircomplement[1],
    ];
    
    echo $OUTPUT->render_from_template('local_asistencia/menu', $templatecontext);

    $PAGE->requires->js_call_amd('local_asistencia/attendance_views', 'init');
    echo $OUTPUT->footer();
    