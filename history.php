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
use core_calendar\local\event\forms\create;

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__ .'/classes/form/edit.php');
require_once(__DIR__.'/externallib.php');

// Creacion de cache
$cache = cache::make('local_asistencia', 'coursestudentslist');
$userid = $USER->id;
$attendancepage = $_GET['page']??1;
$courseid = $_GET['courseid'];
$limit = $_GET['limit']??1;
$currenturl = new moodle_url('/local/asistencia/history.php');
$dircomplement = explode("/",$currenturl->get_path());
$context = context_course::instance($courseid);
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->set_title('Históricos Asistencia');
$PAGE->requires->js_call_amd('local_asistencia/attendance_observations', 'init');
$PAGE->requires->css(new moodle_url('/local/asistencia/styles/styles.css', array('v'=> time())));

require_capability('local/asistencia:view', $context);



// Functions
function studentsFormatMonth($studentslist, $month, $cachehistoryattendance, $userid,  $dbtablefieldname, $comulus=1, $initialdate, $finaldate){ // Función que formatea la información historica de asistencia por aprendiz en un rango de fechas [x-y]
    $dateaux = date_create($initialdate);
    for($i = 0; $i < count($studentslist); $i ++){
        $studentid = $studentslist[$i]['id'];
        
        $filtered = !empty($cachehistoryattendance)?array_filter($cachehistoryattendance, function ($item) use ($studentid){ // Se filtra información relacionada al aprendiz
            return $item['student_id'] == $studentid;
        }):[];
        
        $arrayopts = [0 => "No asistió", 1 => "Asistió", 2 => "Llegada tarde", 3 => "Excusa médica", -8 => "NA"]; // Se define el significado de los valores guardados en la DB externa
        $studentslist[$i]['month'] = $month ;
        
        if (!empty($cachehistoryattendance)){
            $jsonattendance = json_decode($cachehistoryattendance[array_key_first($filtered)][$dbtablefieldname], true);
            
            if ($comulus){
                $filtereddates = !empty($jsonattendance)?array_filter($jsonattendance, function ($item) use ($initialdate, $finaldate){ // Se filtran las asistencias que están dentro del rango de fechas
                    return ($item['DATE'] >= $initialdate) && ($item['DATE'] <= $finaldate);
                }):[];
                foreach($filtereddates as $ja){ // Se recorre cada uno de los datos previamente filtrados
                    $jadate = DateTime::createFromFormat('Y-m-d', $ja["DATE"]);
                    $index = $jadate->diff($dateaux)->days;
                    $studentslist[$i]['month'][$index]['selection']["op"] += isset($ja['AMOUNTHOURS'])?(int) $ja['AMOUNTHOURS']:0;
                    $studentslist[$i]['month'][$index]['current']=0;
                }
            } else {
                $filteredteacher = !empty($jsonattendance)?array_filter($jsonattendance, function ($item) use ($userid, $initialdate, $finaldate){ // Se filtran las asistencias relacionadas al instructor que está visualizando los históricos
                    return ($item['TEACHER_ID'] == $userid) && ($item['DATE'] >= $initialdate) && ($item['DATE'] <= $finaldate);
                }):[];
                
                foreach($filteredteacher as $ja){ // Se recorre cada una de las asistencias previamente filtradas
                    $jadate = DateTime::createFromFormat('Y-m-d', $ja["DATE"]);
                    $index = $jadate->diff($dateaux)->days;
                    $studentslist[$i]['month'][$index]['selection']["op"]=isset($ja['ATTENDANCE'])&&$ja['ATTENDANCE'] != "-1"?$arrayopts[$ja['ATTENDANCE']]:"SUSPENDIDO";
                    $studentslist[$i]['month'][$index]['selection']["time"]= isset($ja['AMOUNTHOURS'])?(int) $ja['AMOUNTHOURS']:0;
                    $studentslist[$i]['month'][$index]['current']=0;
                }
            }
        }
        
    }
    return $studentslist;
}

function getWeekRange($initialdate, $finaldate){ // Funtion that establishes the range of days tha would be shown in the table header
    $week = ['Monday'=>'L', 'Tuesday'=>'M', 'Wednesday'=>'X', 'Thursday'=>'J', 'Friday'=>'V', 'Saturday'=>'S', 'Sunday'=>'D']; // Se define traductor de días
    $fullmonth = [];
    // Modify the date to the start of the week (Monday)
    $firstday = DateTime::createFromFormat('Y-m-d', $initialdate); // To get the initial date
    $lastday = DateTime::createFromFormat('Y-m-d', $finaldate); // To get the final date
    $aux =  $lastday->diff($firstday)->days;
    for ($i = 0 ; $i <= (int) $aux ; $i++){ // Se crea rango de fechas que va a ser mostrado en el header de la tabla
        if ($i !== 0){
            $firstday->modify('+1 day');
        }
        $fullmonth[] = ['day'=> $week[$firstday->format('l')],'date'=> $firstday->format('d/m'), 'current' => date('d/m')==$firstday->format('d/m')?0:1];
    }
    // Format the dates to your preferred format
    return $fullmonth;
}

$dbtablefieldname ="full_attendance";
$historyattendance =$DB->get_records('local_asistencia_permanente', ['course_id'=> $courseid]); // Se consulta todo el histórico relacionado al curso


$cache->set("H_$courseid", json_encode($historyattendance));
$cachehistoryattendance = json_decode($cache->get("H_$courseid"), true);

$pageurl = $attendancepage-1??0;
$currentpage = $pageurl+1;
$postfilter;
$date = new DateTime();
$initialdate = clone $date;
$finaldate = clone $date;

$day = $_GET['day']??0;
$week = $_GET['week']??0;
$range_dates = $_GET['range_dates']??0;

