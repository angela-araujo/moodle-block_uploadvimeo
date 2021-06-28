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
    public function display_page_videos(int $courseid, int $userid, $config, $page = 0) {
        
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $username = $user->username;
        
        if ($result = $DB->get_record('block_uploadvimeo_account', ['id' => $config->accountvimeo])) {
            $token = $result->accesstoken;
        }
        
        $textmyvideos = get_string('text_line1', 'block_uploadvimeo');
        
        $videos = uploadvimeo::get_videos_from_user($userid, $page);
            
        if ($videos) {
            
            $textmyvideos .= '<br><br>' . get_string('text_line2_with_video', 'block_uploadvimeo') . '<br><br>';
            
        } else {
            
            $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
        }

        $pagingbar = new paging_bar(
            $videos['totalvideos'],
            $page,
            VIDEOS_PER_PAGE,
            new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]));        
        
        $data = new stdClass();
        $data->navigationbar = $this->output->render($pagingbar);
        $data->heading = get_string('pluginname', 'block_uploadvimeo');
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]);
        $data->myvideos = $videos['videos'];
        $data->textmyvideos = $textmyvideos;
        $data->accesstoken = $token;
        $data->urldeletevideo = new moodle_url('/blocks/uploadvimeo/update.php', ['courseid' => $courseid, 'page' => $page, 'deletevideoid' => '']);
        $data->urleditthumbnail = new moodle_url('/blocks/uploadvimeo/update.php', ['courseid' => $courseid, 'page' => $page, 'videoid' => '']);
        $data->username = $username;
        
        // Start output to browser.
        echo $this->output->header();
        echo $this->render_from_template('block_uploadvimeo/form', $data);
        echo $this->output->footer(); 
        
    }
    
    public function display_page_account($records) {
        // Prepare the data for the template.
        $table = new stdClass();
        
        // Table headers.
        $table->tableheaders = array(
            get_string('name', 'block_uploadvimeo'),
            get_string('clientid', 'block_uploadvimeo'),
            get_string('clientsecret', 'block_uploadvimeo'),
            get_string('accesstoken', 'block_uploadvimeo'),
            get_string('app_id', 'block_uploadvimeo'),
            get_string('status', 'block_uploadvimeo'), 
            get_string('edit', 'block_uploadvimeo'),
            get_string('delete', 'block_uploadvimeo'));
        
        $status = array();
        $status[0] = get_string('inactive', 'block_uploadvimeo');
        $status[1] = get_string('active', 'block_uploadvimeo');
        
        // Build the data rows.
        foreach ($records as $record) {
            
            $urlbase = '/blocks/uploadvimeo/account.php';
           
            $data = array();
            $data[] = $record->name;
            $data[] = $record->clientid;
            $data[] = $record->clientsecret;
            $data[] = $record->accesstoken;
            $data[] = $record->app_id;
            $data[] = $status[$record->status];
            $url = new moodle_url($urlbase, ['id' => $record->id, 'action' => 'edit']);
            $data[] = html_writer::link($url, get_string('edit', 'block_uploadvimeo'));
            $url = new moodle_url($urlbase, ['id' => $record->id, 'action' => 'delete', 'sesskey'=>sesskey()]);
            $data[] = html_writer::link($url, get_string('delete', 'block_uploadvimeo'));
            
            $table->tabledata[] = $data;
        }        

        $table->btnnewaccount = html_writer::link(new moodle_url($urlbase, ['id' => -1, 'action' => 'add']),get_string('add', 'block_uploadvimeo'), array('class' => 'btn btn-primary'));
        $table->btnsettings = html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'blocksettinguploadvimeo']),get_string('settings'), array('class' => 'btn btn-primary'));
        
        // Call our template to render the data.
        return $this->render_from_template('block_uploadvimeo/account', $table);
    
    }
    
}
