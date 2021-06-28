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
 * Account form
 *
 * @package block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uploadvimeo\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class account_form extends \moodleform {
    
    /**
     * Defines forms elements
     */
    protected function definition() {
        
        $mform = $this->_form;
        
        $mform->addElement('header', 'account', 'Conta');
         
        // Adding the standard "name" field.
        $fieldname ="name";
        $visiblename = get_string($fieldname, 'block_uploadvimeo'); 
        $mform->addElement('text', $fieldname, $visiblename, array('size'=>'255'));
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Adding the standard "clientid" field.
        $fieldname ="clientid";
        $visiblename = get_string($fieldname, 'block_uploadvimeo');
        $mform->addElement('text', $fieldname, $visiblename, array('size'=>'255'));
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Adding the standard "clientsecret" field.
        $fieldname ="clientsecret";
        $visiblename = get_string($fieldname, 'block_uploadvimeo');
        $mform->addElement('text', $fieldname, $visiblename, array('size'=>'255'));
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Adding the standard "accesstoken" field.
        $fieldname ="accesstoken";
        $visiblename = get_string($fieldname, 'block_uploadvimeo');
        $mform->addElement('text', $fieldname, $visiblename, array('size'=>'255'));
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Adding the standard "app_id" field.
        $fieldname ="app_id";
        $visiblename = get_string($fieldname, 'block_uploadvimeo');
        $mform->addElement('text', $fieldname, $visiblename, array('size'=>'50'));
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', 50), 'maxlength', 50, 'client');
        
        // Adding "status" field.
        $fieldname = 'status';
        $visiblename = get_string($fieldname, 'block_uploadvimeo');
        $status = array();
        $status[0] = get_string('inactive', 'block_uploadvimeo');
        $status[1] = get_string('active', 'block_uploadvimeo');
        $mform->addElement('select', $fieldname, $visiblename, $status);
        $mform->setDefault($fieldname, 1);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        
        $mform->addElement('hidden','action');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $this->add_action_buttons();
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        return $errors;
    }
}
