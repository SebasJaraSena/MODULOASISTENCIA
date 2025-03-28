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

use core\plugininfo\local;

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__.'/externallib.php');

// Creacion de cache
$cache = cache::make('local_asistencia', 'coursestudentslist');
$userid = $USER->id;
$initialdate = $_GET['initialdate'];
$finaldate = $_GET['finaldate'];
$cumulous = $_GET['cumulous'];
$courseid = $_GET['courseid'];
$PAGE->set_url(new moodle_url('/local/asistencia/detailed_report_downloader.php'));
$PAGE->set_context(\context_course::instance($courseid));
$PAGE->set_title('Descargar reporte');

function formatData($data){
    global $DB;
    $returndata[]=[];
    foreach($data as $index=>$d){
        $returndata[$index]=[
            'userinfo'=> $d['phone1'].'-'.$d['username'].'-'.$d['firstname'].'-'.$d['lastname'].'-'.$d['email'],
        ];
        $attendancedata = array_values(json_decode($d['attendance'], true));
        $attendancebyteacher = [];
        $keyvalues=[];
        $keyvalue=0;
        foreach($attendancedata as $abt){
            $teacherid = $abt['TEACHER_ID'];
            if(!array_key_exists($teacherid, $keyvalues)){
                $keyvalues[$teacherid] = $keyvalue;
                $keyvalue++;
                $teacher_data = json_decode(json_encode($DB->get_record('user', ['id'=>$teacherid],'username, firstname, lastname, email, phone1')),true);
                $attendancebyteacher[$keyvalues[$teacherid]]=[
                    "userinfo"=> $teacher_data['phone1'].'-'.$teacher_data['username'].'-'.$teacher_data['firstname'].'-'.$teacher_data['lastname'].'-'.$teacher_data['email'],
                ];
            }
            $date = date('m/d', strtotime($abt['DATE']));
            $hours = $abt['AMOUNTHOURS'];
            $attendancebyteacher[$keyvalues[$teacherid]]= array_merge($attendancebyteacher[$keyvalues[$teacherid]], [$date=> $hours]);
        }
        $returndata[$index]=array_merge($returndata[$index],$attendancebyteacher);
    }
    return $returndata;
}
global $CFG, $DB;

$dbtablefieldname = "full_attendance";
$attendancehistory = json_decode(json_encode($DB->get_records('local_asistencia_permanente', ['course_id'=> $courseid])),true);
$shortname = json_decode(json_encode($DB->get_record('course', ['id'=> $courseid],'shortname')), true)['shortname'];
$user = $DB->get_record('user', ['id'=> $userid], 'firstname, lastname');
$userName = $user->firstname ." ". $user->lastname;
$result = local_asistencia_external::fetch_attendance_report_detailed($attendancehistory, $initialdate, $finaldate, $cumulous, $userid);
$arraydata = formatData($result);

if (isset($arraydata[0][0])){
    local_asistencia_external::attendance_detailed_report("Reporte_detallado_$shortname"."_$initialdate"."_$finaldate"."_".time(), $arraydata, 'pdf', $userName, $shortname, $initialdate, $finaldate);
}
else {
    local_asistencia_external::activityReport("Reporte_detallado_$shortname"."_$initialdate"."_$finaldate"."_".time().".pdf", $userid);
}
redirect($CFG->wwwroot."/local/asistencia/history.php?courseid=$courseid&page=1&info=h&cumulous=$cumulous&filtro_fecha=0");