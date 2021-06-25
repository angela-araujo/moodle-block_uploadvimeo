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
 * Class Utilities.
 *
 * @package   block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio (@angela-araujo)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uploadvimeo\local;

class util {
    static public function save_json_file($object, $namefile='log_sync.json') {
        
        $filenamecomplete = self::get_path_temp($namefile);
        
        $fp = fopen($filenamecomplete, 'w');
        fwrite($fp, json_encode($object));
        fclose($fp);
    }
    
    static public function get_json_file($namefile='log_sync.json') {
        
        $filenamecomplete = self::get_path_temp($namefile);
        
        if (file_exists($filenamecomplete)) {            
            return json_decode(file_get_contents($filenamecomplete));
        }
        return false;
    }
    
    
    function get_param_file($param) {
        
        if (isset($_FILES[$param])) {           
            
            // Strip all suspicious characters from filename (moodlelib.php).
            $filename = fix_utf8($_FILES[$param]['name']);
            $filename = preg_replace('~[[:cntrl:]]|[&<>"`\|\':\\\\/]~u', '', $filename);
            if ($filename === '.' || $filename === '..') {
                $filename = '';
            }
            
            $newthumbnail = self::get_path_temp($filename);
            move_uploaded_file($_FILES[$param]['tmp_name'], $newthumbnail);
            return $newthumbnail;
        } else
            return NULL;
            
    }
    
    static public function delete_file_temp ($file_pointer) {
        return unlink($file_pointer);
    }
    
    static private function get_path_temp($filename) {
        global $CFG;
        $pathtemp = $CFG->dataroot . DIRECTORY_SEPARATOR .'temp'. DIRECTORY_SEPARATOR . 'vimeo';
        if (!is_dir($pathtemp)) {
            mkdir($pathtemp, 0777, true);
        }
        
        return $pathtemp . DIRECTORY_SEPARATOR  . $filename;
    }
    
    
}