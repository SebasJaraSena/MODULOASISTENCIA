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
$userid = $USER->id;
$courseid = $_GET['courseid'];
$attendancepage = $_GET['page']??1;
//$limit = $_GET['limit']??1;
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


// Functions
function studentsFormatWeek($studentslist, $week, $cachehistoryattendance, $temporalattendance, $close, $userid){ // Función que formatea la información por aprendiz
    $day = date('l');
    $lastmonday = new DateTime();
    $lastmonday->modify('-4 days');
    // Array que establece la inicial de los días de la semana
    $weekdaysnames = [$lastmonday->format('l')=>0, $lastmonday->modify('+1 days')->format('l')=>1, $lastmonday->modify('+1 days')->format('l')=>2, $lastmonday->modify('+1 days')->format('l')=>3, $lastmonday->modify('+1 days')->format('l')=>4];
    for($i = 0; $i < count($studentslist); $i ++){ // Ciclo que recorre la cantidad de aprendices que se encuentran matrículados
        $studentid = $studentslist[$i]['id'];
        $filtered = array_filter($cachehistoryattendance, function ($item) use ($studentid){ // Se filtra información de aprendiz
            return $item['student_id'] == $studentid;
        });
        
        $studentslist[$i]['week'] = $week; // Se establece un nuevo campo en la información de los aprendices
        
        $studentslist[$i]['week'][$weekdaysnames[$day]]['selection'] = [ // Se modifica información para el día actual
            'op-8' => 1,
            'op0' => 0,
            'op1' => 0,
            'op2' => 0,
            'op3' => 0,
        ]; 
        $studentslist[$i]['week'][$weekdaysnames[$day]]['closed']= $close;
        
        $jsonattendance = json_decode($cachehistoryattendance[array_key_first($filtered)]['full_attendance'], true)??[];
        $lastmonday = new DateTime();
        $lastmonday->modify('-4 days');
        $lastrec = count($jsonattendance)-1;
        $dayaux = new DateTime();
        $startweek = $dayaux->format('Y-m-d');
        
        $filtereddate = array_filter($jsonattendance, function ($item) use ($startweek, $userid){
            return date_diff(date_create($item['DATE']), date_create($startweek))->days <= 4 && $item['TEACHER_ID'] == $userid;
        });
        
        $startindex = array_key_first($filtereddate)??0;
        if (!empty($filtereddate) && $jsonattendance[$lastrec]['DATE']>=$lastmonday->format('Y-m-d') && $startindex != -1){
            
            foreach($filtereddate as $index=>$value){ // Ciclo para interar sobre las fechas que van a ser visualizadas en pantalla
                $jadate = DateTime::createFromFormat('Y-m-d', $jsonattendance[$index]["DATE"]);
                $jadt = $jadate->format('l');
                
                if($jadt == $day){ // Trato especial si es el día actual
                    $studentslist[$i]['week'][$weekdaysnames[$jadate->format('l')]]['closed']=1;
                } 
                $studentslist[$i]['week'][$weekdaysnames[$jadate->format('l')]]['selection']["op".$jsonattendance[$index]['ATTENDANCE']]=1;
            }
        }
        
        if (!empty($temporalattendance) ){
            $filteredtemp = array_filter($temporalattendance, function ($item) use ($studentid){
                return $item->studentid == $studentid;
            });
            
            $hours = $temporalattendance[array_key_first($filteredtemp)]->amounthours;
            $observations = $temporalattendance[array_key_first($filteredtemp)]->observations;
            $studentslist[$i]['week'][$weekdaysnames[$day]]['selection']['op'.$temporalattendance[array_key_first($filteredtemp)]->attendance]=1;
            $studentslist[$i]['week'][$weekdaysnames[$day]]['missedhours']= $hours!=0?$hours:'';
            $studentslist[$i]['week'][$weekdaysnames[$day]]['observations']= $observations;
        }
        
    }
    
    return $studentslist;
}

