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
 * block_uploadvimeo view page
 *
 * @package block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require ('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid = $USER->id;
$urivideo = optional_param('urivideo', null, PARAM_TEXT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]);
$PAGE->set_context($coursecontext);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_uploadvimeo'));

require_login();

//@TODO: Add event log
// If we get here they have viewed the page.
// Log the page viewed event.
//$event = \block_uploadvimeo\event\block_page_viewed::create(['context' => $PAGE->context]);
//$event->trigger();

$config = get_config('block_uploadvimeo');

require_capability('block/uploadvimeo:seepagevideos', $coursecontext);

$renderer = $PAGE->get_renderer('block_uploadvimeo');
$renderer->display_page_videos($courseid, $userid, $urivideo, $config);

