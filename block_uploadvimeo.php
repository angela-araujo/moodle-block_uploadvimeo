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
 * Block for upload videos vimeo
 * 
 *  
 * @package   block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio <angela@ccead.puc-rio.br>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



class block_uploadvimeo extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_uploadvimeo');        
    }
    
    function get_content() {
        global $COURSE;
        
        // Do we have any content?
        if ($this->content !== null) {
            return $this->content;
        }
        
        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }        
        
        $context = context_course::instance($COURSE->id);
        
        if (has_capability('moodle/course:update', $context)) {
            // Get the block content.
            $this->content = new stdClass();
            $this->content->footer = '';
            $renderer = $this->page->get_renderer('block_uploadvimeo');
            $this->content->text = $renderer->display_main_page($COURSE->id);
            return $this->content;
            
        } else {
            return '';
        }
        
    }
    
    
    // Only one instance of this block is required.
    function instance_allow_multiple() {
        return false;
    }

    // Label and button values can be set in admin.
    function has_config() {
        return true;
    }
    
    /**
     * This is a list of places where the block may or
     * may not be added.
     */
    public function applicable_formats() {
        return array (
                'all' => false,
                'site' => false,
                'site-index' => false,
                'course' => true,
                'my' => false
        );
    }
	

}