function getWeekRange(DateTime $date): array{ // Funtion that establishes the range of days tha would be shown in the table header
    $week = ['Monday'=>'L', 'Tuesday'=>'M', 'Wednesday'=>'X', 'Thursday'=>'J', 'Friday'=>'V', 'Saturday'=>'S', 'Sunday'=>'D'];
    $fullweek=[];
    $date->modify('-4 day');
    
    for ($i = 0 ; $i < 5 ; $i++){
        if ($i !== 0){
            $date->modify('+1 day');
        }
        $fullweek[] = ['day'=> $week[$date->format('l')],'date'=> $date->format('d/m'), 'current' => date('d/m')==$date->format('d/m')?0:1];
    }
    
    // Format the dates to your preferred format
    return $fullweek;
}


// Se obtiene información guardada en caché
$attendance_data = $cache->get($courseid);
$attendance_info=[];

$historyattendance = $DB->get_records('local_asistencia_permanente', ['course_id'=> $courseid]);

$cache->set("H_$courseid", json_encode($historyattendance));
$cachehistoryattendance = json_decode($cache->get("H_$courseid"), true);


if ($_SERVER["REQUEST_METHOD"] === "POST"){ // Processing POST requestes
    $postattendance = $_POST['attendance'];
    if(!empty($postattendance) && $close == 0){ // Saving attendance
        $len = count($_POST['userids']);
        $extrainfo =$_POST['extrainfo'];
        $extrainfoNum =$_POST['extrainfoNum'];
        $auxi = 0;
        
        if(!empty($attendance_data)){
            $attendance_info = json_decode($attendance_data, true);
            $flag = isset($attendance_info[$attendancepage]);
        }
        $today = date('Y-m-d');
        $sql = "SELECT id FROM {local_asistencia} WHERE \"date\"<> '$today'";
        $recordstodelete = $DB->get_records_sql($sql);
        foreach ($recordstodelete as $rtd) {
            $DB->delete_records('local_asistencia', ['id'=>$rtd->id]);
        }
        
        for ($i = 0; $i< $len; $i++ ){
            $rcourseid = $_POST['courseid'];
            $studentid = $_POST['userids'][$i];
            $teacherid = $_POST['teacherid'];
            $attendance = $_POST['attendance'][$i];
            $record_insert_update = false;
            try {
                $record_insert_update = $DB->get_record('local_asistencia', [
                    'courseid' => $rcourseid, 
                    'studentid'=> $studentid, 
                    'teacherid' => $teacherid
                ]);
            } catch (\Throwable $th) {
                //throw $th;
            }
            if($postattendance[$i]=="-1"){
                $auxi++;
                $observations= "";
                $amountHours= 0;
            }else{
                $observations= $extrainfo[$i-$auxi];
                $amountHours= $extrainfoNum[$i-$auxi];
            }
            if(!empty($record_insert_update)){ // Updating "temporal" table
                $record_insert_update->attendance = $attendance;
                $record_insert_update->date = date('Y-m-d');
                $record_insert_update->observations = $observations;
                $record_insert_update->amounthours = $amountHours;
                $DB->update_record('local_asistencia', $record_insert_update );
            } else{ // Insertion to "temporal" table
                
                $record_insert_update = new stdClass;
                $record_insert_update->courseid = $rcourseid;
                $record_insert_update->studentid = $studentid;
                $record_insert_update->teacherid = $teacherid;
                $record_insert_update->date = date('Y-m-d');
                $record_insert_update->attendance = $attendance;
                $record_insert_update->observations = $observations;
                $record_insert_update->amounthours = $amountHours;
                $attendance_info[$attendancepage][]= $record_insert_update;
                $DB->insert_record('local_asistencia', $record_insert_update);
            }
            $attendance_info[$attendancepage][]= $record_insert_update;
        }
        $cache->set($courseid, json_encode($attendance_info));
        
        $asistencia = $DB->get_records('local_asistencia', ['courseid' => $courseid]); 
    
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
                
                // $params = [
                //     'COURSE_ID' => $asis->courseid,
                //     'STUDENT_ID' => $asis->studentid,
                // ];
                $result = $DB->get_record('local_asistencia_permanente',["course_id"=> $courseid, "student_id"=> $asis->studentid]);

                if(empty($result)){
                    $insert_update = new stdClass;
                    $insert_update->course_id = $asis->courseid;
                    $insert_update->student_id = $asis->studentid;
                    $insert_update->full_attendance = json_encode($attendance_format);
                    $DB->insert_record('local_asistencia_permanente', $insert_update);
                } else{
                    
                    $attandancehistory = json_decode($result->full_attendance, true);
                    $lastattendancerecordindex = count($attandancehistory);
                    $prevattendancedate = $attandancehistory[$lastattendancerecordindex-1]['DATE'];
                    $prevattendanceteacher = $attandancehistory[$lastattendancerecordindex-1]['TEACHER_ID'];
                    
                    if(($prevattendanceteacher == $userid && $prevattendancedate < date('Y-m-d')) || ($prevattendanceteacher != $userid && $prevattendancedate <= date('Y-m-d'))){
                        $attandancehistory[$lastattendancerecordindex] = $attendance_format[0];
                        $result->full_attendance= json_encode($attandancehistory);
                        
                        $DB->update_record('local_asistencia_permanente', $result);
                    }
                    
                    $todeletecounter++;
                }
                $attendance_format=NULL;
            }
            \core\notification::add("Asistencia guardada con éxito.", \core\output\notification::NOTIFY_SUCCESS);
            // $shortname = $fic->shortname;
            
            $toloadcache = $DB->get_records('local_asistencia_permanente', ['course_id'=>$courseid]);
            // $toloadcache = local_asistencia_external::query("SELECT * FROM \"$dbschema\".\"$dbtablename\" WHERE \"COURSE_ID\" = :COURSE_ID", ["COURSE_ID" => $courseid]);
        }
       // $DB->delete_records('local_asistencia', ['courseid' => $courseid]);
     //   redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
    }
}
$pageurl = $attendancepage-1??0;
$currentpage = $pageurl+1;
$date = new DateTime(date('Y-m-d')); 
$weekRange = getWeekRange($date);

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
    
    local_asistencia_setup_breadcrumb('Asistencia general');
    echo $OUTPUT->header();
    $userid = $USER->id;
    $adminsarray = explode(",",$DB->get_record('config', ['name' => 'siteadmins'])->value);
    $configbutton = in_array($userid, $adminsarray)?1:0;
    $pages_attendance_string = $cache->get('attendancelist'.$courseid);
    $cache->delete('attendancelist'.$courseid);
    $attendance_data = $cache->get($courseid);
    if(isset($pages_attendance_string)){
        $pages_attendance_array = json_decode($pages_attendance_string, true);
        
        $students = local_asistencia_external::fetch_students($context->id, $courseid, 5, $pageurl,/*$limit=='1'?10:*/10000, $condition);
        $pages_attendance_array['pages'] = $students['pages'];
        $pages_attendance_array[$attendancepage] = $students['students_data'];
        $studentsamount= $students['studentsamount'];

        if (empty($pages_attendance_array[$attendancepage])){
            \core\notification::add("No se encontraron aprendices matriculados.", \core\output\notification::NOTIFY_WARNING);
        }
        
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
    $temporalattendance = array_values($DB->get_records('local_asistencia', ['courseid' => $courseid]));
    
    $students = studentsFormatWeek($studentslist[$attendancepage]?? $pages_attendance_array_copy[$attendancepage], $weekRange, $cachehistoryattendance, $temporalattendance, $close, $userid);
    
    $studentslist[$attendancepage] = $students;
    $closeattendance =$studentsamount == count($temporalattendance)?0:1;
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
        'close' => $close,
        'range' => $_GET['range']??0,
        'saved' => $saved,
        'config' => $configbutton,
        'closeattendance' => $closeattendance,
        //'limit' => $limit, //* Variable que cambia entre páginado o todo en una página
        'dirroot' => $dircomplement[1],
        'asistio'=> "Asistió",
        'inasistencia' => "No asistió",
        'retraso' => "Llegó tarde",
        'excusa' => "Excusa médica",
    ];
    


    echo $OUTPUT->render_from_template('local_asistencia/studentslist', $templatecontext);

    $PAGE->requires->js_call_amd('local_asistencia/attendance_views', 'init');
    echo $OUTPUT->footer();
    