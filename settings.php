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
 * Defines the form for editing upload video to Vimeo block instances.
 *
 * @package    block_uploadvimeo
 * @copyright  2020 CCEAD PUC-Rio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    
    $default_id = '';
    $default_secret = '';
    $default_token = '';
    // $default_size = 524288000;

    $settings->add(new admin_setting_configtext('block_uploadvimeo/config_clientid', 
            new lang_string('config_clientid', 'block_uploadvimeo'), 
            '', $default_id, PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_uploadvimeo/config_clientsecret',
            new lang_string('config_clientsecret', 'block_uploadvimeo'),
            '', $default_secret, PARAM_TEXT));
    
    $settings->add(new admin_setting_configtext('block_uploadvimeo/config_accesstoken',
            new lang_string('config_accesstoken', 'block_uploadvimeo'),
            '', $default_token, PARAM_TEXT));
    
}
