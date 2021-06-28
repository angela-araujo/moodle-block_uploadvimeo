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

global $DB;

$accountid = optional_param('id', -1, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash

$course = $DB->get_record('course', array('id' => $COURSE->id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/uploadvimeo/account.php', array());
$PAGE->set_context($coursecontext);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_uploadvimeo'));

require_login();

// @TODO: create a capability for this.
//require_capability('block/uploadvimeo:seepagevideos', $coursecontext);

$renderer = $PAGE->get_renderer('block_uploadvimeo');

  
if ($action == 'delete' and confirm_sesskey()) {

    $sql = "SELECT a.* 
              FROM {block_uploadvimeo_account} a 
             WHERE a.id = :id
               AND a.id NOT IN (SELECT f.accountid FROM {block_uploadvimeo_folders} f)
               AND a.id NOT IN (SELECT v.accountid FROM {block_uploadvimeo_videos} v)  ";
    
    $account = $DB->get_record_sql($sql, ['id' => $accountid]);
    
    if (!$account) {
        redirect($PAGE->url, get_string('deleted_error', 'block_uploadvimeo', $account), 2);
    }
    
    if ($confirm != md5($accountid)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('delete', 'block_uploadvimeo') . ' <b>' . $account->name . '</b>');
        
        $params_delete = array('action' => 'delete', 'id' => $accountid, 'confirm'=> md5($accountid), 'sesskey'=>sesskey());
        $deleteurl = new moodle_url($PAGE->url, $params_delete);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');
        
        echo $OUTPUT->confirm(get_string('delete_check', 'block_uploadvimeo', $account), $deletebutton, $PAGE->url);
        echo $OUTPUT->footer();
        die;
        
    } else if (data_submitted()) {
        if ($DB->delete_records('block_uploadvimeo_account', ['id' => $accountid])) {
            redirect($PAGE->url, get_string('deleted_success', 'block_uploadvimeo'), 2);        
        } else {
            redirect($PAGE->url, get_string('deleted_error', 'block_uploadvimeo'), 2);
        }
    }    
    
}

$mform = new block_uploadvimeo\local\account_form();

if ($mform->is_cancelled()) {
    
    redirect($PAGE->url);
    exit;
    
} else if ($data = $mform->get_data()) {    
    
    if ($action == "edit"){
        $DB->update_record('block_uploadvimeo_account', $data);
        redirect($PAGE->url, get_string('edited_success', 'block_uploadvimeo'), 2);
    }
    
    if ($action == "add") {
        $DB->insert_record('block_uploadvimeo_account', $data);
        redirect($PAGE->url, get_string('added_success', 'block_uploadvimeo'), 2);        
    } 
}

echo $renderer->header();

// If the action is specified as "edit" then we show the edit form
if ($action == 'edit'){
    
    $data = new stdClass();
    $data = $DB->get_record('block_uploadvimeo_account', array('id' => $accountid));
    $data->action = $action;
    $data->id = $accountid;
    $mform->set_data($data);
    echo $renderer->heading('Contas Vimeo', 2);    
    $mform->display();
    
} else if ($action == 'add') {
    
    $data = new stdClass();
    $data->action = $action;
    $data->id = $accountid;
    $mform->set_data($data);
    $mform->display(); 
    
}

echo $renderer->heading(get_string('account_title', 'block_uploadvimeo'), 2);
$sql = "SELECT a.id, a.NAME, concat(substr(a.clientid, 1, 20), '...') clientid,
               concat(substr(a.clientsecret, 1, 20), '...') clientsecret,
               a.accesstoken,
               a.app_id, a.status
          FROM {block_uploadvimeo_account} a";

$alldata = $DB->get_records_sql($sql);

echo $renderer->display_page_account($alldata);

echo $renderer->footer();
