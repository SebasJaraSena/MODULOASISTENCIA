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

$context = context_system::instance();
$currenturl = new moodle_url('/local/asistencia/activities.php');
$dircomplement = explode("/",$currenturl->get_path());
$PAGE->set_url($currenturl);
$PAGE->set_context($context);
$PAGE->set_title('Actividades');
$PAGE->requires->js_call_amd('local_asistencia/attendance_observations', 'init');
$PAGE->requires->css(new moodle_url('/local/asistencia/styles/styles.css', array('v'=> time())));

require_capability('local/asistencia:view', $context);


$pageurl = isset($_GET['page'])?$_GET['page']:1;
$dbname = $DB->get_record('local_asistencia_config', ['name'=> 'dbname'])->value;
try {
    // Se consultan todos los registros en la tabla local_asistencia_logs
    $activities = local_asistencia_external::fetch_activities_report();
    $form = new edit();
    $numpages = (int) ceil(count($activities)/10); // Define la cantidad de páginas que va a tener la visual
    echo $OUTPUT->header();
    
    $pages=[];
    for($page = 1; $page <= $numpages; $page++){
        if ($page === 1){
            $pages[$page] =[
                'page' => $page,
                'current' => $page==$pageurl?1:0,
                'active' => ''
            ];
        }
        if(($page === 2 || $page === $numpages-1) && abs($currentpage-$page) >= 3 ){
            $pages[$page] = [
                'page'=> '...',
                'current'=> false,
                'active' => 'disabled'
            ];
        }
        if (abs($page - $currentpage) < 3){
            $pages[$page] =[
                'page' => $page,
                'current' => $page==$pageurl?1:0,
                'active' => ''
            ];
        }
        if ($page === $numpages){
            $pages[$page] =[
                'page' => $page,
                'current' => $page==$pageurl?1:0,
                'active' => ''
            ];
        }
    }
    $templatecontext = (object)[
        'activities' => array_slice($activities,($pageurl-1)*10,10),
        'pages' => array_values($pages)??[],
        'dirroot' => $dircomplement[1],
    ];
    echo $OUTPUT->render_from_template('local_asistencia/activities', $templatecontext);
    
    echo $OUTPUT->footer();
} catch (\Throwable $th) {
    try {
        $toinsert = new stdClass;
        $toinsert->code = "57";
        $toinsert->message = "No se pudo traer información de la base de datos externa $dbname.";
        $toinsert->date = date("Y-m-d H:i:s", time());
        $toinsert->userid = $USER->id;
        $DB->insert_record("local_asistencia_logs",$toinsert);
    } catch (\Throwable $th) {
        //throw $th;
    }
    $adminsarray = explode(",",$DB->get_record("config", ["name" => "siteadmins"])->value);
    
    $templatecontext= (object) [
        'courseid' => $data['courseid'],
        'config' => in_array($data['userid'], $adminsarray)?1:0,
        'dirroot' => $dircomplement[array_key_last($dircomplement)],
    ];
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_asistencia/error', $templatecontext);
    throw new \moodle_exception('dbconnectionerror', 'local_asistencia', '', $e->getMessage());
    echo $OUTPUT->footer();
}
