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
 * block_uploadvimeo capabilities/permissions
 *
 * @package block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Modified for use in MoodleBites for Developers Level 1
 * by Richard Jones & Justin Hunt.
 *
 * See: https://www.moodlebites.com/mod/page/view.php?id=24546
 */
defined ( 'MOODLE_INTERNAL' ) || die ();

$capabilities = array (

        // User can add to Dashboard.
        'block/uploadvimeo:myaddinstance' => array (
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array (
                        'user' => CAP_ALLOW
                ),

                'clonepermissionsfrom' => 'moodle/my:manageblocks'
        ),

        // Specified user can add to courses etc.
        'block/uploadvimeo:addinstance' => array (
                'riskbitmask' => RISK_SPAM | RISK_XSS,

                'captype' => 'write',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => array (
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW
                ),

                'clonepermissionsfrom' => 'moodle/site:manageblocks'
        ),

        // Restrict access to view page.
        'block/uploadvimeo:seeviewpage' => array (
                'captype' => 'read',
                'contextlevel' => CONTEXT_USER,
                'archetypes' => array (
                        'editingteacher' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW,
                        'student' => CAP_ALLOW,
                        'guest' => CAP_PREVENT
                ),
                'clonepermissionsfrom' => 'moodle/site:manageblocks'
        )
);