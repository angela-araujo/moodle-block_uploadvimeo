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
 * Process Zoom webhook "All Recordings have completed".
 *
 * For more information visit:
 * https://marketplace.zoom.us/docs/api-reference/webhook-reference/recording-events/recording-completed
 *
 * @package   block_uploadvimeo
 * @copyright 2021 CCEAD PUC-Rio (@angela-araujo)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
   exit;
}
if (!$verificationtoken = get_config('zoom_verificationtoken', 'block_uploadvimeo')) {
    exit;
}
if ($headers['Authorization'] != $verificationtoken) {
    exit;
}
$json = file_get_contents("php://input");
if (!$notification = json_decode($json, false)) {
    exit;
}
if (!isset($notification->payload)) {
    exit;
}
require ('../../config.php');
block_uploadvimeo\local\uploadvimeo::zoom_process_webhook($notification);
