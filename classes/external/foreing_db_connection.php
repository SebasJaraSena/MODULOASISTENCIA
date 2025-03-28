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

 //namespace local_aistencia\external;
 
 defined('MOODLE_INTERNAL') || die();
 
 class foreing_db_connection {
 
     private static $externaldb;
 
     /**
      * Constructor to set up the external database connection.
      */
     public function __construct() {
         global $DB, $USER;
 
         // Database connection details
         $dbhost = $DB->get_record('local_asistencia_config', ['name'=> 'dbhost'])->value; // Se llama el host que fue guardado en la tabla "local_asistencia_config"
         $dbname = $DB->get_record('local_asistencia_config', ['name'=> 'dbname'])->value; // Se llama el nombre de la base de datos que fue guardado en la tabla "local_asistencia_config"
         $dbuser = $DB->get_record('local_asistencia_config', ['name'=> 'dbuser'])->value; // Se llama el usuario que fue guardado en la tabla "local_asistencia_config"
         $dbpass = $DB->get_record('local_asistencia_config', ['name'=> 'dbpassword'])->value; // Se llama la contraseña que fue guardada en la tabla "local_asistencia_config"
         $dbport = $DB->get_record('local_asistencia_config', ['name'=> 'dbport'])->value; // Se llama el puerto que fue guardado en la tabla "local_asistencia_config"
         
 
         $dsn = "pgsql:host=$dbhost;port=$dbport;dbname=$dbname";
 
         try { // Se establece la conexión
             self::$externaldb = new \PDO($dsn, $dbuser, $dbpass);
             self::$externaldb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
             self::$externaldb->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
         } catch (\PDOException $e) {
            try {
                $toinsert = new stdClass;
                $toinsert->code = "54";
                $toinsert->message = "No se pudo establecer conexión con la base de datos $dbname.";
                $toinsert->date = date("Y-m-d H:i:s", time());
                $toinsert->userid = $USER->id;
                $DB->insert_record("local_asistencia_logs",$toinsert);
            } catch (\Throwable $th) {
                //throw $th;
            }
             
         }
     }
 
     /**
      * Function to perform a query on the external database.
      * @param string $query The SQL query to execute
      * @param array $params Optional parameters for the query
      * @return array The result set of the query
      */
     public static function query($query, $params) {
        global $USER, $DB;
         try { // Ejecuta las queries
             $stmt = self::$externaldb->prepare($query);
             $stmt->execute($params);
             return $stmt->fetchAll();
         } catch (\PDOException $e) {
            try {
                $toinsert = new stdClass;
                $toinsert->code = "55";
                $toinsert->message = "No se pudo ejecutar la query \" $query \".";
                $toinsert->date = date("Y-m-d H:i:s", time());
                $toinsert->userid = $USER->id;
                $DB->insert_record("local_asistencia_logs",$toinsert);
            } catch (\Throwable $th) {
                //throw $th;
            }
         }
     }
 }
 