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
 * Get all zoom activities that need to be uploaded to vimeo.
 *
 * @package   block_uploadvimeo
 * @copyright 2021 CCEAD PUC-Rio (@angela-araujo)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_uploadvimeo\task;

use block_uploadvimeo\local\uploadvimeo;

defined('MOODLE_INTERNAL') || die();

class zoom extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('zoomupload', 'block_uploadvimeo');
    }

    public function execute() {
        $trace = new \text_progress_trace();
        uploadvimeo::full_zoom_upload($trace);
    }
}
