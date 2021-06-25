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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * block_uploadvimeo account page
 *
 * @package block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require ('../../config.php');

$courseid = required_param('courseid', PARAM_INT);

// Set course related variables.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

$PAGE->set_course($course);

// Set up the page.
$PAGE->set_url('/blocks/uploadvimeo/account.php', array());
$PAGE->set_context($coursecontext);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_uploadvimeo'));

require_login();

require_capability('block/uploadvimeo:seepagevideos', $coursecontext);

$renderer = $PAGE->get_renderer('block_uploadvimeo');

$tablename = 'block_uploadvimeo_account';
$fieldlist = 'id, name, clientid, clientsecret, accesstoken, app_id, status';

$alldata = $DB->get_records($tablename, [], null, $fieldlist);
// Call the function at the top of the page to display an html table.
//echo block_uploadvimeo_display_in_table($alldata);

$renderer->display_page_account($alldata);




/**
 * A function that will display any set of records from the $DB
 * (as long as each record has an id field)
 */
function block_uploadvimeo_display_in_table($data){
    
    // If we do not have any data, lets just return a string to that effect.
    if(!$data || empty($data)){
        return 'No records found';
    }
    
    // Make sure that we are an array.
    if(!is_array($data)){
        $data = array($data);
    }
    
    $head = false;
    $baselink = '/blocks/uploadvmeo/account.php';
    $table = new html_table();
    
    foreach($data as $onedata){
        $onearray = get_object_vars($onedata);
        
        // Build the head row.
        if(!$head){
            $head = true;
            $table->head = array_keys($onearray);
            $table->head[] = get_string('edit');
            $table->head[] = get_string('delete');
        }
        // Build all the other rows, adding links at the end.
        $rowdata = array_values($onearray);
        
        $editlink = html_writer::link(new moodle_url($baselink, []), 'edit');
        $rowdata[] = $editlink;
        
        $deletelink = html_writer::link( new moodle_url($baselink,[]), 'delete');
        $rowdata[] = $deletelink;
        
        $table->data[] = $rowdata;
    }
    
    return html_writer::table($table);
    
} // End of display in table.

return;

