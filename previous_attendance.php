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

global $CFG, $USER;

// Creacion de cache
$cache = cache::make('local_asistencia', 'coursestudentslist');
$userid = $USER->id;
$courseid = $_GET['courseid'];
$attendancepage = $_GET['page']??1;
// $limit = $_GET['limit']??1;
$date = new DateTime(date('Y-m-d')); 
$weeks = isset($_GET['weeks']) && ($_GET['weeks']<=4 &&  $_GET['weeks']>=1)?$_GET['weeks']:1;
$weeks = isset($_POST['numweeks'])?$_POST['numweeks']:$weeks;
$startweek = clone $date;
$endweek = clone $date;
$initial = $date->format('l') == 'Monday'?$startweek->modify("-$weeks week")->format("Y-m-d"):$startweek->modify("-$weeks week")->modify("last monday")->format("Y-m-d");
$final =  $date->format('l') == 'Sunday'?$endweek->modify("-$weeks week")->format("Y-m-d"):$endweek->modify("-$weeks week")->modify("next sunday")->format("Y-m-d");

$close = local_asistencia_external::close_validation_retard($courseid, $initial, $final);
$context = context_course::instance($courseid);
$currenturl = new moodle_url('/local/asistencia/previous_attendance.php');
$dircomplement = explode("/",$currenturl->get_path());
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->set_title("Lista Asistencia Semana $weeks");
$PAGE->requires->js_call_amd('local_asistencia/attendance_observations', 'init');
$PAGE->requires->css(new moodle_url('/local/asistencia/styles/styles.css', array('v'=> time())));

require_capability('local/asistencia:view', $context);

$a=0;

// Functions
function studentsFormatWeek($studentslist, $week, $cachehistoryattendance, $temporalattendance, $userid, $initial, $final, $a, $suspended){ // Función que formatea la información por aprendiz
        
    $lastmonday = new DateTime($initial);
    
    $lastweekmonday = clone $lastmonday;
    
    // Array que establece la inicial de los días de la semana
    $weekdaysnames = ['Monday'=>0, 'Tuesday'=>1, 'Wednesday'=>2, 'Thursday'=>3, 'Friday'=>4, 'Saturday'=>5, 'Sunday'=>6];
    
    $totaldaysattendance = 0;
    
    for($i = 0; $i < count($studentslist); $i ++){ // Ciclo que recorre la cantidad de aprendices que se encuentran matrículados
        $studentid = $studentslist[$i]['id'];
        $filtered = array_filter($cachehistoryattendance, function ($item) use ($studentid){ // Se filtra información de aprendiz
            return $item['student_id'] == $studentid;
        });
        
        $studentslist[$i]['week'] = $week; // Se establece un nuevo campo en la información de los aprendices
        foreach($weekdaysnames as $day ){
            $studentslist[$i]['week'][$day]['selection'] = [ // Se modifica información para el día actual
                'op-8' => 1,
                'op0' => 0,
                'op1' => 0,
                'op2' => 0,
                'op3' => 0,
            ];
            $studentslist[$i]['week'][$day]['edit']=0;
           
        }
        
        $jsonattendance = json_decode($cachehistoryattendance[array_key_first($filtered)]['full_attendance'], true)??[];
        
        
        $filtereddate = array_filter($jsonattendance, function ($item) use ($initial, $final, $userid){
            return  $initial <= $item['DATE'] && $final >= $item['DATE'] && $item['TEACHER_ID'] == $userid;
        });
        
        $lastrec = array_key_last($filtereddate);
        $startindex = array_key_first($filtereddate)??0;
        if (!empty($filtereddate) && $jsonattendance[$lastrec]['DATE']>=$lastweekmonday->format('Y-m-d') && $startindex != -1){
            foreach($filtereddate as $index=>$value){ // Ciclo para interar sobre las fechas que van a ser visualizadas en pantalla
                $a++;
                $jadate = DateTime::createFromFormat('Y-m-d', $jsonattendance[$index]["DATE"]);
                
                $studentslist[$i]['week'][$weekdaysnames[$jadate->format('l')]]['selection']["op".$jsonattendance[$index]['ATTENDANCE']]=1;
                $studentslist[$i]['week'][$weekdaysnames[$jadate->format('l')]]['edit']=1;
                
            }
        }
                
        if (!empty($temporalattendance) ){
            $filteredtemp = array_filter(json_decode(json_encode($temporalattendance),true), function ($item) use ($studentid){
                return $item['studentid'] == $studentid;
            });
            if (!$studentslist[$i]['status']){
                foreach ($filteredtemp as $prevattndnc) {
                    $totaldaysattendance++;
                    $day = DateTime::createFromFormat('Y-m-d', $prevattndnc["date"])->format('l');
                    $hours = $prevattndnc['amounthours'];
                    $observations = $prevattndnc['observations'];
                    $studentslist[$i]['week'][$weekdaysnames[$day]]['selection']['op'.$prevattndnc['attendance']]=1;
                    $studentslist[$i]['week'][$weekdaysnames[$day]]['missedhours']= $hours!=0?$hours:'';
                    $studentslist[$i]['week'][$weekdaysnames[$day]]['observations']= $observations;
                }
            }
        }
    }
    // Evitar la división por 0
    $auxiliar = count($studentslist)-$suspended == 0?0:$totaldaysattendance/(count($studentslist)-$suspended);
    return [$studentslist, $auxiliar, $a];
}

