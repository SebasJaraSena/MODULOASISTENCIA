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
namespace local_asistencia;

use core\dataformat;
use coding_exception;
use core_php_time_limit;
use stored_file;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/classes/dataformat.php'); // Include Moodle's PDF library

class detailed_report_donwloader extends dataformat{
    public static function generate_report_pdf($filename, $arraydata, $dataformat, $userName, $shortname, $initialdate, $finaldate) {
        global $DB, $USER;
        $format = self::get_format_instance($dataformat);
        $title = "\n\n\n\n\nReporte detallado\n\n\n\n\n Curso:\n\n$shortname\n\n\nRango de fechas:\n\n$initialdate - $finaldate\n\n\n\n\nGenerado por el usuario:\n\n$userName\n\n\n\n\n\n\n\n\n\n";
        // The data format export could take a while to generate.
        core_php_time_limit::raise();
        
        // Close the session so that the users other tabs in the same session are not blocked.
        \core\session\manager::write_close();
        

        // If this file was requested from a form, then mark download as complete (before sending headers).
        \core_form\util::form_download_complete();

        $format->set_filename($filename);
        $format->send_http_headers();
        
        $format->start_output();
        $format->start_sheet(["$title"]);
        $format->close_sheet(["$title"]);
        $rownum = 0;
        foreach($arraydata as $person){
            $headers = self::headersDays(array_slice($person,0,1),array_slice($person[0], 1));
            $format->start_sheet($headers);
            
            $limit = count($person)-1;
            for($i = 0; $i < $limit; $i++){
                $format->write_record(array_slice($person[$i], 0), $rownum++);
            }
            $format->close_sheet($headers);
        }
        $format->close_output();

        try {
            $toinsert = new \stdClass;
            $toinsert->code = "40";
            $toinsert->message = "Reporte detallado generado con nombre \"$filename\".";
            $toinsert->date = date("Y-m-d H:i:s", time());
            $toinsert->userid = $USER->id;
            $DB->insert_record("local_asistencia_logs",$toinsert);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    private static function headersDays($array, $days){
        
        foreach($days as $key=>$v){
            $array[$key] = $key;
        }
        return $array;
    }

    public static function activityReport($filename, $userid){
        global $DB;
        
        \core\notification::add("No hay información de asistencia en el rango de fechas.", \core\output\notification::NOTIFY_ERROR);
        try {
            $toinsert = new \stdClass;
            $toinsert->code = "43";
            $toinsert->message = "El reporte \"$filename\" no pudo ser generado. No se encontró información en la tabla.";
            $toinsert->date = date("Y-m-d H:i:s", time());
            $toinsert->userid = $userid;
            $DB->insert_record("local_asistencia_logs",$toinsert);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
  
}
