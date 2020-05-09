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
 * Renderer for the uload video to vimeo block.
 *
 * @package   block_uploadvimeo
 * @copyright 2020 CCEAD PUC-Rio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_uploadvimeo\local\uploadvimeo;

class block_uploadvimeo_renderer extends plugin_renderer_base {
    
    /**
     * Display the main page block
     *
     * @param int $courseid
     * @return string output template
     */
    public function display_main_page($courseid) {
        
        $data = new stdClass();
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]);
        return $this->render_from_template('block_uploadvimeo/main', $data);
        
    }
    
    
    /**
     * Display Page Videos Vimeo
     * @param int $courseid The course id
     * @param int $userid The user logged
     * @param object $config Settings block upload vimeo
     */
    public function display_page_videos(int $courseid, int $userid, $config) {
        
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $username = $user->username;
        $usernamefolder = 'MoodleUpload_' . $username;
        
        $folder = uploadvimeo::get_folder($usernamefolder);
        
        $textmyvideos = get_string('text_line1', 'block_uploadvimeo');
        
        $videos = '';
        
        // Get all the videos in a folder.
        if ($folder) {
            
            $videos = uploadvimeo::get_videos_from_folder($folder['id']);
            
            if ($videos) {
                
                $textmyvideos .= '<br><br>' . get_string('text_line2_with_video', 'block_uploadvimeo') . '<br><br>';
                
            } else {
                
                $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
            }
            
        } else {
            
            $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
            
        }
        
        $data = new stdClass();
        $data->heading = get_string('pluginname', 'block_uploadvimeo');
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid, 'userid' => $userid]);
        $data->myvideos = $videos;
        $data->textmyvideos = $textmyvideos;
        $data->accesstoken = $config->config_accesstoken;
        $data->urldeletevideo = new moodle_url('/blocks/uploadvimeo/update.php', ['courseid' => $courseid, 'deletevideoid' => '']);
        $data->urleditthumbnail = new moodle_url('/blocks/uploadvimeo/update.php', ['courseid' => $courseid, 'videoid' => '']);
        $data->username = $username;
        
        // Start output to browser.
        echo $this->output->header();
        echo $this->render_from_template('block_uploadvimeo/form', $data);
        echo $this->output->footer();
        
    }
    
}
