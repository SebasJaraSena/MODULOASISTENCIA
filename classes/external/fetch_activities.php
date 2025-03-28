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
 * Plugin capabilities for the local_asistencia plugin.
 *
 * @package   local_asistencia
 * @copyright Luis Pérez
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
 
require_once("$CFG->libdir/externallib.php");
 
class fetch_activities {
 
    public static function fetch_attendance_report($attendancehistory, $initialdate, $finaldate, $cumulous, $userid) {

        global $DB;
        
        $studentsinfo=[];
        $arrayopts = ['-1'=> "SUSPENDIDO", 0 => "INASISTENCIA", 1 => "ASISTIO", 2 => "LLEGA_TARDE", 3 => "EXCUSA_MEDICA", '-8'=> "NA",]; // Se establece el significado de los valores guardados en la asistencia
        foreach($attendancehistory as $index=>$ah){ // Ciclo para iterar sobre los datos de la asistencia
            $id = $ah['student_id'];
            $studentsinfo[$index]= json_decode(json_encode($DB->get_record('user',['id'=>$id],'username, lastname, firstname, email, phone1')), true);
            $attendancearray =json_decode($ah['full_attendance'], true);
            
            // Se filtra por rango de fechas o por el rango de fecha y por el id del instructor según se cumpla la condición
            $filtereddates = $cumulous==1?array_filter($attendancearray, function ($item) use ($initialdate, $finaldate){ // Se filtra solo por rango de fechas
                return ($item['DATE'] >= $initialdate) && ($item['DATE'] <= $finaldate);
            }):array_filter($attendancearray, function ($item) use ($userid,$initialdate, $finaldate){ // Se filtra por rango de fechas y por id de instructor
                return ($item['TEACHER_ID'] == $userid) && ($item['DATE'] >= $initialdate) && ($item['DATE'] <= $finaldate);
            });
            $i = 0;
            foreach($filtereddates as $attendanceinfo){ // Ciclo para formatear la información de la asistencia para que sea legible
                $studentsinfo[$index]["day$i"]= $attendanceinfo['DATE'];
                $studentsinfo[$index]["state$i"]= $arrayopts[$attendanceinfo['ATTENDANCE']];
                $studentsinfo[$index]["time$i"]= $attendanceinfo['AMOUNTHOURS'];
                $teacherinfo = json_decode(json_encode($DB->get_record('user',['id'=>$attendanceinfo['TEACHER_ID']],'username, lastname, firstname, email, phone1')), true);
                $studentsinfo[$index]["teacher$i"]= $teacherinfo['phone1'].'-'.$teacherinfo['username'].'-'.$teacherinfo['lastname'].'-'.$teacherinfo['firstname'].'-'.$teacherinfo['email'];
                $i++;
            }
            
            //$studentsinfo[$index]['attendance'] = json_encode($filtereddates);
            
        }
        // Function to sort the data by "lastname"
        usort($studentsinfo, function($a, $b) { 
            return strcmp($a['lastname'], $b['lastname']);
        });
        // Now $studentsinfo is sorted by lastname
        return $studentsinfo;
    }

    public static function fetch_attendance_report_detailed($attendancehistory, $initialdate, $finaldate, $cumulous, $userid) {

        global $DB;
        
        $studentsinfo=[];
        $arrayopts = ['-1'=> "SUSPENDIDO", 0 => "INASISTENCIA", 1 => "ASISTIO", 2 => "LLEGA_TARDE", 3 => "EXCUSA_MEDICA", '-8'=> "NA",]; // Se establece el significado de los valores guardados en la asistencia
        foreach($attendancehistory as $index=>$ah){ // Ciclo para iterar sobre los datos de la asistencia
            $id = $ah['student_id'];
            $studentsinfo[$index]= json_decode(json_encode($DB->get_record('user',['id'=>$id],'username, lastname, firstname, email, phone1')), true);
            $attendancearray =json_decode($ah['full_attendance'], true);
            
            // Se filtra por rango de fechas o por el rango de fecha y por el id del instructor según se cumpla la condición
            $filtereddates = $cumulous==1?array_filter($attendancearray, function ($item) use ($initialdate, $finaldate){ // Se filtra solo por rango de fechas
                return ($item['DATE'] >= $initialdate) && ($item['DATE'] <= $finaldate);
            }):array_filter($attendancearray, function ($item) use ($userid,$initialdate, $finaldate){ // Se filtra por rango de fechas y por id de instructor
                return ($item['TEACHER_ID'] == $userid) && ($item['DATE'] >= $initialdate) && ($item['DATE'] <= $finaldate);
            });
            $i = 0;
            foreach($filtereddates as $attendanceinfo){ // Ciclo para formatear la información de la asistencia para que sea legible
                $studentsinfo[$index]["day$i"]= $attendanceinfo['DATE'];
                $studentsinfo[$index]["state$i"]= $arrayopts[$attendanceinfo['ATTENDANCE']];
                $studentsinfo[$index]["time$i"]= $attendanceinfo['AMOUNTHOURS'];
                $teacherinfo = json_decode(json_encode($DB->get_record('user',['id'=>$attendanceinfo['TEACHER_ID']],'username, lastname, firstname, email, phone1')), true);
                $studentsinfo[$index]["teacher$i"]= $teacherinfo['phone1'].'-'.$teacherinfo['username'].'-'.$teacherinfo['lastname'].'-'.$teacherinfo['firstname'].'-'.$teacherinfo['email'];
                $i++;
            }
            
            $studentsinfo[$index]['attendance'] = json_encode($filtereddates);
            
        }
        // Function to sort the data by "lastname"
        usort($studentsinfo, function($a, $b) { 
            return strcmp($a['lastname'], $b['lastname']);
        });
        // Now $studentsinfo is sorted by lastname
        return $studentsinfo;
    }

    public static function fetch_activities_report() {

        global $DB;
        
        $activitiesinfo= $DB->get_records("local_asistencia_logs",null,'date DESC','*'); // Se obtiene toda al información de los logs
        $activitiesarray=[];
        foreach($activitiesinfo as $ai){ // Ciclo para formatear la información para hacerla legible
            $userid = $ai->userid;
            $activity['code'] = $ai->code;
            $activity['message'] = $ai->message;
            $activity['date'] = $ai->date;
            $activity['username'] = $DB->get_record('user',['id'=> $userid])->username;
            $activitiesarray[] = $activity;
        }
        
        return $activitiesarray;
    }
 }
 
 ?>