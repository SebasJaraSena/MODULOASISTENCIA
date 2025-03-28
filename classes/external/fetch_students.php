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
 
class fetch_students {
 
    public static function fetch_students($contextid, $courseid, $roleid, $offset, $limit, $conditions='') {

        global $DB;
        
        $students_cant = $DB->count_records('enrol', ['enrol' => 'manual', 'courseid' => $courseid]);
        
        // Check if the course exists
        if (!$students_cant) {
            return false;
        }
        // Getting the id record of manual enrolment for the $courseid value
        $enrolid = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], 'id');

        // Getting all the records whom are realted to the enrol id
        $enrollments = array_values(self::fetch_user_enrolments($enrolid->id));
        $enrollmentscopy =self::into_array_values($enrollments);
        // Building the string to query the users info
        $querystring = self::user_query_string($enrollments);
        
        // Getting only the users that belongs to the course and that were assigned as students
        $studentsids = array_values(self::fetch_user_roles($querystring, $roleid, $contextid));
        
        // Building the string to query the users info
        $querystring = self::user_query_string($studentsids)??'';
        
        
        // Fetch the users main info
        $query = empty($conditions)?'SELECT id, username, lastname, firstname,  email  FROM {user} WHERE id IN ('.$querystring.') ORDER BY lastname ASC OFFSET '.($offset*$limit).' LIMIT '.$limit:'SELECT id, username, lastname, firstname,  email  FROM {user} WHERE id IN ('.$querystring.') '.$conditions.' ORDER BY lastname ASC ';
        // Se trae la información de los aprendices
        $studentsinfo = !empty($querystring)?$DB->get_records_sql($query):[];
        

        // Student info format
        $students_data =[];
        // Format the student data
        foreach($studentsinfo as $studentinfo){ // Ciclo para formatear la información para que sea legible
            $id = $studentinfo->id;
            $filtered_array = array_values(array_filter($enrollmentscopy, function ($item) use ($id) { // Se filtra la información de los usuarios que están matriculados como aprendices
                return $item['userid'] == $id ;
            }));
            
            $student_data = [
                'id' => $id,
                'username' => $studentinfo->username,
                'lastname' => $studentinfo->lastname,
                'firstname' => $studentinfo->firstname,
                'email' => $studentinfo->email,
                'status' => $filtered_array[0]['status'],
            ];
            $students_data[]=$student_data;
        }
        
        // Retun the students data formatted
        return ['students_data' => $students_data, 'pages' => empty($conditions)?(int) ceil(count($studentsids)/$limit):0, 'studentsamount' => count($studentsids)];
    }

    // Function to fetch all the enrollments related with enrolid
    public static function fetch_user_enrolments($enrolid){
        global $DB;

        // Fecth the user enrollments
        $userenrolments = $DB->get_records('user_enrolments', ['enrolid'=> $enrolid], '', 'userid, status');
        
        return $userenrolments;
    }

    // Function to fetch all the students related to a context and userids
    public static function fetch_user_roles($querystring, $roleid, $contextid){
        global $DB;
        
        // Fecth the user enrollments
        $userenrolments = empty($querystring)?[]:$DB->get_records_sql('SELECT userid FROM {role_assignments} WHERE roleid=:roleid AND contextid = :contextid AND userid IN ('.$querystring.')', array('roleid'=> $roleid, 'contextid' => $contextid));
    
        return $userenrolments;
    }

    // Function to build query string with userid
    public static function user_query_string($array){
        $string='';
        foreach($array as $item){ // Ciclo para agregar los ids a una cadena de texto separada por ","
            $string.=$item->userid.',';
        }

        return rtrim($string, ","); // Se retrna la cadena generada eliminando la última ","
    }

    // Function to transform all the items into array values
    public static function into_array_values($array){
        $len = count($array);
        for($i = 0 ; $i < $len; $i++){ // Ciclo para convertir la información en arreglos asociativos
            $array[$i] = get_object_vars($array[$i]);
        }
        
        return $array;
    }

    // Function to check if the attendance should be close or open to be edit
    public static function close_validation($courseid){
        global $USER, $DB;
        $userid = $USER->id;
        $currentdate = date('Y-m-d', time());
        $context = context_course::instance($courseid);
        
        $historyattendance=[];
        try {
            $historyattendance = $DB->get_record('local_asistencia_permanente', ['course_id'=>$courseid]); // Consulta de tabla histórica de base de datos
            $adminrole = $DB->get_record('role_assignments', ['contextid'=> $context->id, 'userid'=>$userid], 'roleid');
            $admin = $adminrole?$adminrole->roleid != 5:$adminrole;
        } catch (\Throwable $th) {
            //throw $th;
        }
        
        $attendacearray = $admin?json_decode($historyattendance->full_attendance,true):[]; // Traducción de JSON a array
        $filtered = !empty($attendacearray)?array_filter($attendacearray, function ($item) use ($userid, $currentdate){ // Filtro para encontrar los datos relacionados con el id del instructor
            return $item['TEACHER_ID'] == $userid && $item['DATE']==$currentdate;
        }):[];
        
        // Retorna  si el usuario puede editar o no
        return !empty($admin)&&empty($filtered)?0:1;
        
    }

    // Function to check if the attendance should be close or open to be edit
    public static function close_validation_retard($courseid, $initial, $final){
        global $USER, $DB;
        $userid = $USER->id;
        $context = context_course::instance($courseid);
        
        $historyattendance=[];
        try {
            $historyattendance = $DB->get_record('local_asistencia_permanente', ['course_id'=>$courseid]); // Consulta de tabla histórica de base de datos
            $adminrole = $DB->get_record('role_assignments', ['contextid'=> $context->id, 'userid'=>$userid], 'roleid');
            $admin = $adminrole?$adminrole->roleid != 5:$adminrole;
        } catch (\Throwable $th) {
            //throw $th;
        }
        
        $attendacearray = $admin?json_decode($historyattendance->full_attendance,true):[]; // Traducción de JSON a array
        $filtered = !empty($attendacearray)?array_filter($attendacearray, function ($item) use ($userid, $initial, $final){ // Filtro para encontrar los datos relacionados con el id del instructor
            return $item['TEACHER_ID'] == $userid && $item['DATE'] >= $initial && $item['DATE'] <= $final;
        }):[];
        
        // Retorna  si el usuario puede editar o no
        return !empty($admin)&&(count($filtered)<7)?0:1;
        
    }
 }
 