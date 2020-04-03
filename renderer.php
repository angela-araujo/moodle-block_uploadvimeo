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
use Vimeo\Vimeo;
use Vimeo\Exceptions\VimeoUploadException;


class block_uploadvimeo_renderer extends plugin_renderer_base {
    
    /**
     * Display the main page block
     * 
     * @param int $blockid
     * @param int $courseid
     * @return string|boolean
     */
    function display_main_page($courseid) {
        
        $data = new stdClass();
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid]);        
        return $this->render_from_template('block_uploadvimeo/main', $data);

    }
    
    /**
     * Display form upload using Moodle
     * 
     * @deprecated 
     * @param string $form
     * @param int $blockid
     * @param int $courseid
     */
    function display_form_upload($form, $blockid, $courseid) {
        
        $data = new stdClass();
        $data->form = $form;
        $data->heading = get_string('pluginname', 'block_uploadvimeo');
        
        echo $this->output->header();
        echo $this->render_from_template('block_uploadvimeo/form', $data);
        echo $this->output->footer();
        
    }
    

    /**
     * Display form to upload video and the list of videos.
     * @param int $courseid
     * @param int $userid
     * @param string $action
     */
    function display_iframe(int $courseid, int $userid, string $urivideo = null){
        
        global $DB;
		$folderid = false;
        
        
        // Connect to Vimeo.
        $config = require(__DIR__ . '/vimeo_init.php');
        $client = new Vimeo($config['client_id'], $config['client_secret'], $config['access_token']);
        
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $usernamefolder = 'MoodleUpload_' . $user->username;
        $listmyvideos = array();

        // Verify if folder exists
        $folders = $client->request('/me/projects', array(), 'GET'); // echo '<pre>'; print_r($response['body']['data'][1]); echo '</pre>'; exit;
        
        if ($folders['body']['total'] <> '0') {
            
            // Looking for the folder from the user moodle.
            foreach ($folders['body']['data'] as $folderkey => $foldervalue) {
                
                if ($usernamefolder == $foldervalue['name']) {
                    
                    $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $foldervalue['uri']));
                    list($useridvimeo, $folderid) = explode(',', $urifolder);
                    break;
                    
                }
            }
        }
        
        
        // If there is uri video, then uploaded success.
        //$videoid = '403040371';
        
        //if ($videoid) {
        if ($urivideo) {
            $videoid = str_replace('/videos/', '', $urivideo);
            
            if ($folderid) {
                // Add video to the folder.
                $videoaddfolder = $client->request('/me/projects/' . $folderid . '/videos/' . $videoid, array(), 'PUT');
                
            } else {
                // PUT /me/projects/{project_id}/videos/{video_id}
                // Create folder
                $foldercreated = $client->request('/me/projects', array('name' => $usernamefolder), 'POST');
                
                if ($foldercreated['status'] == '201') { // 201 Created	- The folder was created.
                    
                    $urifolder = str_replace('/projects/', ',', str_replace('/users/', '', $foldercreated['body']['uri'])); ///users/42385845/projects/1621667
                    
                    
                    list($useridvimeo, $folderid) = explode(',', $urifolder);
                    
                    $videoaddfolder = $client->request('/me/projects/' . $folderid . '/videos/' . $videoid, array(), 'PUT');
                    
                    if ($videoaddfolder['status'] != '204') {    // 204 No Content	- The video was added.
                        echo '<hr><pre>response video add folder: <br>'; print_r($videoaddfolder); echo '</pre>';
                    }
                    
                } else {
                    echo '<hr><pre>response create folder: <br>'; print_r($foldercreated); echo '</pre>';
                }
                
                
            }
        }
        
        $textmyvideos = get_string('text_line1', 'block_uploadvimeo');
        
        // Get all the videos in a folder.
        if ($folderid) {								  
							
            $videos = $client->request('/me/projects/'.$folderid.'/videos');
            
            if ($videos['body']['total'] <> '0') { // OK
                
                $textmyvideos .= '<br><br>' . get_string('text_line2_with_video', 'block_uploadvimeo') . '<br><br>';
                foreach ($videos['body']['data'] as $videokey => $videovalue) {
                    $listmyvideos[] = array('name' => $videovalue['name'],
                            'linkvideo' => $videovalue['link'],
                            'videoid' => str_replace('/videos/', '', $videovalue['uri']), //[uri] => /videos/401242079
                            'htmlembed' => '' . $videovalue['embed']['html'] . '',
                            'thumbnail' => $videovalue['pictures']['sizes'][0]['link'],
                    );
					 
                }
            } else {
                $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
            }
        }else {
            $textmyvideos .= '<br><br>' . get_string('text_line2_empty', 'block_uploadvimeo') . '<br><br>';
        }
        
         
        $data = new stdClass();
        
        $data->heading = get_string('pluginname', 'block_uploadvimeo');
        $data->url = new moodle_url('/blocks/uploadvimeo/form.php', ['courseid' => $courseid, 'userid' => $userid]);
        
        // Return link course.
        $data->returnlink = new moodle_url('/course/view.php', ['id' => $courseid]);
        $data->returntext = get_string('returncourse', 'block_uploadvimeo');        
        
        $data->class = 'block_uploadviemto_links';
        $data->myvideos = $listmyvideos;
		$data->textmyvideos = $textmyvideos;								
        
        // Start output to browser.
        echo $this->output->header();
        echo $this->render_from_template('block_uploadvimeo/form', $data);
        echo $this->output->footer();
        
    }
    
    protected function get_all_videos_from_folder() {
        return ;
    }
    
    protected function upload_video_to_folder() {
        
        //@TODO 
        /**
         * @see https://developer.vimeo.com/api/upload/videos#resumable-approach
         * 1. Create the video
         *      Make an authenticated POST request to /me/videos:
         *      POST https://api.vimeo.com/me/videos
         *      
         * 2. Upload the video file
         *      PATCH the binary data of the entire video file to the URL from upload.upload_link, along with some custom tus headers:
         *      PATCH {upload.upload_link}
         *      
         * 3. Verify the upload
         *      To monitor the progress of the upload, send a HEAD request to upload.upload_link:
         *      HEAD {upload.upload_link}           
         */

        // Connect to Vimeo.
        $config = require(__DIR__ . '/vimeo_init.php');
        $client = new Vimeo($config['client_id'], $config['client_secret'], $config['access_token']);

        // Step 1: Creating video.
        $parambody = json_decode('{
            "upload": {
            "approach": "tus",
            "size": "524288000"
            }
        }');
        $autorization = 'bearer '.$config['access_token'];
        $response = $client->request('/me/videos', $parambody, 'POST', true, array('Authorization' => $autorization,
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.vimeo.*+json;version=3.4',
        ) );
        
        if ($response['status'] != '200') {
            // print error
        }
        
        // Step 2:
        
        
        
        
        return ;
    }
    
    
}
