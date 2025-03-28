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

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__ .'/classes/form/edit.php');


$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/asistencia/manage.php'));
$PAGE->set_context($context);
$PAGE->set_title('Configuración Asistencia');

require_capability('local/asistencia:manage', $context);

$form = new edit();

echo $OUTPUT->header();
if ($fromform = $form->get_data()){
    foreach($fromform as $key =>$value){
        if($key != 'submitbutton'){
            $record_insert_update = $DB->get_record('local_asistencia_config', ['name' => $key]);
            if(empty($record_insert_update)){
                $record_insert_update = new stdClass();
                $record_insert_update->name = $key;
                $record_insert_update->value= $value;
                $DB->insert_record('local_asistencia_config', $record_insert_update);
            }else{
                $record_insert_update->value= $value;
                $DB->update_record('local_asistencia_config', $record_insert_update);
            }
        }
    }
    try { // Inserción a logs
        $toinsert = new stdClass;
        $toinsert->code = "20";
        $toinsert->message = "Configuración de la base de datos guardada con éxito.";
        $toinsert->date = date("Y-m-d H:i:s", time());
        $toinsert->userid = $USER->id;
        $DB->insert_record("local_asistencia_logs",$toinsert);
    } catch (\Throwable $th) {
        //throw $th;
    }
    \core\notification::add("Configuración guardada éxitosamente.", \core\output\notification::NOTIFY_SUCCESS);
}

$templatecontext = (object) [
    'h1' =>get_string('pluginmanagetitle', 'local_asistencia'),
];

echo $OUTPUT->render_from_template('local_asistencia/manage', $templatecontext);
echo $form->display();
echo $OUTPUT->footer();
