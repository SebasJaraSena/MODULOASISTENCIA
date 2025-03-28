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

require_once("$CFG->libdir/formslib.php");

class edit extends moodleform{

    public function definition(){
        global $USER, $DB;
        // Clean $_GET parameters by removing "amp;"
        foreach ($_GET as $key => $value) {
            // Clean the key and reassign the value to the cleaned key
            $cleanKey = str_replace('amp;', '', $key);
            if ($cleanKey !== $key) {
                $_GET[$cleanKey] = $value; // Reassign value to cleaned key
                unset($_GET[$key]); // Remove the original key
            }
        }

        $courseid = $_GET['courseid'];
        $userid = $USER->id;
        
        $adminsarray = array_values($DB->get_records('config', ['name' => 'siteadmins']));
        $adminavailable = in_array($userid, explode(",", $adminsarray[0]->value));
        $mform = $this->_form;
        
        if (isset($_GET['courseid'])) {
            $mform->addElement('hidden', 'data', $_GET['data']);
            $mform->setType('data', PARAM_RAW);
        }
        if (isset($_GET['page'])) {
            $mform->addElement('hidden', 'page', $_GET['page']);
            $mform->setType('page', PARAM_INT);
        }
        if (isset($_GET['info'])) {
            $mform->addElement('hidden', 'info', $_GET['info']);
            $mform->setType('info', PARAM_TEXT);
        }
       
        $vpage = isset($_GET['info'])? ($_GET['info']==='h' ?'history':($_GET['info']=='p'?'previous_attendance':'index')):'index';
        $currenturl = $courseid? new moodle_url("/local/asistencia/$vpage.php", $_GET): new moodle_url('/local/asistencia/manage.php'); 
        $mform->updateAttributes(['action' => $currenturl->out()]);

        if($adminavailable && !$courseid){
            $this->dbConfiguration();
        } else{
            $this->filters($courseid);
        }
        
    }
    
    public function filters($courseid){
        $mform = $this->_form;
        
        $mform->addElement('select', 'filters', 'Filtros:' , array('Nombre', 'Apellido', 'Correo', 'Documento'));
        $mform->addElement('text', 'filterValue', '');
        
        $this->add_action_buttons(true,'Filtrar Usuarios');
    }
    
    public function dbConfiguration(){
        global $DB;
        
        $mform = $this->_form;
        
        $mform->addElement('text', 'dbhost', get_string('dbhost','local_asistencia'));
        $mform->addRule('dbhost', get_string('error', 'local_asistencia'), 'required');
        $mform->setDefault('dbhost', $DB->get_record('local_asistencia_config', ['name' => 'dbhost'])->value);
        $mform->addElement('text', 'dbport', get_string('dbport','local_asistencia'));
        $mform->addRule('dbport', get_string('error', 'local_asistencia'), 'required');
        $mform->setDefault('dbport', $DB->get_record('local_asistencia_config', ['name' => 'dbport'])->value);
        $mform->addElement('text', 'dbname', get_string('dbname','local_asistencia'));
        $mform->addRule('dbname', get_string('error', 'local_asistencia'), 'required');
        $mform->setDefault('dbname', $DB->get_record('local_asistencia_config', ['name' => 'dbname'])->value);
        $mform->addElement('text', 'dbuser', get_string('dbuser','local_asistencia'));
        $mform->addRule('dbuser', get_string('error', 'local_asistencia'), 'required');
        $mform->setDefault('dbuser', $DB->get_record('local_asistencia_config', ['name' => 'dbuser'])->value);
        $mform->addElement('password', 'dbpassword', get_string('dbpassword','local_asistencia'));
        $mform->addRule('dbpassword', get_string('error', 'local_asistencia'), 'required');
        $mform->setDefault('dbpassword', $DB->get_record('local_asistencia_config', ['name' => 'dbpassword'])->value);
        $this->add_action_buttons(false);
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
