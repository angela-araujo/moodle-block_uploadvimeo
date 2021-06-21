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

namespace block_uploadvimeo\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The video upload event class
 * 
 */
class video_edit_title extends \core\event\base {
    
    protected function init() {
        $this->data['objecttable'] = 'block_uploadvimeo_videos';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }
    
    public static function get_name() {
        return get_string('event_video_edit_title', 'block_uploadvimeo');        
    }
    
    /**
     * Returns non-localised event description with id's for admin use only.
     * 
     * @return string
     * 
     */
    public function get_description() {
        return "The user with id '$this->userid' has
                edited the title video with the id '{$this->other['videoid']}'
                in the block Upload Vimeo with course
                id '{$this->courseid}'.";
    }
}