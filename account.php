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

$sql = "SELECT a.id, a.NAME, concat(substr(a.clientid, 1, 20), '...') clientid,
               concat(substr(a.clientsecret, 1, 20), '...') clientsecret,
               a.accesstoken,
               a.app_id, a.status
          FROM {block_uploadvimeo_account} a"; 

$alldata = $DB->get_records_sql($sql);
// Call the function at the top of the page to display an html table.
//echo block_uploadvimeo_display_in_table($alldata);

//$mform = new \block_uploadvimeo\local\account_form();
//$mform->display();

$renderer->display_page_account($alldata);

return;