if (isset($_GET['initial']) && isset($_GET['final']) && ($day || $week || $range_dates)){
    $inital = (int) $_GET['initial'] <= strtotime($initialdate->format(('Y-m-d')))/100;
    $final = ((int) $_GET['final'] < strtotime($finaldate->modify('last day of')->format(('Y-m-d')))/100) || (int) $_GET['final'] < strtotime($finaldate->modify('next sunday')->format(('Y-m-d')))/100;
    [$day, $week, $range_dates] = ($inital&&$final)?[$day, $week, $range_dates]:0;
} 

$initialdate = $inital? date_timestamp_set($initialdate, (int) $_GET['initial']*100): $initialdate->modify('first day of');
$finaldate = $final? date_timestamp_set($finaldate, (int) $_GET['final']*100):$finaldate->modify('last day of');


$op = 1;
$postfilter;

if ($_SERVER["REQUEST_METHOD"] === "POST"){ // Processing POST requestes
    $postfilter = $_POST['filtro_fecha'];
    $date = new DateTime(date('Y-m-d')); 
    if ($postfilter == 'range_dates'){ // Rango de fechas seleccionado por usuario
        $day=0;
        $week=0;
        $range_dates = 1;
        $op = 0;
        $initialdate = isset($_POST['start-date'])?date_create($_POST['start-date']):$initialdate;
        $finaldate = isset($_POST['end-date'])?date_create($_POST['end-date']):$finaldate;
    } else if ($postfilter == 'week'){ // Rangp de fechas dado por la semana actual contando desde el lunes hasta el siguiente domingo
        $op = 0;
        $day=0;
        $week=1;
        $range_dates = 0;
        $dateaux = clone $date;
        $nextsunday = clone $date;
        $dateaux->modify('last monday');
        if ($dateaux->modify('+7 days')->format('Y-m-d') == $date->format('Y-m-d')){
            $initialdate = clone $date;
        }else{
            $dateaux->modify('last monday');
            $initialdate = clone $dateaux;
        }    
        
        $finaldate = clone $nextsunday->modify('next sunday');
        
    } else if ($postfilter == 'day'){ // Rango de fecha limitado al día actual
        $op = 0;
        $day = 1;
        $week = 0;
        $range_dates = 0;
        $initialdate = clone $date;
        $finaldate = clone $date;
    }
}

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

$monthrange = getWeekRange($initialdate->format('Y-m-d'), $finaldate->format('Y-m-d'));

$datac = json_decode($urldata, true);
$datac['condition'] =$condition;    
$urldata = json_encode($datac);
unset($datac['condition']);
$urldata2 = json_encode($datac);

echo $OUTPUT->header();
$userid = $USER->id;
$adminsarray = explode(",",$DB->get_record('config', ['name' => 'siteadmins'])->value);
$configbutton = in_array($userid, $adminsarray)?1:0;
$pages_attendance_string = $cache->get('attendancelist'.$courseid);
$cache->delete('attendancelist'.$courseid);
$attendance_data = $cache->get($courseid);

if(isset($pages_attendance_string)){
    $pages_attendance_array = json_decode($pages_attendance_string, true);

    $students = local_asistencia_external::fetch_students($context->id, $courseid, 5, $pageurl,$limit==1?10:10000, $condition);
    $pages_attendance_array['pages'] = $students['pages'];
    $pages_attendance_array[$attendancepage] = $students['students_data'];
    $studentsamount= $students['studentsamount'];
    
    $pages_attendance_array_copy = $pages_attendance_array;
    $listlimit = count($pages_attendance_array_copy[$attendancepage]);
    $cache->set('attendancelist'.$courseid, json_encode($pages_attendance_array));
    $test=$cache->get('attendancelist'.$courseid);
    $studentslist = json_decode($test, true);
}

for($page = 1; $page <= $pages_attendance_array['pages']; $page++){ // Paginador
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
    
$comulus = isset($_GET['range'])?$_GET['range']:0;
$temporalattendance = array_values($DB->get_records('local_asistencia', ['courseid' => $courseid]));
$students = studentsFormatMonth($studentslist[$attendancepage]?? $pages_attendance_array_copy[$attendancepage], $monthrange, $cachehistoryattendance, $userid, $dbtablefieldname, $_GET['range'],$initialdate->format('Y-m-d'), $finaldate->format('Y-m-d'));

$studentslist[$attendancepage] = $students;
$closeattendance =$studentsamount == count($temporalattendance)?0:1;
$cache->set('attendancelist'.$courseid, json_encode($studentslist));

$studentsstring = $cache->get('attendancelist'.$courseid);
$students = json_decode($studentsstring, true);
$templatecontext = (object)[
    'students' => $students[$attendancepage],
    'courseid' => $courseid,
    'teacherid' => $userid,
    'monthheader' => $monthrange,
    'display' => 0,
    'range' => $comulus,
    'listpages' => !empty($pages)?array_values($pages):[],
    'currentpage' => $currentpage,
    'initial_value' => $initialdate->format('Y-m-d'),
    'final_value' => $finaldate->format('Y-m-d'),
    'option'=> $option,
    'day' => $day,
    'week' => $week,
    'range_dates' => $range_dates,
    'page' => $_GET['page']??0,
    'saved' => $saved,
    'closeattendance' => $closeattendance,
    'initial' => (int) (strtotime($initialdate->format('Y-m-d')))/100,
    'final' => (int) (strtotime($finaldate->format('Y-m-d')))/100,
    'config'=> $configbutton,
    'limit' => $limit,
    'dirroot' => $dircomplement[1],
];
$form->display();
echo $OUTPUT->render_from_template('local_asistencia/history', $templatecontext);

echo $OUTPUT->footer();