function getWeekRange($initial): array{ // Funtion that establishes the range of days tha would be shown in the table header
    $week = ['Monday'=>'L', 'Tuesday'=>'M', 'Wednesday'=>'X', 'Thursday'=>'J', 'Friday'=>'V', 'Saturday'=>'S', 'Sunday'=>'D'];
    $fullweek=[];
    $date = new DateTime($initial);
    
    
    for ($i = 0 ; $i < 7 ; $i++){
        if ($i !== 0){
            $date->modify('+1 day');
        }
        $fullweek[] = [
            'day'=> $week[$date->format('l')],
            'date'=> $date->format('d/m'),
            'fulldate'=> $date->format('Y-m-d'),
        ];
    }
    
    // Format the dates to your preferred format
    return $fullweek;
}

// Se obtiene información guardada en caché

$attendance_info=[];

$historyattendance = $DB->get_records('local_asistencia_permanente', ['course_id'=> $courseid]);

$cache->set("H_$courseid", json_encode($historyattendance));
$cachehistoryattendance = json_decode($cache->get("H_$courseid"), true);



if ($_SERVER["REQUEST_METHOD"] === "POST"){ // Processing POST requestes
    $postattendance = $_POST['attendance'];
    
    if(!empty($postattendance) ){ // Saving temporal attendance
        $len = count($_POST['userids']);
        $extrainfo =$_POST['extrainfo'];
        $extrainfoNum =$_POST['extrainfoNum'];
        $dates = $_POST['date'];
        $auxi = 0;
        $tofilterdates = json_decode($cachehistoryattendance[array_key_first($cachehistoryattendance)]['full_attendance'],true);
        $filter = empty($tofilterdates)?[]:array_filter($tofilterdates, function ($item) use ($userid,$initial, $final){
            return $item['DATE'] >= $initial && $item['DATE'] <= $final && $item['TEACHER_ID'] == $userid;
        });
        
        for ($i = 0; $i< $len; $i++ ){
            $rcourseid = $_POST['courseid'];
            $studentid = $_POST['userids'][$i];
            $teacherid = $_POST['teacherid'];
            $attendance = $_POST['attendance'];
            $secondlen = count($attendance)/$len;
            
            for($j =0 ; $j < $secondlen; $j++ ){
                $record_insert_update = false;
                
                try {
                    $record_insert_update = $DB->get_record('local_asistencia', [
                        'courseid' => $rcourseid, 
                        'studentid'=> $studentid, 
                        'teacherid' => $teacherid,
                        'date'=> $dates[$j],
                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                }
                $datecheck = array_filter($filter, function ($item) use ($dates, $j){
                    return $item['DATE'] == $dates[$j];
                });
                                if (!empty($datecheck)){
                    continue;
                }
                // echo $dates[$j];
                $observations= $extrainfo[$auxi];
                $amountHours= $extrainfoNum[$auxi];
                $auxi++;
                
                if(!empty($record_insert_update)){ // Updating "temporal" table
                    $record_insert_update->attendance = $attendance[($i*$secondlen)+$j];
                    $record_insert_update->date = $dates[$j];
                    $record_insert_update->observations = $observations;
                    $record_insert_update->amounthours = $amountHours;
                    $DB->update_record('local_asistencia', $record_insert_update );
                } else{ // Insertion to "temporal" table
                    
                    $record_insert_update = new stdClass;
                    $record_insert_update->courseid = $rcourseid;
                    $record_insert_update->studentid = $studentid;
                    $record_insert_update->teacherid = $teacherid;
                    $record_insert_update->date = $dates[$j];
                    $record_insert_update->attendance = $attendance[($i*$secondlen)+$j];
                    $record_insert_update->observations = $observations;
                    $record_insert_update->amounthours = $amountHours;
                    $attendance_info[$attendancepage][]= $record_insert_update;
                    $DB->insert_record('local_asistencia', $record_insert_update);
                }
                $attendance_info[$attendancepage][]= $record_insert_update;
            }
        }
        $cache->set($courseid, json_encode($attendance_info));
        
        $asistencia = $DB->get_records('local_asistencia', ['courseid' => $courseid, 'teacherid' => $userid]); 
        
        if(!empty($asistencia)){
            $todeletecounter = 1;
            foreach($asistencia as $asis){
                $fic =$DB->get_record('course', ['id'=>$asis->courseid], 'shortname');
                $attendance_format[] =[
                    'TEACHER_ID' => $asis->teacherid,
                    'ATTENDANCE' => $asis->attendance,
                    'DATE' => $asis->date,
                    'OBERVATIONS' => $asis->observations,
                    'AMOUNTHOURS' => $asis->amounthours,
                ];
                
                $result = $DB->get_record('local_asistencia_permanente',["course_id"=> $courseid, "student_id"=> $asis->studentid]);
                if(empty($result)){
                    $insert_update = new stdClass;
                    $insert_update->course_id = $asis->courseid;
                    $insert_update->student_id = $asis->studentid;
                    $insert_update->full_attendance = json_encode($attendance_format);
                    $DB->insert_record('local_asistencia_permanente', $insert_update);
                } else{
                    
                    $attendancehistory = json_decode($result->full_attendance, true);
                    $lastattendancerecordindex = count($attendancehistory);
                    $prevattendancedate = $attendancehistory[$lastattendancerecordindex-1]['DATE'];
                    $prevattendanceteacher = $attendancehistory[$lastattendancerecordindex-1]['TEACHER_ID'];
                    
                    $attendancehistory[$lastattendancerecordindex] = $attendance_format[0];
                    $result->full_attendance= json_encode($attendancehistory);
                    
                    $DB->update_record('local_asistencia_permanente', $result);
                
                    
                    $todeletecounter++;
                }
                $attendance_format=NULL;
            }
            \core\notification::add("Asistencia guardada con éxito.", \core\output\notification::NOTIFY_SUCCESS);
            
            $toloadcache = $DB->get_records('local_asistencia_permanente', ['course_id'=>$courseid]);
        }
    //    $DB->delete_records('local_asistencia', ['courseid' => $courseid]);
      //  redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
    }
}
$pageurl = $attendancepage-1??0;
$currentpage = $pageurl+1;

$weekRange = getWeekRange($initial);

$form = new edit();

$condition='';


if ($form->is_cancelled()){
    $condition='';
}
else if ($fromform = $form->get_data()){
    $filterarrayoptionstraslate = ['firstname', 'lastname', 'email', 'username'];
    foreach($fromform as $key => $value){
        if ($key != 'submitbutton'){
            if ($key == 'filters' && $value >= 0 && $value <=3){
                $condition = "AND UPPER(".$filterarrayoptionstraslate[$value].")";
            } elseif($key == 'filterValue'){
                $value = in_array('email', explode(" ",$condition))?$value:strtoupper($value);
                if(!empty($value)){
                    $condition.= " LIKE '%$value%' ";
                }else{
                    $condition='';
                }
                
            }
        }
    }
}

    $cache->set("course$courseid.user$userid", $condition);
    
    echo $OUTPUT->header();
    $userid = $USER->id;
    $adminsarray = explode(",",$DB->get_record('config', ['name' => 'siteadmins'])->value);
    $configbutton = in_array($userid, $adminsarray)?1:0;
    $pages_attendance_string = $cache->get('attendancelist'.$courseid);
    $cache->delete('attendancelist'.$courseid);
    
    if(isset($pages_attendance_string)){
        $pages_attendance_array = json_decode($pages_attendance_string, true);
        
        $students = local_asistencia_external::fetch_students($context->id, $courseid, 5, $pageurl,/*$limit=='1'?10:*/10000, $condition);
        $supended = array_filter($students['students_data'], function($item) {
            return $item['status'] == 1;
        });
        $pages_attendance_array['pages'] = $students['pages'];
        $pages_attendance_array[$attendancepage] = $students['students_data'];
        $studentsamount= $students['studentsamount'];
        
        $pages_attendance_array_copy = $pages_attendance_array;
        $listlimit = count($pages_attendance_array_copy[$attendancepage]);
        $cache->set('attendancelist'.$courseid, json_encode($pages_attendance_array));
        $test=$cache->get('attendancelist'.$courseid);
        $studentslist = json_decode($test, true);
    }
        
    for($page = 1; $page <= $pages_attendance_array['pages']; $page++){
        if ($page === 1){
            $pages[$page] =[
                'page' => $page,
                'current' => $page==$pageurl+1,
                'active' => ''
            ];
        }
        if(($page === 2 || $page === $pages_attendance_array['pages']-1) && abs($currentpage-$page) >= 3 ){
            $pages[$page] = [
                'page'=> '...',
                'current'=> false,
                'active' => 'disabled'
            ];
        }
        if (abs($page - $currentpage) < 3){
            $pages[$page] =[
                'page' => $page,
                'current' => $page==$pageurl+1,
                'active' => ''
            ];
        }
        if ($page === $pages_attendance_array['pages']){
            $pages[$page] =[
                'page' => $page,
                'current' => $page==$pageurl+1];
        }
    }
            
    $range = isset($_GET['range'])?$_GET['range']:0;
    
    [$initialdate, $finaldate]= [$weekRange[0]['fulldate'],$weekRange[6]['fulldate']];
    $sql= "SELECT * FROM {local_asistencia} WHERE courseid = $courseid AND teacherid = $userid AND \"date\" BETWEEN '$initialdate' AND '$finaldate'";
    $temporalattendance = array_values($DB->get_records_sql($sql));
    
    
    [$students, $totaldaysattendance, $a] = studentsFormatWeek($studentslist[$attendancepage]?? $pages_attendance_array_copy[$attendancepage], $weekRange, $cachehistoryattendance, $temporalattendance, $userid, $initial,$final, $a, count($supended));
    
    $studentslist[$attendancepage] = $students;
    
    $closeattendance = ($studentsamount*$totaldaysattendance) == count($temporalattendance) && count($temporalattendance)?0:1;
    $cache->set('attendancelist'.$courseid, json_encode($studentslist));

    $studentsstring = $cache->get('attendancelist'.$courseid);
    $students = json_decode($studentsstring, true);
    $templatecontext = (object)[
        'students' => $students[$attendancepage],
        'courseid' => $courseid,
        'teacherid' => $userid,
        'data' => $urldata,
        'data2' => $urldata2,
        'weekheader' => $weekRange,
        'monthheader' => $monthrange,
        'display' => 0,
        'range' => $range,
        'listpages' => !empty($pages)?array_values($pages):[],
        'currentpage' => $currentpage,
        'close' => $a == (count($students[$attendancepage])*7)?1:0,
        'closed' => $close,
        'range' => $_GET['range']??0,
        'saved' => $saved,
        'config' => $configbutton,
        'closeattendance' => $closeattendance,
        // 'limit' => $limit, //*  Variable que cambia entre páginado o todo en una página
        'dirroot' => $dircomplement[1],
        'weeks' => $weeks,
        '1week' => $weeks == 1?1:0,
        '2week' => $weeks == 2?1:0,
        '3week' => $weeks == 3?1:0,
        '4week' => $weeks == 4?1:0,
        'asistio'=> "Asistió",
        'inasistencia' => "No asistió",
        'retraso' => "Llegó tarde",
        'excusa' => "Excusa médica",
    ];
    
    $form->display();

    echo $OUTPUT->render_from_template('local_asistencia/previous_attendance', $templatecontext);

    $PAGE->requires->js_call_amd('local_asistencia/attendance_views', 'init');
    echo $OUTPUT->footer();
    