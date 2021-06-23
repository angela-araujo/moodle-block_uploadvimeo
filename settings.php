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
    
    global $DB;
    if ($accounts = $DB->get_records('block_uploadvimeo_account', ['status' => 1])) {
        $choice_account = array();
        foreach ($accounts as $account) {
            $choice_account[$account->id] = $account->name;
        }
    }
    
    // Setting option variables.
    $yesno = array(
            new lang_string('no'),
            new lang_string('yes'),
    );
    $titlevisibility = array(
            new lang_string('hide', 'block_uploadvimeo'),
            new lang_string('show', 'block_uploadvimeo'),
            new lang_string('user', 'block_uploadvimeo'),
    );
    $whocomment = array(
            new lang_string('anybody', 'block_uploadvimeo'),
            new lang_string('contacts', 'block_uploadvimeo'),
            new lang_string('nobody', 'block_uploadvimeo'),
    );
    $view = array(
            new lang_string('anybody', 'block_uploadvimeo'),
            new lang_string('contacts', 'block_uploadvimeo'),
            new lang_string('disable', 'block_uploadvimeo'),
            new lang_string('nobody', 'block_uploadvimeo'),
            new lang_string('password', 'block_uploadvimeo'),
            new lang_string('unlisted', 'block_uploadvimeo'),
            new lang_string('users', 'block_uploadvimeo'),
    );
    $embed = array(
            new lang_string('private', 'block_uploadvimeo'),
            new lang_string('public', 'block_uploadvimeo'),
            new lang_string('whitelist', 'block_uploadvimeo'),
    );
    
    // Access.
    $settings->add(new admin_setting_heading('block_uploadvimeo/config_headingaccess', new lang_string('config_headingaccess', 'block_uploadvimeo'), ''));
    
    $name = 'block_uploadvimeo/accountvimeo';
    $visiblename = new lang_string('accountvimeo', 'block_uploadvimeo');
    $description = new lang_string('accountvimeo_desc', 'block_uploadvimeo');
    $default = 0;
    $setting = new admin_setting_configselect($name, $visiblename, $description, $default, $choice_account);
    $settings->add($setting);
    
    /*
    // Embed.
    $settings->add(new admin_setting_heading('block_uploadvimeo/config_headingembed', new lang_string('config_headingembed', 'block_uploadvimeo'), ''));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedbuttonsembed', new lang_string('config_embedbuttonsembed', 'block_uploadvimeo'), new lang_string('config_embedbuttonsembed_desc', 'block_uploadvimeo'), 0, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedbuttonsfullscreen', new lang_string('config_embedbuttonsfullscreen', 'block_uploadvimeo'), new lang_string('config_embedbuttonsfullscreen_desc', 'block_uploadvimeo'), 1, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedbuttonslike', new lang_string('config_embedbuttonslike', 'block_uploadvimeo'), new lang_string('config_embedbuttonslike_desc', 'block_uploadvimeo'), 0, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedbuttonsshare', new lang_string('config_embedbuttonsshare', 'block_uploadvimeo'), new lang_string('config_embedbuttonsshare_desc', 'block_uploadvimeo'), 0, $yesno));
    $settings->add(new admin_setting_configcolourpicker('block_uploadvimeo/config_embedcolor', new lang_string('config_embedcolor', 'block_uploadvimeo'), '', '#ff9933'));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedlogoscustomactive', new lang_string('config_embedlogoscustomactive', 'block_uploadvimeo'), '', 0, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedlogosvimeo', new lang_string('config_embedlogosvimeo', 'block_uploadvimeo'), '', 0, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedtitlename', new lang_string('config_embedtitlename', 'block_uploadvimeo'), new lang_string('config_embedtitlename_desc', 'block_uploadvimeo'), 0, $titlevisibility));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_embedtitleportrait', new lang_string('config_embedtitleportrait', 'block_uploadvimeo'), new lang_string('config_embedtitlename_desc', 'block_uploadvimeo'), 0, $titlevisibility));
    $settings->add(new admin_setting_configtext('block_uploadvimeo/config_width', new lang_string('config_width', 'block_uploadvimeo'), '', 600, PARAM_INT));
    $settings->add(new admin_setting_configtext('block_uploadvimeo/config_height', new lang_string('config_height', 'block_uploadvimeo'), '', 400, PARAM_INT));
    
    
    // Settings - Privacy.
    $settings->add(new admin_setting_heading('block_uploadvimeo/config_headingprivacy', new lang_string('config_headingprivacy', 'block_uploadvimeo'), ''));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_privacyadd', new lang_string('config_privacyadd', 'block_uploadvimeo'), '', 0, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_privacycomments', new lang_string('config_privacycomments', 'block_uploadvimeo'), new lang_string('config_privacycomments_desc', 'block_uploadvimeo'), 2, $whocomment));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_privacydownload', new lang_string('config_privacydownload', 'block_uploadvimeo'), '', 0, $yesno));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_privacyembed', new lang_string('config_privacyembed', 'block_uploadvimeo'), '', 2, $embed));
    $settings->add(new admin_setting_configselect('block_uploadvimeo/config_privacyview', new lang_string('config_privacyview', 'block_uploadvimeo'), '', 5, $view));
    
    
    // Settings - Restrictions.
    $settings->add(new admin_setting_heading('block_uploadvimeo/config_headingrestriction', new lang_string('config_headingrestriction', 'block_uploadvimeo'), ''));
    $settings->add(new admin_setting_configtext('block_uploadvimeo/config_whitelist', new lang_string('config_whitelist', 'block_uploadvimeo'), null, '', PARAM_TEXT, 50));
    */
}
