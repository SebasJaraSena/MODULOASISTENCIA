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

 require_once($CFG->libdir . '/excellib.class.php'); // Include Moodle's built-in Excel library
 require_once(__DIR__ . '/../../../../config.php'); // Asegúrate de cargar el entorno Moodle

 use PhpOffice\PhpSpreadsheet\Spreadsheet;
 use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class report_donwloader {
    public static function attendance_report($result, $initialdate, $finaldate, $shortname) {
        global $DB, $USER;
    
        // Define the filename
        $filename = 'reporte_asistencia_'.$shortname.'_'.$initialdate.'_'.$finaldate.'_'.time().'.xlsx'; 
    
        if (!empty($result) && isset($result[0]['day0'])) {
            // Crear un nuevo archivo XLSX
            $spreadsheet = new Spreadsheet($filename);
            $spreadsheet->getProperties()
                ->setCreator('') // Autor vacío
                ->setLastModifiedBy('') // Última modificación vacía
                ->setTitle('Reporte de Ejemplo')
                ->setSubject('Reporte')
                ->setDescription('Descripción del reporte generado en Moodle')
                ->setKeywords('Moodle, Reporte')
                ->setCategory('Educación');
            
                 // Añadir contenido
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte');
            $headers = [];
            foreach ($result[0] as $key => $value) {
                $headers[] = $key;
            }
            $sheet->fromArray($headers, NULL, 'A1');
            $sheet->fromArray($result, NULL, 'A2');

    
            // Guardar el archivo temporalmente
    $filename = sys_get_temp_dir() . "/$filename";
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    
    // Descargar el archivo
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: ' . filesize($filename));
    readfile($filename);
    unlink($filename); // Eliminar el archivo temporal
    
            // Log the report generation
            try {
                $toinsert = new stdClass;
                $toinsert->code = "40";
                $toinsert->message = "Reporte generado con nombre \"$filename\".";
                $toinsert->date = date("Y-m-d H:i:s", time());
                $toinsert->userid = $USER->id;
                $DB->insert_record("local_asistencia_logs", $toinsert);
            } catch (\Throwable $th) {
                // Handle error
            }
        } else {
            // Handle case with no data
            \core\notification::add("No hay información de asistencia en el rango de fechas.", \core\output\notification::NOTIFY_ERROR);
            try {
                $toinsert = new stdClass;
                $toinsert->code = "43";
                $toinsert->message = "El reporte \"$filename\" no pudo ser generado. No se encontró información en la tabla.";
                $toinsert->date = date("Y-m-d H:i:s", time());
                $toinsert->userid = $USER->id;
                $DB->insert_record("local_asistencia_logs", $toinsert);
            } catch (\Throwable $th) {
                // Handle error
            }
        }
    }

   
   


    public static function attendance_report_csv($result, $initialdate, $finaldate, $shortname){
        global $DB,$USER;
        $filename ='reporte_asistencia_'.$shortname.'_'.$initialdate.'_'.$finaldate.'_'.time().'.csv'; // Se define nombre del archivo, concatenando el shortname, fecha inicial y fecha final, y el timestamps actual para diferenciar los archivos
        
        if (!empty($result) && isset($result[0]['day0'])) {
            // Set headers to indicate a CSV file download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            
            $output = fopen('php://output', 'w');
            
            
            // Fetch and write the column headers
            $headers = [];
            foreach ($result[0] as $key => $value) {
                $headers[] = $key;
            }
            fputcsv($output, $headers);
            
            // Fetch and write the data rows
            foreach($result as $row) {
                fputcsv($output, $row);
                
            }
            
            fclose($output);
            
            try {
                $toinsert = new stdClass;
                $toinsert->code = "40";
                $toinsert->message = "Reporte generado con nombre \"$filename\".";
                $toinsert->date = date("Y-m-d H:i:s", time());
                $toinsert->userid = $USER->id;
                $DB->insert_record("local_asistencia_logs",$toinsert);
            } catch (\Throwable $th) {
                //throw $th;
            }
        } else {
            \core\notification::add("No hay información de asistencia en el rango de fechas.", \core\output\notification::NOTIFY_ERROR);
            try {
                $toinsert = new stdClass;
                $toinsert->code = "43";
                $toinsert->message = "El reporte \"$filename\" no pudo ser generado. No se encontró información en la tabla.";
                $toinsert->date = date("Y-m-d H:i:s", time());
                $toinsert->userid = $USER->id;
                $DB->insert_record("local_asistencia_logs",$toinsert);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }

    // public static function attendance_report($result, $initialdate, $finaldate, $shortname) {
    //     global $DB, $USER;

    //     $filename = 'reporte_asistencia_'.$shortname.'_'.$initialdate.'_'.$finaldate.'_'.time().'.xlsx'; // Define the filename

    //     if (!empty($result) && isset($result[0]['day0'])) {
    //         // Create a new Excel workbook
    //         $workbook = new MoodleExcelWorkbook('php://output');
    //         $workbook->send($filename);  // Send the file to the browser for download

    //         $worksheet = $workbook->add_worksheet('Attendance Report');

    //         // Fetch and write the column headers
    //         $headers = [];
    //         $columnIndex = 0; // Excel columns start at 0 (A)
    //         foreach ($result[0] as $key => $value) {
    //             $headers[] = $key;
    //             $worksheet->write_string(0, $columnIndex++, $key); // Write headers to the first row
    //         }

    //         // Fetch and write the data rows
    //         $rowIndex = 1; // Data starts from row 2 (row 1 is for headers)
    //         foreach($result as $row) {
    //             $columnIndex = 0;
    //             foreach ($row as $value) {
    //                 $worksheet->write_string($rowIndex, $columnIndex++, (string)$value); // Write data to the sheet
    //             }
    //             $rowIndex++;
    //         }

    //         $workbook->close();  // Close the workbook and send the output to the browser

    //         // Log the report generation
    //         try {
    //             $toinsert = new stdClass;
    //             $toinsert->code = "40";
    //             $toinsert->message = "Reporte generado con nombre \"$filename\".";
    //             $toinsert->date = date("Y-m-d H:i:s", time());
    //             $toinsert->userid = $USER->id;
    //             $DB->insert_record("local_asistencia_logs", $toinsert);
    //         } catch (\Throwable $th) {
    //             // Handle error
    //         }
    //     } else {
    //         // Handle case with no data
    //         \core\notification::add("No hay información de asistencia en el rango de fechas.", \core\output\notification::NOTIFY_ERROR);
    //         try {
    //             $toinsert = new stdClass;
    //             $toinsert->code = "43";
    //             $toinsert->message = "El reporte \"$filename\" no pudo ser generado. No se encontró información en la tabla.";
    //             $toinsert->date = date("Y-m-d H:i:s", time());
    //             $toinsert->userid = $USER->id;
    //             $DB->insert_record("local_asistencia_logs", $toinsert);
    //         } catch (\Throwable $th) {
    //             // Handle error
    //         }
    //     }
    // }
    

    // public static function attendance_report($result, $initialdate, $finaldate, $shortname) {
    //     global $DB, $USER;
    
    //     // Define the filename
    //     $filename = 'reporte_asistencia_'.$shortname.'_'.$initialdate.'_'.$finaldate.'_'.time().'.xlsx'; 
    
    //     if (!empty($result) && isset($result[0]['day0'])) {
    //         // Set the path to store the temporary file
    //         $tempFilePath = '/path/to/temp/directory/' . $filename;  // Update with a valid temporary directory path
    
    //         // Create a new Excel workbook and store the file temporarily
    //         $workbook = new MoodleExcelWorkbook($tempFilePath);
    //         $workbook->send($filename);  // Send the file to the browser for download
    //         // Modify metadata: Title, Subject, Author, etc.
    //         $workbook->add_format(array(
    //             'creator' => '', // Autor vacío
    //             'lastmodified' => '', // Última modificación vacía
    //             'title' => '', // Título vacío
    //             'subject' => '', // Asunto vacío
    //             'description' => '', // Descripción vacía
    //             'keywords' => '', // Palabras clave vacías
    //             'category' => '' // Categoría vacía
    //         ));

    //         $worksheet = $workbook->add_worksheet('Attendance Report');

    
    //         // Fetch and write the column headers
    //         $headers = [];
    //         $columnIndex = 0; // Excel columns start at 0 (A)
    //         foreach ($result[0] as $key => $value) {
    //             $headers[] = $key;
    //             $worksheet->write_string(0, $columnIndex++, $key); // Write headers to the first row
    //         }
    
    //         // Fetch and write the data rows
    //         $rowIndex = 1; // Data starts from row 2 (row 1 is for headers)
    //         foreach($result as $row) {
    //             $columnIndex = 0;
    //             foreach ($row as $value) {
    //                 $worksheet->write_string($rowIndex, $columnIndex++, (string)$value); // Write data to the sheet
    //             }
    //             $rowIndex++;
    //         }
    
    //         // Close the workbook and write the output to the browser
    //         $workbook->close();  
    
    //         // Change the file owner (e.g., 'www-data' for web server)
    //         if (chown($tempFilePath, 'www-data')) {
    //             echo "File owner changed successfully!";
    //         } else {
    //             echo "Failed to change file owner.";
    //         }
    
    //         // Serve the file for download
    //         header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //         header('Content-Disposition: attachment; filename="'.$filename.'"');
    //         header('Content-Length: ' . filesize($filename));
    //         readfile($filename);  // Send the file content to the browser
    
    //         // Optionally, delete the file after download
    //         unlink($tempFilePath);
    
    //         // Log the report generation
    //         try {
    //             $toinsert = new stdClass;
    //             $toinsert->code = "40";
    //             $toinsert->message = "Reporte generado con nombre \"$filename\".";
    //             $toinsert->date = date("Y-m-d H:i:s", time());
    //             $toinsert->userid = $USER->id;
    //             $DB->insert_record("local_asistencia_logs", $toinsert);
    //         } catch (\Throwable $th) {
    //             // Handle error
    //         }
    //     } else {
    //         // Handle case with no data
    //         \core\notification::add("No hay información de asistencia en el rango de fechas.", \core\output\notification::NOTIFY_ERROR);
    //         try {
    //             $toinsert = new stdClass;
    //             $toinsert->code = "43";
    //             $toinsert->message = "El reporte \"$filename\" no pudo ser generado. No se encontró información en la tabla.";
    //             $toinsert->date = date("Y-m-d H:i:s", time());
    //             $toinsert->userid = $USER->id;
    //             $DB->insert_record("local_asistencia_logs", $toinsert);
    //         } catch (\Throwable $th) {
    //             // Handle error
    //         }
    //     }
    // }

   
    

}
